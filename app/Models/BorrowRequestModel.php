<?php
namespace App\Models;
use CodeIgniter\Model;
class BorrowRequestModel extends Model { protected $table='borrow_requests'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true; protected $allowedFields=['request_number','borrower_id','purpose','requested_date','expected_return_date','status','approved_by','approved_at','rejection_reason','released_by','released_at','returned_to','returned_at','notes']; }
