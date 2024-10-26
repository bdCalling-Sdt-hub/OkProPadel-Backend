<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       User::create([
        "id"=> 1,
        "full_name"=> "Admin",
        // "user_name"=> "admin",
        "email"=> "admin@gmail.com",
        "password"=> Hash::make("12345678"),
        "role"=> "ADMIN",
        "status"=> "active",
        "level" => "5",
        "level_name" => "Professonal",
       ]);

      // Creating users for testing
    $users = [
            [
                "full_name" => "user",
                // "user_name" => "user",
                "email" => "user@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 1",
                // "user_name" => "user_1",
                "email" => "user1@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 2",
                // "user_name" => "user_2",
                "email" => "user2@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 3",
                // "user_name" => "user_3",
                "email" => "user3@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 4",
                // "user_name" => "user_4",
                "email" => "user4@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 5",
                // "user_name" => "user_5",
                "email" => "user5@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 6",
                // "user_name" => "user_6",
                "email" => "user6@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 7",
                // "user_name" => "user_7",
                "email" => "user7@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 8",
                // "user_name" => "user_8",
                "email" => "user8@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
            [
                "full_name" => "user 9",
                // "user_name" => "user_9",
                "email" => "user9@gmail.com",
                "password" => Hash::make("12345678"),
                "role" => "MEMBER",
                "status" => "active",
                "level" => "4",
                "level_name" => "Advanced",
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
        $this->call(QuestionnaireSeeder::class);
    }
}
