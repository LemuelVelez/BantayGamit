<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\EquipmentRepositoryInterface;
use CodeIgniter\Database\BaseConnection;

class MySqlEquipmentRepository implements EquipmentRepositoryInterface
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null) { $this->db = $db ?? db_connect(); }

    public function findUserByUsername(string $username): ?array
    {
        return $this->db->table('users')->where('username', $username)->get()->getRowArray() ?: null;
    }

    public function findUser(int $id): ?array
    {
        return $this->db->table('users')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public function equipment(array $filters = []): array
    {
        $builder = $this->db->table('equipment e')
            ->select('e.*, c.name AS category_name, l.name AS location_name')
            ->join('equipment_categories c', 'c.id=e.category_id')
            ->join('equipment_locations l', 'l.id=e.location_id');
        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $builder->groupStart()->like('e.name', $q)->orLike('e.asset_code', $q)->orLike('e.description', $q)->groupEnd();
        }
        if (! empty($filters['status'])) $builder->where('e.status', $filters['status']);
        if (! empty($filters['category_id'])) $builder->where('e.category_id', (int) $filters['category_id']);
        $rows = $builder->orderBy('e.name')->get()->getResultArray();
        foreach ($rows as &$row) $row['available_quantity'] = $this->availableQuantity((int) $row['id']);
        return $rows;
    }

    public function findEquipment(int $id): ?array
    {
        $row = $this->db->table('equipment e')->select('e.*,c.name AS category_name,l.name AS location_name')
            ->join('equipment_categories c','c.id=e.category_id')->join('equipment_locations l','l.id=e.location_id')
            ->where('e.id',$id)->get()->getRowArray();
        if ($row) $row['available_quantity'] = $this->availableQuantity($id);
        return $row ?: null;
    }

    public function equipmentOptions(): array
    {
        $rows = $this->equipment(['status'=>'available']);
        return array_values(array_filter($rows, static fn(array $row): bool => (int) $row['available_quantity'] > 0));
    }

    public function categories(bool $activeOnly = false): array
    {
        $b=$this->db->table('equipment_categories'); if($activeOnly)$b->where('status','active'); return $b->orderBy('name')->get()->getResultArray();
    }

    public function locations(bool $activeOnly = false): array
    {
        $b=$this->db->table('equipment_locations'); if($activeOnly)$b->where('status','active'); return $b->orderBy('name')->get()->getResultArray();
    }

    public function availableQuantity(int $equipmentId, ?int $excludeRequestId = null): int
    {
        $equipment = $this->db->table('equipment')->select('total_quantity,status')->where('id',$equipmentId)->get()->getRowArray();
        if (! $equipment || in_array($equipment['status'], ['unavailable','maintenance','retired'], true)) return 0;

        $reserved = $this->db->table('borrow_request_items bri')
            ->select("COALESCE(SUM(CASE WHEN br.status = 'approved' THEN bri.quantity_requested ELSE GREATEST(bri.quantity_released - bri.quantity_returned,0) END),0) AS qty", false)
            ->join('borrow_requests br','br.id=bri.borrow_request_id')
            ->where('bri.equipment_id',$equipmentId)
            ->whereIn('br.status',['approved','released','overdue']);
        if ($excludeRequestId) $reserved->where('br.id !=', $excludeRequestId);
        $reservedQty = (int) ($reserved->get()->getRowArray()['qty'] ?? 0);

        $maintenanceQty = (int) ($this->db->table('maintenance_records')
            ->select('COALESCE(SUM(quantity),0) AS qty', false)->where('equipment_id',$equipmentId)
            ->whereIn('status',['reported','scheduled','in_progress'])->get()->getRowArray()['qty'] ?? 0);
        return max(0, (int) $equipment['total_quantity'] - $reservedQty - $maintenanceQty);
    }

    public function borrowRequests(string $role, int $userId, ?string $status = null): array
    {
        $b=$this->db->table('borrow_requests br')->select('br.*,u.display_name AS borrower_name, COUNT(bri.id) AS item_count, COALESCE(SUM(bri.quantity_requested),0) AS requested_units', false)
            ->join('users u','u.id=br.borrower_id')->join('borrow_request_items bri','bri.borrow_request_id=br.id','left')->groupBy('br.id');
        if($role==='borrower')$b->where('br.borrower_id',$userId); if($status)$b->where('br.status',$status);
        return $b->orderBy('br.created_at','DESC')->get()->getResultArray();
    }

    public function findBorrowRequest(int $id): ?array
    {
        return $this->db->table('borrow_requests br')->select('br.*,u.display_name AS borrower_name,u.email AS borrower_email,u.contact_number AS borrower_contact,u.address AS borrower_address')
            ->join('users u','u.id=br.borrower_id')->where('br.id',$id)->get()->getRowArray() ?: null;
    }

    public function borrowRequestItems(int $requestId): array
    {
        return $this->db->table('borrow_request_items bri')->select('bri.*,e.asset_code,e.name AS equipment_name,e.unit,e.total_quantity')
            ->join('equipment e','e.id=bri.equipment_id')->where('bri.borrow_request_id',$requestId)->orderBy('e.name')->get()->getResultArray();
    }

    public function notifications(int $userId, int $limit = 50): array
    {
        return $this->db->table('notifications')->where('user_id',$userId)->orderBy('created_at','DESC')->limit($limit)->get()->getResultArray();
    }

    public function dashboard(string $role, int $userId): array
    {
        if ($role === 'borrower') {
            $requests=$this->borrowRequests($role,$userId);
            $active=array_values(array_filter($requests,static fn($r)=>in_array($r['status'],['approved','released','overdue'],true)));
            $today=date('Y-m-d');
            $dueDates=array_values(array_filter(array_map(static fn($r)=>$r['status']==='released'&&$r['expected_return_date']>=$today?$r['expected_return_date']:null,$requests)));
            sort($dueDates);
            $borrowedUnits=(int)($this->db->table('borrow_request_items bri')->select('COALESCE(SUM(bri.quantity_released-bri.quantity_returned),0) AS qty',false)->join('borrow_requests br','br.id=bri.borrow_request_id')->where('br.borrower_id',$userId)->whereIn('br.status',['released','overdue'])->get()->getRowArray()['qty']??0);
            return [
                'activeRequests'=>count($active), 'approvedRequests'=>count(array_filter($requests,fn($r)=>$r['status']==='approved')),
                'borrowedCount'=>$borrowedUnits,
                'overdueCount'=>count(array_filter($requests,fn($r)=>$r['status']==='overdue')), 'nextDueDate'=>$dueDates[0]??null,
                'recentRequests'=>array_slice($requests,0,6), 'availableEquipment'=>array_slice($this->equipmentOptions(),0,6),
            ];
        }

        $stats=[];
        $stats['totalEquipment']=(int)$this->db->table('equipment')->selectSum('total_quantity','qty')->get()->getRowArray()['qty'];
        $equipment=$this->equipment(); $stats['availableEquipment']=array_sum(array_column($equipment,'available_quantity'));
        $stats['currentlyBorrowed']=(int)($this->db->table('borrow_request_items bri')->select('COALESCE(SUM(bri.quantity_released-bri.quantity_returned),0) AS qty',false)->join('borrow_requests br','br.id=bri.borrow_request_id')->whereIn('br.status',['released','overdue'])->get()->getRowArray()['qty']??0);
        $stats['pendingRequests']=$this->db->table('borrow_requests')->where('status','pending')->countAllResults();
        $stats['overdueBorrowings']=$this->db->table('borrow_requests')->where('status','overdue')->countAllResults();
        $stats['maintenanceItems']=(int)($this->db->table('maintenance_records')->select('COALESCE(SUM(quantity),0) AS qty',false)->whereIn('status',['reported','scheduled','in_progress'])->get()->getRowArray()['qty']??0);
        $stats['activeBorrowers']=$this->db->table('users')->where(['role'=>'borrower','status'=>'active'])->countAllResults();
        $stats['recentRequests']=$this->borrowRequests($role,$userId);
        $stats['recentRequests']=array_slice($stats['recentRequests'],0,6);
        $stats['overdue']=$this->borrowRequests($role,$userId,'overdue');
        $stats['recentActivity']=$this->db->table('audit_logs a')->select('a.*,u.display_name AS actor_name')->join('users u','u.id=a.actor_user_id','left')->orderBy('a.created_at','DESC')->limit(6)->get()->getResultArray();
        $stats['equipmentByCategory']=$this->db->table('equipment_categories c')->select('c.name,COALESCE(SUM(e.total_quantity),0) AS total',false)->join('equipment e','e.category_id=c.id','left')->where('c.status','active')->groupBy('c.id')->orderBy('total','DESC')->limit(7)->get()->getResultArray();
        $stats['mostBorrowed']=$this->db->table('equipment e')->select('e.name,COALESCE(SUM(bri.quantity_released),0) AS total',false)->join('borrow_request_items bri','bri.equipment_id=e.id','left')->groupBy('e.id')->orderBy('total','DESC')->limit(5)->get()->getResultArray();
        $stats['monthlyActivity']=$this->db->table('borrow_requests')->select("DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total",false)->where('created_at >=',date('Y-m-01 00:00:00',strtotime('-5 months')))->groupBy("DATE_FORMAT(created_at, '%Y-%m')",false)->orderBy('month','ASC')->get()->getResultArray();
        if($role==='barangay_official'){
            $stats['approvedToRelease']=$this->db->table('borrow_requests')->where('status','approved')->countAllResults();
            $stats['dueToday']=$this->db->table('borrow_requests')->whereIn('status',['released','overdue'])->where('expected_return_date',date('Y-m-d'))->countAllResults();
            $stats['returnsToInspect']=$this->db->table('borrow_requests')->whereIn('status',['released','overdue'])->countAllResults();
        }
        return $stats;
    }
}
