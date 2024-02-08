<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Genera dos usuarios de prueba
        User::factory()->count(2)->create();
    }
}
