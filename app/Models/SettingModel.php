<?php
namespace App\Models;
use CodeIgniter\Model;
class SettingModel extends Model { protected $table='settings'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=false; protected $allowedFields=['setting_key','setting_value','updated_by','updated_at']; }
