<?php
namespace App\Models;
use CodeIgniter\Model;
class EquipmentModel extends Model { protected $table='equipment'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['asset_code','category_id','location_id','name','description','total_quantity','unit','condition','status','acquired_date','notes']; }
