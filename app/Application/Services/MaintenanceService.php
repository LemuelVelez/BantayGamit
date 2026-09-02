<?php
namespace App\Application\Services;
use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Models\MaintenanceRecordModel;
use RuntimeException;
class MaintenanceService {
    private const TRANSITIONS=['reported'=>['scheduled','in_progress','completed','cancelled'],'scheduled'=>['reported','in_progress','completed','cancelled'],'in_progress'=>['scheduled','completed','cancelled'],'completed'=>[],'cancelled'=>[]];
    public function __construct(private EquipmentRepositoryInterface $repository,private ?AuditService $audit=null,private ?NotificationService $notifications=null){$this->audit??=new AuditService();$this->notifications??=new NotificationService();}
    public static function isTransitionAllowed(string $from,string $to): bool { return $from===$to||in_array($to,self::TRANSITIONS[$from]??[],true); }
    public function create(array $data,int $actorId): int {
        $eid=(int)($data['equipment_id']??0);$qty=(int)($data['quantity']??0);if(!$this->repository->findEquipment($eid))throw new RuntimeException('Equipment not found.');if($qty<1)throw new RuntimeException('Maintenance quantity must be at least 1.');if($qty>$this->repository->availableQuantity($eid))throw new RuntimeException('Maintenance quantity exceeds currently available equipment.');
        if(!in_array((string)($data['status']??''),['reported','scheduled','in_progress'],true))throw new RuntimeException('New maintenance records must start as Reported, Scheduled, or In Progress.');
        if(($data['completion_date']??null)!==null)throw new RuntimeException('Completion date is recorded when maintenance is completed.');
        if(isset($data['cost'])&&$data['cost']!==null&&(float)$data['cost']<0)throw new RuntimeException('Maintenance cost cannot be negative.');
        $data['reported_by']=$actorId;$model=new MaintenanceRecordModel();$model->insert($data);$id=(int)$model->getInsertID();$this->audit->log($actorId,'maintenance_created','maintenance_record',$id,'Created maintenance record');return $id;
    }
    public function updateStatus(int $id,string $status,int $actorId): void { if(!array_key_exists($status,config('BantayGamit')->maintenanceStatuses))throw new RuntimeException('Invalid maintenance status.');$m=new MaintenanceRecordModel();$row=$m->find($id);if(!$row)throw new RuntimeException('Maintenance record not found.');$from=(string)$row['status'];if(!self::isTransitionAllowed($from,$status))throw new RuntimeException('Invalid maintenance status transition from '.str_replace('_',' ',$from).' to '.str_replace('_',' ',$status).'.');if($from===$status)return;$data=['status'=>$status];if($status==='completed')$data['completion_date']=date('Y-m-d');$m->update($id,$data);$this->audit->log($actorId,'maintenance_'.$status,'maintenance_record',$id,'Maintenance record marked '.ucwords(str_replace('_',' ',$status)));if($status==='completed')$this->notifications->notifyRoles(['admin','barangay_official'],'maintenance_completed','A maintenance record has been completed.'); }
}
