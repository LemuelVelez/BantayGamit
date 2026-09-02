<?php
namespace App\Models;
use CodeIgniter\Model;
class EquipmentCategoryModel extends Model { protected $table='equipment_categories'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['name','description','status']; }
