<?php
namespace App\Controllers;
class AuditLogsController extends BaseController { public function index(){return view('audit_logs/index',['title'=>'Audit Logs','logs'=>db_connect()->table('audit_logs a')->select('a.*,u.display_name actor_name')->join('users u','u.id=a.actor_user_id','left')->orderBy('a.created_at','DESC')->limit(250)->get()->getResultArray()]);} }
