<?php

namespace App\Commands;

class SeedAllCommand extends SeedCommand
{
    protected $name = 'seed';
    protected $description = 'Seeds pending BantayGamit demo data; defaults to the full seeder sequence.';
    protected $usage = 'seed [seeder_name] [--no-ansi]';
}
