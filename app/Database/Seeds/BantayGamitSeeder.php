<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
class BantayGamitSeeder extends Seeder {
 public function run():void {
  $now=date('Y-m-d H:i:s');
  $users=[
   ['username'=>'admin','password_hash'=>password_hash('Admin@12345',PASSWORD_DEFAULT),'display_name'=>'System Administrator','email'=>'admin@bantaygamit.local','role'=>'admin','status'=>'active','created_at'=>$now,'updated_at'=>$now],
   ['username'=>'official','password_hash'=>password_hash('Official@12345',PASSWORD_DEFAULT),'display_name'=>'Barangay Official','email'=>'official@bantaygamit.local','role'=>'barangay_official','status'=>'active','created_at'=>$now,'updated_at'=>$now],
   ['username'=>'borrower','password_hash'=>password_hash('Borrower@12345',PASSWORD_DEFAULT),'display_name'=>'Demo Borrower','email'=>'borrower@bantaygamit.local','contact_number'=>'0917 000 0000','address'=>'Sample Barangay Resident','role'=>'borrower','status'=>'active','created_at'=>$now,'updated_at'=>$now],
  ];
  $this->db->table('users')->insertBatch($users);$admin=(int)$this->db->insertID();
  $ids=[];foreach($this->db->table('users')->select('id,username')->get()->getResultArray() as $u)$ids[$u['username']]=(int)$u['id'];
  foreach(['Audio Equipment','Sports Equipment','Tables and Chairs','Emergency Equipment','Cleaning Equipment','Tools','Event Equipment'] as $name)$this->db->table('equipment_categories')->insert(['name'=>$name,'status'=>'active','created_at'=>$now,'updated_at'=>$now]);
  foreach(['Barangay Hall Storage Room','Covered Court Storage','Disaster Response Room'] as $name)$this->db->table('equipment_locations')->insert(['name'=>$name,'status'=>'active','created_at'=>$now,'updated_at'=>$now]);
  $cats=[];foreach($this->db->table('equipment_categories')->select('id,name')->get()->getResultArray() as $r)$cats[$r['name']]=$r['id'];$locs=[];foreach($this->db->table('equipment_locations')->select('id,name')->get()->getResultArray() as $r)$locs[$r['name']]=$r['id'];
  $equipment=[
   ['EQ-0001','Portable PA Speaker','Audio Equipment','Barangay Hall Storage Room',4,'unit','good'],['EQ-0002','Wireless Microphone','Audio Equipment','Barangay Hall Storage Room',8,'unit','excellent'],
   ['EQ-0003','Plastic Monobloc Chair','Tables and Chairs','Covered Court Storage',120,'piece','good'],['EQ-0004','Folding Table','Tables and Chairs','Covered Court Storage',24,'piece','good'],
   ['EQ-0005','Basketball','Sports Equipment','Covered Court Storage',12,'piece','fair'],['EQ-0006','First Aid Kit','Emergency Equipment','Disaster Response Room',10,'kit','excellent'],
   ['EQ-0007','Extension Cord 20m','Event Equipment','Barangay Hall Storage Room',6,'piece','good'],['EQ-0008','Power Drill','Tools','Barangay Hall Storage Room',3,'unit','good'],
  ];
  foreach($equipment as $e)$this->db->table('equipment')->insert(['asset_code'=>$e[0],'name'=>$e[1],'category_id'=>$cats[$e[2]],'location_id'=>$locs[$e[3]],'description'=>'Barangay-owned '.$e[1].' for approved community use.','total_quantity'=>$e[4],'unit'=>$e[5],'condition'=>$e[6],'status'=>'available','acquired_date'=>'2026-01-15','created_at'=>$now,'updated_at'=>$now]);
  $eq=[];foreach($this->db->table('equipment')->select('id,asset_code')->get()->getResultArray() as $r)$eq[$r['asset_code']]=$r['id'];
  $this->db->table('borrow_requests')->insert(['request_number'=>'BR-2026-000001','borrower_id'=>$ids['borrower'],'purpose'=>'Barangay youth sports activity','requested_date'=>date('Y-m-d',strtotime('+2 days')),'expected_return_date'=>date('Y-m-d',strtotime('+3 days')),'status'=>'pending','created_at'=>$now,'updated_at'=>$now]);$r1=(int)$this->db->insertID();
  $this->db->table('borrow_request_items')->insertBatch([['borrow_request_id'=>$r1,'equipment_id'=>$eq['EQ-0005'],'quantity_requested'=>3,'quantity_released'=>0,'quantity_returned'=>0,'created_at'=>$now,'updated_at'=>$now],['borrow_request_id'=>$r1,'equipment_id'=>$eq['EQ-0007'],'quantity_requested'=>1,'quantity_released'=>0,'quantity_returned'=>0,'created_at'=>$now,'updated_at'=>$now]]);
  $this->db->table('borrow_requests')->insert(['request_number'=>'BR-2026-000002','borrower_id'=>$ids['borrower'],'purpose'=>'Community meeting','requested_date'=>date('Y-m-d',strtotime('-4 days')),'expected_return_date'=>date('Y-m-d',strtotime('-2 days')),'status'=>'returned','approved_by'=>$ids['official'],'approved_at'=>date('Y-m-d H:i:s',strtotime('-5 days')),'released_by'=>$ids['official'],'released_at'=>date('Y-m-d H:i:s',strtotime('-4 days')),'returned_to'=>$ids['official'],'returned_at'=>date('Y-m-d H:i:s',strtotime('-2 days')),'created_at'=>date('Y-m-d H:i:s',strtotime('-6 days')),'updated_at'=>$now]);$r2=(int)$this->db->insertID();
  $this->db->table('borrow_request_items')->insert(['borrow_request_id'=>$r2,'equipment_id'=>$eq['EQ-0003'],'quantity_requested'=>20,'quantity_released'=>20,'quantity_returned'=>20,'condition_on_release'=>'good','condition_on_return'=>'good','created_at'=>$now,'updated_at'=>$now]);
  $this->db->table('maintenance_records')->insert(['equipment_id'=>$eq['EQ-0008'],'reported_by'=>$ids['official'],'maintenance_type'=>'Preventive inspection','description'=>'Routine inspection and cleaning before next release.','quantity'=>1,'status'=>'scheduled','start_date'=>date('Y-m-d',strtotime('+1 day')),'created_at'=>$now,'updated_at'=>$now]);
  $this->db->table('notifications')->insertBatch([['user_id'=>$ids['admin'],'type'=>'request_submitted','message'=>'New borrowing request BR-2026-000001 is awaiting review.','is_read'=>0,'created_at'=>$now],['user_id'=>$ids['official'],'type'=>'request_submitted','message'=>'New borrowing request BR-2026-000001 is awaiting review.','is_read'=>0,'created_at'=>$now],['user_id'=>$ids['borrower'],'type'=>'welcome','message'=>'Welcome to BantayGamit. You can now request available barangay equipment.','is_read'=>0,'created_at'=>$now]]);
  $this->db->table('settings')->insertBatch([['setting_key'=>'barangay_name','setting_value'=>'Sample Barangay','updated_by'=>$ids['admin'],'updated_at'=>$now],['setting_key'=>'due_soon_days','setting_value'=>'2','updated_by'=>$ids['admin'],'updated_at'=>$now]]);
  $this->db->table('audit_logs')->insert(['actor_user_id'=>$ids['admin'],'action'=>'development_seed','entity_type'=>'system','message'=>'Loaded BantayGamit development seed data.','created_at'=>$now]);
 }
}
