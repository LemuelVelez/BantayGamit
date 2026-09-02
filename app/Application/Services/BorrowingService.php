<?php
namespace App\Application\Services;

use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Models\BorrowRequestItemModel;
use App\Models\BorrowRequestModel;
use RuntimeException;

class BorrowingService
{
    private const TRANSITIONS = [
        'pending'=>['approved','rejected','cancelled'], 'approved'=>['released'],
        'released'=>['returned','overdue'], 'overdue'=>['returned'], 'rejected'=>[], 'cancelled'=>[], 'returned'=>[],
    ];

    public function __construct(
        private EquipmentRepositoryInterface $repository,
        private ?NotificationService $notifications=null,
        private ?AuditService $audit=null
    ) { $this->notifications??=new NotificationService(); $this->audit??=new AuditService(); }

    public static function isTransitionAllowed(string $from,string $to): bool { return in_array($to,self::TRANSITIONS[$from]??[],true); }
    public static function isOverdueDate(string $expectedDate,string $status,?string $today=null): bool { return in_array($status,['released','overdue'],true) && $expectedDate<($today??date('Y-m-d')); }
    public static function validateReturnQuantities(int $released,int $alreadyReturned,int $returnNow): bool { return $returnNow>=0 && ($alreadyReturned+$returnNow)<=$released; }

