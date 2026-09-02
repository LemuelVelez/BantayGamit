<?php
namespace App\Application\Services;
use App\Models\NotificationModel;
class NotificationService {
    public function notify(int $userId,string $type,string $message): void { (new NotificationModel())->insert(['user_id'=>$userId,'type'=>$type,'message'=>$message,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')]); }
    public function notifyRoles(array $roles,string $type,string $message): void { $users=db_connect()->table('users')->select('id')->where('status','active')->whereIn('role',$roles)->get()->getResultArray(); foreach($users as $u)$this->notify((int)$u['id'],$type,$message); }
    public function markRead(int $notificationId,int $userId): void { (new NotificationModel())->where('id',$notificationId)->where('user_id',$userId)->set(['is_read'=>1])->update(); }
    public function markAllRead(int $userId): void { (new NotificationModel())->where('user_id',$userId)->set(['is_read'=>1])->update(); }
    public function unreadCount(int $userId): int { return (new NotificationModel())->where(['user_id'=>$userId,'is_read'=>0])->countAllResults(); }
}
