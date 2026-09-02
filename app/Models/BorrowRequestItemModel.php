<?php
namespace App\Models;
use CodeIgniter\Model;
class BorrowRequestItemModel extends Model { protected $table='borrow_request_items'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['borrow_request_id','equipment_id','quantity_requested','quantity_released','quantity_returned','condition_on_release','condition_on_return','damage_notes']; }
