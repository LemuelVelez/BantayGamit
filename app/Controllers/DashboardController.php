<?php
namespace App\Controllers;
class DashboardController extends BaseController { public function index(){try{$this->borrowingService()->refreshOverdue();$data=$this->repository()->dashboard($this->role(),$this->userId());}catch(\Throwable){$data=[];session()->setFlashdata('error','Some dashboard data could not be loaded.');}$data['title']='Dashboard';$data['role']=$this->role();return view('dashboard/index',$data);} }
