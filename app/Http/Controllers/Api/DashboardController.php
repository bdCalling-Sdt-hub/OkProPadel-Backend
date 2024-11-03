<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
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
        $data = [
            'total_users' => $totalUsers ?? 0,
            'total_volunteers' => $totalVolunteers ?? 0,
            'total_clubs' => $totalClubs ?? 0,
        ];
        return $this->sendResponse($data, 'Dashboard statistics retrieved successfully.');
    }
}
