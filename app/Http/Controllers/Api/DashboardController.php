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
        $currentYear = now()->year;
        $monthlyData = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            $createdCount = PadelMatch::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->count();

            $playedCount = PadelMatchMemberHistory::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->count() / 4;

            return [
                'month' => now()->month($month)->format('M'),
                'created' => $createdCount,
                'played' => $playedCount,
            ];
        });

        return $this->sendResponse($monthlyData, "Monthly graph data for $currentYear retrieved successfully.");
    }

}
