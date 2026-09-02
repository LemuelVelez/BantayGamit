<?php
namespace App\Models;
use CodeIgniter\Model;
class EquipmentLocationModel extends Model { protected $table='equipment_locations'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['name','description','status']; }
