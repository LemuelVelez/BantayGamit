<?php
namespace App\Controllers;
class AuditLogsController extends BaseController { public function index(){$filters=['q'=>(string)$this->request->getGet('q')];$page=$this->repository()->auditLogsPage($filters,$this->page(),25);return view('audit_logs/index',['title'=>'Audit Logs','logs'=>$page['rows'],'pagerLinks'=>$this->pagerLinks($page['page'],25,$page['total']),'total'=>$page['total']]);} }
