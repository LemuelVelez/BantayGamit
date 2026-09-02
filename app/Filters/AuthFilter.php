<?php
namespace App\Filters;
use App\Application\Services\NotificationService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
class AuthFilter implements FilterInterface {
    public function before(RequestInterface $request,$arguments=null){$id=(int)session()->get('user_id');if(!$id)return redirect()->to('/login')->with('error','Please sign in to continue.');try{$u=db_connect()->table('users')->select('id,username,display_name,role,status')->where('id',$id)->get()->getRowArray();}catch(\Throwable){session()->destroy();return redirect()->to('/login')->with('error','Your session could not be verified. Please sign in again.');}if(!$u||($u['status']??'')!=='active'){session()->destroy();return redirect()->to('/login')->with('error','Your account is inactive or no longer available.');}session()->set(['username'=>$u['username'],'display_name'=>$u['display_name'],'role'=>$u['role'],'unread_count'=>(new NotificationService())->unreadCount($id)]);}
    public function after(RequestInterface $request,ResponseInterface $response,$arguments=null){$response->setHeader('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');$response->setHeader('Pragma','no-cache');$response->setHeader('Expires','0');}
}
