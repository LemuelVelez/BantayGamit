<?php
namespace App\Controllers;
use App\Application\Services\AuditService;use App\Models\UserModel;
class ProfileController extends BaseController {
 public function index(){return view('profile/index',['title'=>'Profile','user'=>(new UserModel())->find($this->userId())]);}
 public function update(){if(!$this->validate('profile'))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());$m=new UserModel();$email=$this->postString('email');if($email&&$m->where('email',$email)->where('id !=',$this->userId())->first())return redirect()->back()->withInput()->with('errors',['email'=>'Email is already in use.']);$data=['display_name'=>$this->postString('display_name'),'email'=>$email?:null,'contact_number'=>$this->postString('contact_number')?:null,'address'=>$this->postString('address')?:null];if($this->postString('password'))$data['password_hash']=password_hash($this->postString('password'),PASSWORD_DEFAULT);try{$m->update($this->userId(),$data);session()->set('display_name',$data['display_name']);(new AuditService())->log($this->userId(),'profile_updated','user',$this->userId(),'Updated personal profile');return redirect()->back()->with('success','Profile updated.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$this->safeErrorMessage($e,'Profile could not be updated.'));}}
}
