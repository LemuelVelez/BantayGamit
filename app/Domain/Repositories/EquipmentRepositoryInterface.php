<?php

namespace App\Domain\Repositories;

interface EquipmentRepositoryInterface
{
    public function findUserByUsername(string $username): ?array;
    public function findUser(int $id): ?array;
    public function equipment(array $filters = []): array;
    public function findEquipment(int $id): ?array;
    public function equipmentOptions(): array;
    public function categories(bool $activeOnly = false): array;
    public function locations(bool $activeOnly = false): array;
    public function availableQuantity(int $equipmentId, ?int $excludeRequestId = null): int;
    public function borrowRequests(string $role, int $userId, ?string $status = null): array;
    public function findBorrowRequest(int $id): ?array;
    public function borrowRequestItems(int $requestId): array;
    public function notifications(int $userId, int $limit = 50): array;
    public function dashboard(string $role, int $userId): array;
}
