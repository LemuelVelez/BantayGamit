<?php
namespace App\Controllers;
class ReturnsController extends BaseController { public function index(){return view('returns/index',['title'=>'Returns','rows'=>$this->repository()->borrowRequests($this->role(),$this->userId(),'returned')]);} }
