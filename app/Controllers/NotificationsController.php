<?php
namespace App\Controllers;
use App\Application\Services\NotificationService;
class NotificationsController extends BaseController {
 public function index(){return view('notifications/index',['title'=>'Notifications','notifications'=>$this->repository()->notifications($this->userId())]);}
 public function read(int $id){(new NotificationService())->markRead($id,$this->userId());return redirect()->back();}
 public function readAll(){(new NotificationService())->markAllRead($this->userId());return redirect()->back()->with('success','All notifications marked as read.');}
}
