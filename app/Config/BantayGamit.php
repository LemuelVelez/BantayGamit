<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BantayGamit extends BaseConfig
{
    public array $roles = [
        'admin' => 'Administrator',
        'barangay_official' => 'Barangay Official',
        'borrower' => 'Borrower',
    ];

    public array $equipmentStatuses = [
        'available' => 'Available', 'unavailable' => 'Unavailable',
        'maintenance' => 'Maintenance', 'retired' => 'Retired',
    ];

    public array $conditions = [
        'excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged',
    ];

    public array $requestStatuses = [
        'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
        'cancelled' => 'Cancelled', 'released' => 'Released', 'returned' => 'Returned', 'overdue' => 'Overdue',
    ];

    public array $maintenanceStatuses = [
        'reported' => 'Reported', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress',
        'completed' => 'Completed', 'cancelled' => 'Cancelled',
    ];

    public array $menus = [
        'admin' => [
            ['dashboard','Dashboard','dashboard'], ['equipment','Equipment','dumbbell'], ['equipment-categories','Categories','sliders'],
            ['equipment-locations','Locations','target'], ['borrow-requests','Borrow Requests','clipboard-check'], ['borrowings','Active Borrowings','trophy'],
            ['returns','Returns','calendar-clock'], ['maintenance','Maintenance','settings'], ['users','Users','users'], ['reports','Reports','chart-bar'],
            ['notifications','Notifications','bell'], ['audit-logs','Audit Logs','clipboard-score'], ['settings','Settings','settings'],
        ],
        'barangay_official' => [
            ['dashboard','Dashboard','dashboard'], ['equipment','Equipment','dumbbell'], ['borrow-requests','Borrow Requests','clipboard-check'],
            ['borrowings','Active Borrowings','trophy'], ['returns','Returns','calendar-clock'], ['maintenance','Maintenance','settings'],
            ['reports','Reports','chart-bar'], ['notifications','Notifications','bell'], ['profile','Profile / Settings','settings'],
        ],
        'borrower' => [
            ['dashboard','Dashboard','dashboard'], ['equipment','Browse Equipment','dumbbell'], ['borrow-requests','My Requests','clipboard-check'],
            ['borrowings','Borrowed Equipment','trophy'], ['borrow-history','Borrowing History','calendar'],
            ['notifications','Notifications','bell'], ['profile','Profile / Settings','settings'],
        ],
    ];
}
