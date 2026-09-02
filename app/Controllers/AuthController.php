<?php
namespace App\Controllers;
use App\Application\Services\AuthService;use App\Application\Services\NotificationService;
class AuthController extends BaseController {
 public function login(){if(session()->get('user_id'))return redirect()->to('/dashboard');return view('auth/login',['title'=>'Sign in']);}
 public function attempt(){if(!$this->validate(['username'=>'required|min_length[3]|max_length[80]','password'=>'required|min_length[6]|max_length[255]']))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());$username=$this->postString('username');try{$user=(new AuthService($this->repository()))->authenticate($username,$this->postString('password'));}catch(\Throwable){return redirect()->back()->withInput()->with('error','Sign-in is temporarily unavailable. Please try again.');}if(!$user)return redirect()->back()->withInput()->with('error','Invalid username or password.');session()->regenerate(true);session()->set(['user_id'=>$user['id'],'username'=>$user['username'],'display_name'=>$user['display_name'],'role'=>$user['role'],'unread_count'=>(new NotificationService())->unreadCount((int)$user['id'])]);return redirect()->to('/dashboard');}
 public function logout(){session()->destroy();return redirect()->to('/login');}
}
