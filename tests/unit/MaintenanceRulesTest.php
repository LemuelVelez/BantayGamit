<?php

use App\Application\Services\MaintenanceService;
use PHPUnit\Framework\TestCase;

final class MaintenanceRulesTest extends TestCase
{
    public function testAllowedMaintenanceTransitions(): void
    {
        $this->assertTrue(MaintenanceService::isTransitionAllowed('reported','scheduled'));
        $this->assertTrue(MaintenanceService::isTransitionAllowed('scheduled','in_progress'));
        $this->assertTrue(MaintenanceService::isTransitionAllowed('in_progress','completed'));
        $this->assertTrue(MaintenanceService::isTransitionAllowed('reported','cancelled'));
    }

    public function testTerminalMaintenanceStatesCannotBeReopened(): void
    {
        $this->assertFalse(MaintenanceService::isTransitionAllowed('completed','in_progress'));
        $this->assertFalse(MaintenanceService::isTransitionAllowed('cancelled','reported'));
    }
}
