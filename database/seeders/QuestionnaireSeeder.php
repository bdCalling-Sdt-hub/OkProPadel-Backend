<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\Questionnaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $questionsData = [
            [
                'question' => 'How long have you been regularly playing padel?',
                'type' => 'vertical',
                'options' => json_encode([
                    'It will be my first time',
                    'Couple of months',
                    'Between 1 and 4 years',
                    'More than 4 years',
                ]),
            ],
            [
                'question' => 'How often do you play matches or train padel?',
                'type' => 'vertical',
                'options' => json_encode([
                    'Less than once a week',
                    '1-2 times a week',
                    '3-4 times a week',
                    'More than 4 times a week',
                ]),
            ],
            [
                'question' => 'How confident are you with basic shots (forehand and backhand)?',
                'type' => 'vertical',
                'options' => json_encode([
                    'I still struggle with controlling direction and power',
                    '1-2 times a week',
                    '3-4 times a week',
                    'More than 4 times a week',
                ]),
            ],
            [
                'question' => 'How comfortable are you with advanced shots (bandeja, volley, smash)?',
                'type' => 'vertical',
                'options' => json_encode([
                    'It will be my first time',
                    'Couple of months',
                    'Between 1 and 4 years',
                    'More than 4 years',
                ]),
            ],
            [
                'question' => 'Regarding your movement on the court, you would say:',
                'type' => 'vertical',
                'options' => json_encode([
                    'Less than once a week',
                    '1-2 times a week',
                    '3-4 times a week',
                    'More than 4 times a week',
                ]),
            ],
            [
                'question' => 'How evenly matched were the players in the following aspects (scale from 1 to 5)?',
                'type' => 'horizontal',
                'options' => json_encode([
                    'Technique (quality of strokes, ball placement)',
                    'Placement (accuracy of ball positioning)',
                ]),
            ],
            [
                'question' => 'When playing at the net, you feel:',
                'type' => 'vertical',
                'options' => json_encode([
                    'I still struggle with controlling direction and power',
                    '1-2 times a week',
                    '3-4 times a week',
                    'More than 4 times a week',
                ]),
            ],
            [
                'question' => 'How would you describe your ability to read the game and anticipate your opponent’s movements?',
                'type' => 'vertical',
                'options' => json_encode([
                    'Less than once a week',
                    '1-2 times a week',
                    '3-4 times a week',
                    'More than 4 times a week',
                ]),
            ],
            [
                'question' => 'Write OK, low or high besides each name of players',
                'type' => 'feedback',
                'options' => json_encode([
                    ['image' => 'https://i.pravatar.cc/150?img=1', 'name' => 'John Doe', 'level' => 3],
                    ['image' => 'https://i.pravatar.cc/150?img=2', 'name' => 'Midul Doe', 'level' => 5],
                    ['image' => 'https://i.pravatar.cc/150?img=3', 'name' => 'Arif Doe', 'level' => 1],
                ]),
            ],
        ];
        foreach ($questionsData as $data) {
         $questionsData = Questionnaire::create($data);
        }
        // $userIds = [2,3,4,5];
        // foreach ($userIds as $userId) {
        //     Feedback::create([
        //         'user_id' => $userId,
        //         'questionnaire_id' =>9,
        //         'response' => 'ok',
        //     ]);
        // }
    }
}
