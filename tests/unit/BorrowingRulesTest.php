<?php

use App\Application\Services\BorrowingService;
use PHPUnit\Framework\TestCase;

final class BorrowingRulesTest extends TestCase
{
    public function testValidRequestTransitions(): void
    {
        $this->assertTrue(BorrowingService::isTransitionAllowed('pending','approved'));
        $this->assertTrue(BorrowingService::isTransitionAllowed('pending','rejected'));
        $this->assertTrue(BorrowingService::isTransitionAllowed('approved','released'));
        $this->assertTrue(BorrowingService::isTransitionAllowed('released','returned'));
        $this->assertTrue(BorrowingService::isTransitionAllowed('overdue','returned'));
    }

    public function testImpossibleTransitionsAreRejected(): void
    {
        $this->assertFalse(BorrowingService::isTransitionAllowed('pending','returned'));
        $this->assertFalse(BorrowingService::isTransitionAllowed('returned','released'));
        $this->assertFalse(BorrowingService::isTransitionAllowed('rejected','approved'));
        $this->assertFalse(BorrowingService::isTransitionAllowed('approved','cancelled')); // borrower cancellation is pending-only
    }

    public function testOverdueCalculationOnlyAppliesToReleasedLoans(): void
    {
        $this->assertTrue(BorrowingService::isOverdueDate('2026-09-01','released','2026-09-03'));
        $this->assertFalse(BorrowingService::isOverdueDate('2026-09-03','released','2026-09-03'));
        $this->assertFalse(BorrowingService::isOverdueDate('2026-09-01','returned','2026-09-03'));
    }

    public function testReturnQuantityCannotExceedReleasedQuantity(): void
    {
        $this->assertTrue(BorrowingService::validateReturnQuantities(5,2,3));
        $this->assertFalse(BorrowingService::validateReturnQuantities(5,2,4));
        $this->assertFalse(BorrowingService::validateReturnQuantities(5,2,-1));
    }
}
