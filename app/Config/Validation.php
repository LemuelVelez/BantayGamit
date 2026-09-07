<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    public array $templates = [
        'list'   => 'CodeIgniter\\Validation\\Views\\list',
        'single' => 'CodeIgniter\\Validation\\Views\\single',
    ];

    public array $login = [
        'username' => 'required|min_length[3]|max_length[80]',
        'password' => 'required|min_length[6]|max_length[255]',
    ];

    public array $equipment = [
        'asset_code' => 'required|max_length[40]',
        'name' => 'required|max_length[150]',
        'category_id' => 'required|is_natural_no_zero',
        'location_id' => 'required|is_natural_no_zero',
        'total_quantity' => 'required|is_natural',
        'unit' => 'required|max_length[40]',
        'condition' => 'required|in_list[excellent,good,fair,damaged]',
        'status' => 'required|in_list[available,unavailable,maintenance]',
        'acquired_date' => 'permit_empty|valid_date[Y-m-d]',
        'description' => 'permit_empty|max_length[5000]',
        'notes' => 'permit_empty|max_length[5000]',
    ];

    public array $equipmentCategory = [
        'name' => 'required|max_length[120]',
        'description' => 'permit_empty|max_length[2000]',
        'status' => 'permit_empty|in_list[active,inactive]',
    ];

    public array $equipmentLocation = [
        'name' => 'required|max_length[150]',
        'description' => 'permit_empty|max_length[2000]',
        'status' => 'permit_empty|in_list[active,inactive]',
    ];

    public array $userCreate = [
        'username' => 'required|min_length[3]|max_length[80]',
        'display_name' => 'required|max_length[120]',
        'email' => 'permit_empty|valid_email|max_length[190]',
        'contact_number' => 'permit_empty|max_length[40]',
        'address' => 'permit_empty|max_length[5000]',
        'password' => 'required|min_length[8]|max_length[255]',
        'role' => 'required|in_list[admin,barangay_official,borrower]',
        'status' => 'required|in_list[active,inactive]',
    ];

    public array $userUpdate = [
        'display_name' => 'required|max_length[120]',
        'email' => 'permit_empty|valid_email|max_length[190]',
        'contact_number' => 'permit_empty|max_length[40]',
        'address' => 'permit_empty|max_length[5000]',
        'role' => 'required|in_list[admin,barangay_official,borrower]',
        'status' => 'required|in_list[active,inactive]',
        'password' => 'permit_empty|min_length[8]|max_length[255]',
    ];

    public array $borrowRequest = [
        'purpose' => 'required|max_length[2000]',
        'requested_date' => 'required|valid_date[Y-m-d]',
        'expected_return_date' => 'required|valid_date[Y-m-d]',
        'notes' => 'permit_empty|max_length[5000]',
    ];

    public array $borrowReject = [
        'rejection_reason' => 'required|max_length[2000]',
    ];

    public array $maintenance = [
        'equipment_id' => 'required|is_natural_no_zero',
        'maintenance_type' => 'required|max_length[100]',
        'description' => 'required|max_length[3000]',
        'quantity' => 'required|is_natural_no_zero',
        'status' => 'required|in_list[reported,scheduled,in_progress]',
        'start_date' => 'permit_empty|valid_date[Y-m-d]',
        'cost' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'notes' => 'permit_empty|max_length[5000]',
    ];

    public array $profile = [
        'display_name' => 'required|max_length[120]',
        'email' => 'permit_empty|valid_email|max_length[190]',
        'contact_number' => 'permit_empty|max_length[40]',
        'address' => 'permit_empty|max_length[5000]',
        'password' => 'permit_empty|min_length[8]|max_length[255]',
    ];

    public array $settings = [
        'barangay_name' => 'required|max_length[160]',
        'due_soon_days' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[30]',
    ];
}
