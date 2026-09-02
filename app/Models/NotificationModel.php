<?php
namespace App\Models;
use CodeIgniter\Model;
class NotificationModel extends Model { protected $table='notifications'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=false; protected $allowedFields=['user_id','type','message','is_read','created_at']; }
