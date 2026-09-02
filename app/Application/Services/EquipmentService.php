<?php
namespace App\Application\Services;
use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Models\EquipmentModel;
use RuntimeException;
class EquipmentService {
    public function __construct(private EquipmentRepositoryInterface $repository, private ?AuditService $audit=null){$this->audit??=new AuditService();}
    public function availableQuantity(int $equipmentId,?int $excludeRequestId=null): int { return $this->repository->availableQuantity($equipmentId,$excludeRequestId); }
    public function create(array $data,int $actorId): int {
        $this->validateInventory($data); $this->validateReferences($data); $model=new EquipmentModel();
        if($model->where('asset_code',$data['asset_code'])->first()) throw new RuntimeException('Asset code is already in use.');
        $model->insert($data); $id=(int)$model->getInsertID(); $this->audit->log($actorId,'equipment_created','equipment',$id,'Created equipment '.$data['asset_code']); return $id;
    }
    public function update(int $id,array $data,int $actorId): void {
        $this->validateInventory($data); $this->validateReferences($data); $model=new EquipmentModel(); if(!$model->find($id)) throw new RuntimeException('Equipment not found.');
        $dup=$model->where('asset_code',$data['asset_code'])->where('id !=',$id)->first(); if($dup)throw new RuntimeException('Asset code is already in use.');
        $active=$this->activeAllocatedQuantity($id); if((int)$data['total_quantity']<$active) throw new RuntimeException('Total quantity cannot be lower than quantities currently reserved, borrowed, or under maintenance.');
        $model->update($id,$data); $this->audit->log($actorId,'equipment_updated','equipment',$id,'Updated equipment '.$data['asset_code']);
    }
    private function validateInventory(array $data): void {
        if(trim((string)($data['asset_code']??''))===''||trim((string)($data['name']??''))==='')throw new RuntimeException('Asset code and equipment name are required.');
        if((int)($data['total_quantity']??-1)<0)throw new RuntimeException('Total quantity cannot be negative.');
        if(!array_key_exists((string)($data['status']??''),config('BantayGamit')->equipmentStatuses))throw new RuntimeException('Invalid equipment status.');
        if(!array_key_exists((string)($data['condition']??''),config('BantayGamit')->conditions))throw new RuntimeException('Invalid equipment condition.');
    }

    private function validateReferences(array $data): void {
        $db=db_connect();
        $category=$db->table('equipment_categories')->select('id')->where('id',(int)($data['category_id']??0))->get()->getRowArray();
        $location=$db->table('equipment_locations')->select('id')->where('id',(int)($data['location_id']??0))->get()->getRowArray();
        if(!$category)throw new RuntimeException('Selected equipment category does not exist.');
        if(!$location)throw new RuntimeException('Selected equipment location does not exist.');
    }
    private function activeAllocatedQuantity(int $equipmentId): int {
        $db=db_connect();
        $borrowed=(int)($db->table('borrow_request_items bri')->select("COALESCE(SUM(CASE WHEN br.status='approved' THEN bri.quantity_requested ELSE GREATEST(bri.quantity_released-bri.quantity_returned,0) END),0) qty",false)->join('borrow_requests br','br.id=bri.borrow_request_id')->where('bri.equipment_id',$equipmentId)->whereIn('br.status',['approved','released','overdue'])->get()->getRowArray()['qty']??0);
        $maint=(int)($db->table('maintenance_records')->select('COALESCE(SUM(quantity),0) qty',false)->where('equipment_id',$equipmentId)->whereIn('status',['reported','scheduled','in_progress'])->get()->getRowArray()['qty']??0);
        return $borrowed+$maint;
    }
}
