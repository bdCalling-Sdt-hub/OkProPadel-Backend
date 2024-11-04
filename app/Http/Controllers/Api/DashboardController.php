<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\PadelMatch;
use App\Models\PadelMatchMemberHistory;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::where('role','MEMBER')->count();
        $totalVolunteers = Volunteer::count();
        $totalClubs = Club::count();
        $createdCommunity = PadelMatch::count();
        $padelMatchPlayed = PadelMatchMemberHistory::count() / 4;
        $data = [
            'total_users' => $totalUsers ?? 0,
            'total_volunteers' => $totalVolunteers ?? 0,
            'total_clubs' => $totalClubs ?? 0,
            'createdCommunity' => $createdCommunity ?? 0,
            'padelMatchPlayed' => $padelMatchPlayed ?? 0
        ];
        return $this->sendResponse($data, 'Dashboard statistics retrieved successfully.');
    }
    public function dashboardGraphData()
    {
        $createdCommunityMonthly = PadelMatch::where('created_at', '>=', now()->startOfMonth())->count();
        $createdCommunityWeekly = PadelMatch::where('created_at', '>=', now()->startOfWeek())->count();
        $createdCommunityYearly = PadelMatch::where('created_at', '>=', now()->startOfYear())->count();

        $padelMatchPlayedMonthly = PadelMatchMemberHistory::where('created_at', '>=', now()->startOfMonth())->count() / 4;
        $padelMatchPlayedWeekly = PadelMatchMemberHistory::where('created_at', '>=', now()->startOfWeek())->count() / 4;
        $padelMatchPlayedYearly = PadelMatchMemberHistory::where('created_at', '>=', now()->startOfYear())->count() / 4;
        $data = [
            'labels' => ['Weekly', 'Monthly', 'Yearly'],
            'created_community' => [
                $createdCommunityWeekly,
                $createdCommunityMonthly,
                $createdCommunityYearly,
            ],
            'padel_match_played' => [
                $padelMatchPlayedWeekly,
                $padelMatchPlayedMonthly,
                $padelMatchPlayedYearly,
            ],
        ];

        return $this->sendResponse($data, 'Graph data retrieved successfully.');
    }
}