    public function createRequest(int $borrowerId,array $payload,array $items): int
    {
        if(trim((string)($payload['purpose']??''))==='')throw new RuntimeException('Purpose of borrowing is required.');
        $requested=(string)($payload['requested_date']??''); $expected=(string)($payload['expected_return_date']??'');
        if(!$requested||!$expected||$expected<$requested)throw new RuntimeException('Expected return date must be on or after the requested borrowing date.');
        if(!$items)throw new RuntimeException('Select at least one equipment item.');
        $clean=[];
        foreach($items as $item){$eid=(int)($item['equipment_id']??0);$qty=(int)($item['quantity']??0);if($eid<1||$qty<1)continue;if(isset($clean[$eid]))$clean[$eid]+=$qty;else$clean[$eid]=$qty;}
        if(!$clean)throw new RuntimeException('Select valid equipment quantities.');
        foreach($clean as $eid=>$qty){$eq=$this->repository->findEquipment($eid);if(!$eq)throw new RuntimeException('Selected equipment no longer exists.');if($qty>$this->repository->availableQuantity($eid))throw new RuntimeException($eq['name'].' does not have enough available quantity.');}
        $db=db_connect();$db->transBegin();
        try{
            $requestModel=new BorrowRequestModel();$requestModel->insert(['request_number'=>null,'borrower_id'=>$borrowerId,'purpose'=>trim($payload['purpose']),'requested_date'=>$requested,'expected_return_date'=>$expected,'status'=>'pending','notes'=>trim((string)($payload['notes']??''))?:null]);
            $id=(int)$requestModel->getInsertID();$number=sprintf('BR-%s-%06d',date('Y'),$id);$requestModel->update($id,['request_number'=>$number]);
            $itemModel=new BorrowRequestItemModel();foreach($clean as $eid=>$qty)$itemModel->insert(['borrow_request_id'=>$id,'equipment_id'=>$eid,'quantity_requested'=>$qty,'quantity_released'=>0,'quantity_returned'=>0]);
            $this->audit->log($borrowerId,'request_submitted','borrow_request',$id,'Submitted borrowing request '.$number);
            $this->notifications->notifyRoles(['admin','barangay_official'],'request_submitted','New borrowing request '.$number.' is awaiting review.');
            if($db->transStatus()===false)throw new RuntimeException('Request could not be saved.');$db->transCommit();return $id;
        }catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function cancel(int $requestId,int $borrowerId): void
    {
        $request=$this->requireRequest($requestId);if((int)$request['borrower_id']!==$borrowerId)throw new RuntimeException('You can only cancel your own request.');if($request['status']!=='pending')throw new RuntimeException('Only pending requests can be cancelled.');
        $this->transition($request,'cancelled',$borrowerId,['message'=>'Cancelled borrowing request '.$request['request_number']]);
    }

    public function approve(int $requestId,int $officialId): void
    {
        $request=$this->requireRequest($requestId);$this->assertStaffActor($request,$officialId);$this->assertTransition($request['status'],'approved');
        foreach($this->repository->borrowRequestItems($requestId) as $item){$available=$this->repository->availableQuantity((int)$item['equipment_id'],$requestId);if((int)$item['quantity_requested']>$available)throw new RuntimeException($item['equipment_name'].' no longer has enough stock to approve this request.');}
        $db=db_connect();$db->transBegin();try{(new BorrowRequestModel())->update($requestId,['status'=>'approved','approved_by'=>$officialId,'approved_at'=>date('Y-m-d H:i:s'),'rejection_reason'=>null]);$this->audit->log($officialId,'request_approved','borrow_request',$requestId,'Approved borrowing request '.$request['request_number']);$this->notifications->notify((int)$request['borrower_id'],'request_approved','Your request '.$request['request_number'].' was approved.');if($db->transStatus()===false)throw new RuntimeException('Approval failed.');$db->transCommit();}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function reject(int $requestId,int $officialId,string $reason): void
    {
        $request=$this->requireRequest($requestId);$this->assertStaffActor($request,$officialId);$this->assertTransition($request['status'],'rejected');if(trim($reason)==='')throw new RuntimeException('A rejection reason is required.');
        $db=db_connect();$db->transBegin();try{(new BorrowRequestModel())->update($requestId,['status'=>'rejected','approved_by'=>$officialId,'approved_at'=>date('Y-m-d H:i:s'),'rejection_reason'=>trim($reason)]);$this->audit->log($officialId,'request_rejected','borrow_request',$requestId,'Rejected borrowing request '.$request['request_number']);$this->notifications->notify((int)$request['borrower_id'],'request_rejected','Your request '.$request['request_number'].' was rejected: '.trim($reason));if($db->transStatus()===false)throw new RuntimeException('Rejection failed.');$db->transCommit();}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function release(int $requestId,int $officialId,array $conditions): void
    {
        $request=$this->requireRequest($requestId);$this->assertStaffActor($request,$officialId);$this->assertTransition($request['status'],'released');$items=$this->repository->borrowRequestItems($requestId);
        foreach($items as $item){$available=$this->repository->availableQuantity((int)$item['equipment_id'],$requestId);if((int)$item['quantity_requested']>$available)throw new RuntimeException($item['equipment_name'].' no longer has enough stock to release.');}
        $db=db_connect();$db->transBegin();try{$model=new BorrowRequestItemModel();foreach($items as $item){$condition=(string)($conditions[(int)$item['id']]??'good');if(!array_key_exists($condition,config('BantayGamit')->conditions))$condition='good';$model->update((int)$item['id'],['quantity_released'=>(int)$item['quantity_requested'],'condition_on_release'=>$condition]);}(new BorrowRequestModel())->update($requestId,['status'=>'released','released_by'=>$officialId,'released_at'=>date('Y-m-d H:i:s')]);$this->audit->log($officialId,'equipment_released','borrow_request',$requestId,'Released equipment for '.$request['request_number']);$this->notifications->notify((int)$request['borrower_id'],'equipment_released','Equipment for '.$request['request_number'].' has been released.');if($db->transStatus()===false)throw new RuntimeException('Release failed.');$db->transCommit();}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function returnAll(int $requestId,int $officialId,array $conditions,array $damageNotes): void
    {
        $request=$this->requireRequest($requestId);$this->assertStaffActor($request,$officialId);if(!in_array($request['status'],['released','overdue'],true))throw new RuntimeException('Only released or overdue requests can be returned.');$items=$this->repository->borrowRequestItems($requestId);
        $db=db_connect();$db->transBegin();try{$model=new BorrowRequestItemModel();foreach($items as $item){$returnNow=(int)$item['quantity_released']-(int)$item['quantity_returned'];if(!self::validateReturnQuantities((int)$item['quantity_released'],(int)$item['quantity_returned'],$returnNow))throw new RuntimeException('Invalid return quantity.');$condition=(string)($conditions[(int)$item['id']]??'good');if(!array_key_exists($condition,config('BantayGamit')->conditions))$condition='good';$notes=trim((string)($damageNotes[(int)$item['id']]??''));$model->update((int)$item['id'],['quantity_returned'=>(int)$item['quantity_released'],'condition_on_return'=>$condition,'damage_notes'=>$notes?:null]);if($condition==='damaged'){$this->notifications->notifyRoles(['admin','barangay_official'],'damaged_equipment_reported',$item['equipment_name'].' was returned damaged on '.$request['request_number'].'.');}}(new BorrowRequestModel())->update($requestId,['status'=>'returned','returned_to'=>$officialId,'returned_at'=>date('Y-m-d H:i:s')]);$this->audit->log($officialId,'return_recorded','borrow_request',$requestId,'Recorded return for '.$request['request_number']);$this->notifications->notify((int)$request['borrower_id'],'equipment_returned','Return for '.$request['request_number'].' has been completed.');if($db->transStatus()===false)throw new RuntimeException('Return could not be recorded.');$db->transCommit();}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function refreshOverdue(): int
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $rows = $db->table('borrow_requests')->where('status', 'released')->where('expected_return_date <', $today)->get()->getResultArray();
        $count = 0;
        $model = new BorrowRequestModel();
        foreach ($rows as $r) {
            $model->update((int) $r['id'], ['status' => 'overdue']);
            $this->notifications->notify((int) $r['borrower_id'], 'equipment_overdue', 'Borrowing request ' . $r['request_number'] . ' is overdue.');
            $count++;
        }

        $setting = $db->table('settings')->select('setting_value')->where('setting_key', 'due_soon_days')->get()->getRowArray();
        $days = max(0, min(30, (int) ($setting['setting_value'] ?? 2)));
        if ($days > 0) {
            $until = date('Y-m-d', strtotime('+' . $days . ' days'));
            $dueSoon = $db->table('borrow_requests')->where('status', 'released')->where('expected_return_date >=', $today)->where('expected_return_date <=', $until)->get()->getResultArray();
            foreach ($dueSoon as $r) {
                $message = 'Borrowing request ' . $r['request_number'] . ' is due on ' . date('M j, Y', strtotime($r['expected_return_date'])) . '.';
                $exists = $db->table('notifications')->where(['user_id' => $r['borrower_id'], 'type' => 'equipment_due_soon', 'message' => $message])->countAllResults();
                if (! $exists) $this->notifications->notify((int) $r['borrower_id'], 'equipment_due_soon', $message);
            }
        }
        return $count;
    }

    private function transition(array $request,string $to,int $actorId,array $context=[]): void {$this->assertTransition($request['status'],$to);(new BorrowRequestModel())->update((int)$request['id'],['status'=>$to]);$this->audit->log($actorId,'request_'.$to,'borrow_request',(int)$request['id'],$context['message']??ucfirst($to).' '.$request['request_number']);}
    private function assertTransition(string $from,string $to): void { if(!self::isTransitionAllowed($from,$to))throw new RuntimeException('Invalid request status transition from '.$from.' to '.$to.'.'); }
    private function requireRequest(int $id): array { $r=$this->repository->findBorrowRequest($id);if(!$r)throw new RuntimeException('Borrowing request not found.');return $r; }
    private function assertStaffActor(array $request,int $actorId): void { if((int)$request['borrower_id']===$actorId)throw new RuntimeException('A borrower cannot approve, reject, release, or receive their own request.'); }
}
