<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
       User::create([
        "id"=> 1,
        "full_name"=> "Maria",
        "email"=> "mariapicio4@gmail.com",
        "password"=> Hash::make("12345678"),
        "role"=> "ADMIN",
        "status"=> "active",
        "level" => "5",
        "level_name" => "Professional",
       ]);
        $this->call(QuestionnaireSeeder::class);
    }
}
