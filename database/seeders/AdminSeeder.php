<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            "id"=> 1,
            "full_name"=> "Admin",
            "email"=> "admin@gmail.com",
            "password"=> Hash::make("12345678"),
            "role"=> "ADMIN",
            "status"=> "active",
            "level" => "5",
            "level_name" => "Professional",
           ]);
    }
}
