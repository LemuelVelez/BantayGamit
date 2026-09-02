<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBantayGamitSchema extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'username' => ['type'=>'VARCHAR','constraint'=>80],
            'password_hash' => ['type'=>'VARCHAR','constraint'=>255],
            'display_name' => ['type'=>'VARCHAR','constraint'=>120],
            'email' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'contact_number' => ['type'=>'VARCHAR','constraint'=>40,'null'=>true],
            'address' => ['type'=>'TEXT','null'=>true],
            'role' => ['type'=>'ENUM','constraint'=>['admin','barangay_official','borrower']],
            'status' => ['type'=>'ENUM','constraint'=>['active','inactive'],'default'=>'active'],
            'created_at' => ['type'=>'DATETIME','null'=>true], 'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('username'); $this->forge->addUniqueKey('email'); $this->forge->createTable('users', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'name'=>['type'=>'VARCHAR','constraint'=>120],
            'description'=>['type'=>'TEXT','null'=>true], 'status'=>['type'=>'ENUM','constraint'=>['active','inactive'],'default'=>'active'],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('name'); $this->forge->createTable('equipment_categories', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'name'=>['type'=>'VARCHAR','constraint'=>150],
            'description'=>['type'=>'TEXT','null'=>true], 'status'=>['type'=>'ENUM','constraint'=>['active','inactive'],'default'=>'active'],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('name'); $this->forge->createTable('equipment_locations', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'asset_code'=>['type'=>'VARCHAR','constraint'=>40],
            'category_id'=>['type'=>'INT','unsigned'=>true], 'location_id'=>['type'=>'INT','unsigned'=>true], 'name'=>['type'=>'VARCHAR','constraint'=>150],
            'description'=>['type'=>'TEXT','null'=>true], 'total_quantity'=>['type'=>'INT','unsigned'=>true,'default'=>0], 'unit'=>['type'=>'VARCHAR','constraint'=>40,'default'=>'piece'],
            'condition'=>['type'=>'ENUM','constraint'=>['excellent','good','fair','damaged'],'default'=>'good'],
            'status'=>['type'=>'ENUM','constraint'=>['available','unavailable','maintenance','retired'],'default'=>'available'],
            'acquired_date'=>['type'=>'DATE','null'=>true], 'notes'=>['type'=>'TEXT','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('asset_code'); $this->forge->addKey('category_id'); $this->forge->addKey('location_id');
        $this->forge->addForeignKey('category_id','equipment_categories','id','RESTRICT','CASCADE'); $this->forge->addForeignKey('location_id','equipment_locations','id','RESTRICT','CASCADE');
        $this->forge->createTable('equipment', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'request_number'=>['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'borrower_id'=>['type'=>'INT','unsigned'=>true], 'purpose'=>['type'=>'TEXT'], 'requested_date'=>['type'=>'DATE'], 'expected_return_date'=>['type'=>'DATE'],
            'status'=>['type'=>'ENUM','constraint'=>['pending','approved','rejected','cancelled','released','returned','overdue'],'default'=>'pending'],
            'approved_by'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'approved_at'=>['type'=>'DATETIME','null'=>true], 'rejection_reason'=>['type'=>'TEXT','null'=>true],
            'released_by'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'released_at'=>['type'=>'DATETIME','null'=>true],
            'returned_to'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'returned_at'=>['type'=>'DATETIME','null'=>true], 'notes'=>['type'=>'TEXT','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('request_number'); $this->forge->addKey(['borrower_id','status']);
        $this->forge->addForeignKey('borrower_id','users','id','RESTRICT','CASCADE');
        $this->forge->addForeignKey('approved_by','users','id','SET NULL','CASCADE'); $this->forge->addForeignKey('released_by','users','id','SET NULL','CASCADE'); $this->forge->addForeignKey('returned_to','users','id','SET NULL','CASCADE');
        $this->forge->createTable('borrow_requests', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'borrow_request_id'=>['type'=>'INT','unsigned'=>true], 'equipment_id'=>['type'=>'INT','unsigned'=>true],
            'quantity_requested'=>['type'=>'INT','unsigned'=>true], 'quantity_released'=>['type'=>'INT','unsigned'=>true,'default'=>0], 'quantity_returned'=>['type'=>'INT','unsigned'=>true,'default'=>0],
            'condition_on_release'=>['type'=>'ENUM','constraint'=>['excellent','good','fair','damaged'],'null'=>true],
            'condition_on_return'=>['type'=>'ENUM','constraint'=>['excellent','good','fair','damaged'],'null'=>true], 'damage_notes'=>['type'=>'TEXT','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey(['borrow_request_id','equipment_id']); $this->forge->addKey('equipment_id');
        $this->forge->addForeignKey('borrow_request_id','borrow_requests','id','CASCADE','CASCADE'); $this->forge->addForeignKey('equipment_id','equipment','id','RESTRICT','CASCADE');
        $this->forge->createTable('borrow_request_items', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'equipment_id'=>['type'=>'INT','unsigned'=>true], 'reported_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'maintenance_type'=>['type'=>'VARCHAR','constraint'=>100], 'description'=>['type'=>'TEXT'], 'quantity'=>['type'=>'INT','unsigned'=>true,'default'=>1],
            'status'=>['type'=>'ENUM','constraint'=>['reported','scheduled','in_progress','completed','cancelled'],'default'=>'reported'],
            'start_date'=>['type'=>'DATE','null'=>true], 'completion_date'=>['type'=>'DATE','null'=>true], 'cost'=>['type'=>'DECIMAL','constraint'=>'12,2','null'=>true], 'notes'=>['type'=>'TEXT','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['equipment_id','status']);
        $this->forge->addForeignKey('equipment_id','equipment','id','RESTRICT','CASCADE'); $this->forge->addForeignKey('reported_by','users','id','SET NULL','CASCADE');
        $this->forge->createTable('maintenance_records', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'user_id'=>['type'=>'INT','unsigned'=>true], 'type'=>['type'=>'VARCHAR','constraint'=>80],
            'message'=>['type'=>'VARCHAR','constraint'=>500], 'is_read'=>['type'=>'TINYINT','constraint'=>1,'default'=>0], 'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['user_id','is_read']); $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE'); $this->forge->createTable('notifications', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'actor_user_id'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'action'=>['type'=>'VARCHAR','constraint'=>100],
            'entity_type'=>['type'=>'VARCHAR','constraint'=>80,'null'=>true], 'entity_id'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'message'=>['type'=>'VARCHAR','constraint'=>500],
            'metadata'=>['type'=>'TEXT','null'=>true], 'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['entity_type','entity_id']); $this->forge->addForeignKey('actor_user_id','users','id','SET NULL','CASCADE'); $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'setting_key'=>['type'=>'VARCHAR','constraint'=>100], 'setting_value'=>['type'=>'TEXT','null'=>true],
            'updated_by'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('setting_key'); $this->forge->addForeignKey('updated_by','users','id','SET NULL','CASCADE'); $this->forge->createTable('settings', true);
    }

    public function down(): void
    {
        foreach (['settings','audit_logs','notifications','maintenance_records','borrow_request_items','borrow_requests','equipment','equipment_locations','equipment_categories','users'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
