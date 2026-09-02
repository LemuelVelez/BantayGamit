<?php
namespace App\Controllers;
class BorrowingsController extends BaseController {
 public function index(){try{$this->borrowingService()->refreshOverdue();}catch(\Throwable){}$all=$this->repository()->borrowRequests($this->role(),$this->userId());$rows=array_values(array_filter($all,fn($r)=>in_array($r['status'],['released','overdue'],true)));return view('borrowings/index',['title'=>$this->role()==='borrower'?'Borrowed Equipment':'Active Borrowings','rows'=>$rows,'history'=>false]);}
 public function history(){$rows=array_values(array_filter($this->repository()->borrowRequests('borrower',$this->userId()),fn($r)=>in_array($r['status'],['returned','rejected','cancelled'],true)));return view('borrowings/index',['title'=>'Borrowing History','rows'=>$rows,'history'=>true]);}
}
