<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Module::create(['name' => 'User Management', 'description' => 'Manage users/roles']);
        Module::create(['name' => 'Inventory', 'description' => 'Stock handling']);
        Module::create(['name' => 'Reports', 'description' => 'View system reports']);
    }
}
