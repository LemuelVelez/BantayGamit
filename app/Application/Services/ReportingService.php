<?php
namespace App\Application\Services;
class ReportingService {
    public function report(string $type,array $filters=[]): array {
        $db=db_connect();$from=$filters['from']??null;$to=$filters['to']??null;$categoryId=(int)($filters['category_id']??0);$status=(string)($filters['status']??'');$borrowerId=(int)($filters['borrower_id']??0);$equipmentId=(int)($filters['equipment_id']??0);
        if($type==='inventory'||$type==='available'||$type==='damaged'){
            $b=$db->table('equipment e')->select('e.asset_code,e.name,c.name category,l.name location,e.total_quantity,e.condition,e.status')->join('equipment_categories c','c.id=e.category_id')->join('equipment_locations l','l.id=e.location_id');
            if($type==='damaged')$b->where('e.condition','damaged');if($categoryId)$b->where('e.category_id',$categoryId);if($equipmentId)$b->where('e.id',$equipmentId);if($status&&array_key_exists($status,config('BantayGamit')->equipmentStatuses))$b->where('e.status',$status);$rows=$b->orderBy('e.name')->get()->getResultArray();
            if($type==='available'){$repo=new \App\Infrastructure\Persistence\MySqlEquipmentRepository($db);foreach($rows as &$r){$eq=$db->table('equipment')->select('id')->where('asset_code',$r['asset_code'])->get()->getRowArray();$r['available_quantity']=$repo->availableQuantity((int)$eq['id']);}unset($r);$rows=array_values(array_filter($rows,fn($r)=>(int)$r['available_quantity']>0));}
            return $rows;
        }
        if($type==='maintenance'){
            $b=$db->table('maintenance_records m')->select('m.id,e.asset_code,e.name equipment_name,m.maintenance_type,m.quantity,m.status,m.start_date,m.completion_date,m.cost,u.display_name reported_by_name')->join('equipment e','e.id=m.equipment_id')->join('users u','u.id=m.reported_by','left');
            if($from)$b->where('m.created_at >=',$from.' 00:00:00');if($to)$b->where('m.created_at <=',$to.' 23:59:59');if($equipmentId)$b->where('m.equipment_id',$equipmentId);if($categoryId)$b->where('e.category_id',$categoryId);if($status&&array_key_exists($status,config('BantayGamit')->maintenanceStatuses))$b->where('m.status',$status);return $b->orderBy('m.created_at','DESC')->get()->getResultArray();
        }
        $b=$db->table('borrow_requests br')->select('br.request_number,u.display_name borrower,br.purpose,br.requested_date,br.expected_return_date,br.status,br.created_at')->join('users u','u.id=br.borrower_id');
        if($equipmentId||$categoryId){$b->join('borrow_request_items bri','bri.borrow_request_id=br.id')->join('equipment e','e.id=bri.equipment_id');if($equipmentId)$b->where('bri.equipment_id',$equipmentId);if($categoryId)$b->where('e.category_id',$categoryId);$b->groupBy('br.id');}
        if($type==='borrowed')$b->whereIn('br.status',['released','overdue']);if($type==='overdue')$b->where('br.status','overdue');if($from)$b->where('br.created_at >=',$from.' 00:00:00');if($to)$b->where('br.created_at <=',$to.' 23:59:59');if($borrowerId)$b->where('br.borrower_id',$borrowerId);if($status&&array_key_exists($status,config('BantayGamit')->requestStatuses))$b->where('br.status',$status);return $b->orderBy('br.created_at','DESC')->get()->getResultArray();
    }
}
