<?php
namespace App\Models;
use CodeIgniter\Model;
class MaintenanceRecordModel extends Model { protected $table='maintenance_records'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['equipment_id','reported_by','maintenance_type','description','quantity','status','start_date','completion_date','cost','notes']; }
