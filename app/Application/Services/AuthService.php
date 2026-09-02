<?php
namespace App\Application\Services;
use App\Domain\Repositories\EquipmentRepositoryInterface;
class AuthService {
    public function __construct(private EquipmentRepositoryInterface $repository) {}
    public function authenticate(string $username,string $password): ?array {
        $user=$this->repository->findUserByUsername(trim($username));
        if(!$user || ($user['status']??'')!=='active' || !password_verify($password,(string)$user['password_hash'])) return null;
        unset($user['password_hash']); return $user;
    }
}
