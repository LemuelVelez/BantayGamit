<?php
namespace App\Controllers;
use App\Application\Services\AuditService;use App\Models\SettingModel;
class SettingsController extends BaseController {
 public function index(){$rows=(new SettingModel())->findAll();$settings=[];foreach($rows as $r)$settings[$r['setting_key']]=$r['setting_value'];return view('settings/index',['title'=>'Settings','settings'=>$settings]);}
 public function update(){if(!$this->validate(['barangay_name'=>'required|max_length[160]','due_soon_days'=>'required|integer|greater_than_equal_to[0]|less_than_equal_to[30]']))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());$m=new SettingModel();foreach(['barangay_name'=>$this->postString('barangay_name'),'due_soon_days'=>$this->postString('due_soon_days')] as $k=>$v){$row=$m->where('setting_key',$k)->first();$data=['setting_key'=>$k,'setting_value'=>$v,'updated_by'=>$this->userId(),'updated_at'=>date('Y-m-d H:i:s')];$row?$m->update((int)$row['id'],$data):$m->insert($data);}(new AuditService())->log($this->userId(),'settings_updated','settings',null,'Updated system settings');return redirect()->back()->with('success','Settings updated.');}
}
