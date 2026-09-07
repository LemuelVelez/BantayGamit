<?php
namespace App\Application\Services;
use App\Models\NotificationModel;
class NotificationService {
    private const MESSAGE_LIMIT = 500;
    public function __construct(private ?AuditService $audit=null){$this->audit??=new AuditService();}
    public function notify(int $userId,string $type,string $message): void { $message=mb_substr($message,0,self::MESSAGE_LIMIT);(new NotificationModel())->insert(['user_id'=>$userId,'type'=>$type,'message'=>$message,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')]); }
    public function notifyRoles(array $roles,string $type,string $message): void { $users=db_connect()->table('users')->select('id')->where('status','active')->whereIn('role',$roles)->get()->getResultArray(); foreach($users as $u)$this->notify((int)$u['id'],$type,$message); }
    public function markRead(int $notificationId,int $userId): void { $m=new NotificationModel();$row=$m->where('id',$notificationId)->where('user_id',$userId)->first();if(!$row)return;if(!(int)$row['is_read']){$m->update((int)$row['id'],['is_read'=>1]);$this->audit->log($userId,'notification_read','notification',(int)$row['id'],'Marked notification as read');} }
    public function markAllRead(int $userId): void { $m=new NotificationModel();$count=$m->where(['user_id'=>$userId,'is_read'=>0])->countAllResults();if($count<1)return;$m->where('user_id',$userId)->where('is_read',0)->set(['is_read'=>1])->update();$this->audit->log($userId,'notifications_read_all','notification',null,'Marked all notifications as read',['count'=>$count]); }
    public function unreadCount(int $userId): int { return (new NotificationModel())->where(['user_id'=>$userId,'is_read'=>0])->countAllResults(); }
}
