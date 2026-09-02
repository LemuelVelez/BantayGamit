<?php

use App\Application\Services\AuthService;
use App\Domain\Repositories\EquipmentRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testValidActiveUserCanAuthenticateAndHashIsRemoved(): void
    {
        $repo=$this->createMock(EquipmentRepositoryInterface::class);
        $repo->expects($this->once())->method('findUserByUsername')->with('admin')->willReturn([
            'id'=>1,'username'=>'admin','password_hash'=>password_hash('Admin@12345',PASSWORD_DEFAULT),
            'display_name'=>'System Administrator','role'=>'admin','status'=>'active',
        ]);
        $user=(new AuthService($repo))->authenticate(' admin ','Admin@12345');
        $this->assertNotNull($user);$this->assertSame('admin',$user['role']);$this->assertArrayNotHasKey('password_hash',$user);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $repo=$this->createMock(EquipmentRepositoryInterface::class);
        $repo->method('findUserByUsername')->willReturn(['id'=>1,'username'=>'admin','password_hash'=>password_hash('Admin@12345',PASSWORD_DEFAULT),'display_name'=>'Admin','role'=>'admin','status'=>'active']);
        $this->assertNull((new AuthService($repo))->authenticate('admin','wrong-password'));
    }

    public function testInactiveAccountIsRejected(): void
    {
        $repo=$this->createMock(EquipmentRepositoryInterface::class);
        $repo->method('findUserByUsername')->willReturn(['id'=>2,'username'=>'borrower','password_hash'=>password_hash('Borrower@12345',PASSWORD_DEFAULT),'display_name'=>'Borrower','role'=>'borrower','status'=>'inactive']);
        $this->assertNull((new AuthService($repo))->authenticate('borrower','Borrower@12345'));
    }
}
