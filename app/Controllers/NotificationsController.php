<?php
namespace App\Controllers;
use App\Application\Services\NotificationService;
class NotificationsController extends BaseController {
 public function index(){$filters=['q'=>(string)$this->request->getGet('q'),'read'=>(string)$this->request->getGet('read')];$page=$this->repository()->notificationsPage($this->userId(),$filters,$this->page(),25);return view('notifications/index',['title'=>'Notifications','notifications'=>$page['rows'],'pagerLinks'=>$this->pagerLinks($page['page'],25,$page['total']),'total'=>$page['total']]);}
 public function read(int $id){try{(new NotificationService())->markRead($id,$this->userId());return redirect()->back()->with('success','Notification marked as read.');}catch(\Throwable $e){return redirect()->back()->with('error',$this->safeErrorMessage($e,'Notification could not be marked as read.'));}}
 public function readAll(){try{(new NotificationService())->markAllRead($this->userId());return redirect()->back()->with('success','All notifications marked as read.');}catch(\Throwable $e){return redirect()->back()->with('error',$this->safeErrorMessage($e,'Notifications could not be marked as read.'));}}
}
