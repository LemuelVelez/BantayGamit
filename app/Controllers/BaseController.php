<?php

namespace App\Controllers;

use App\Application\Services\BorrowingService;
use App\Application\Services\EquipmentService;
use App\Infrastructure\Persistence\MySqlEquipmentRepository;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected function repository(): MySqlEquipmentRepository { return new MySqlEquipmentRepository(); }
    protected function equipmentService(): EquipmentService { return new EquipmentService($this->repository()); }
    protected function borrowingService(): BorrowingService { return new BorrowingService($this->repository()); }
    protected function postString(string $key): string { $v=$this->request->getPost($key); return is_scalar($v)?trim((string)$v):''; }
    protected function postPositiveInt(string $key): int { $v=$this->postString($key); return preg_match('/^[1-9]\d*$/',$v)?(int)$v:0; }
    protected function safeErrorMessage(\Throwable $e,string $fallback): string { return $e instanceof \RuntimeException && !($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException)?$e->getMessage():$fallback; }
    protected function userId(): int { return (int)session()->get('user_id'); }
    protected function role(): string { return (string)session()->get('role'); }
    public function initController(RequestInterface $request,ResponseInterface $response,LoggerInterface $logger): void { $this->helpers=['form','url','bantay']; parent::initController($request,$response,$logger); }
}
