<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{
    public function updateVolunteerRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "role" => "required|string",
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Errors", $validator->errors());
        }
        $volunteer = Volunteer::find($id);
        if (!$volunteer) {
            return $this->sendError("Volunteer not found.");
        }
        $roleLevels = [
            'Beginner' => 1,
            'Lower-Intermediate' => 2,
            'Upper-Intermediate' => 3,
            'Advanced' => 4,
            'Professional' => 5,
        ];
        if (!array_key_exists($request->role, $roleLevels)) {
            return $this->sendError("Invalid role provided.");
        }
        $volunteer->role = $request->role;
        $volunteer->level = $roleLevels[$request->role];
        $volunteer->save();
        return $this->sendResponse([], "Role and level successfully updated.");
    }

    public function index(Request $request)
    {
        $volunteers = Volunteer::where('status', true)
            ->orderBy('id', 'desc')
            ->get();

        if ($volunteers->isEmpty()) {
            return $this->sendError('No volunteers found.');
        }
        $formattedVolunteers = $volunteers->map(function ($volunteer) {
            return [
                'id' => $volunteer->id,
                'name' => $volunteer->name,
                'email' => $volunteer->email,
                'location' => $volunteer->location,
                'level' => $volunteer->level,
                'role' => $volunteer->role,
                'phone_number' => $volunteer->phone_number ?? 'N/A',
                'status' => $volunteer->status,
                'image' => $volunteer->image ? url('uploads/volunteers/'. $volunteer->image) : url('avatar','profile.jpg')
            ];
        });
        return $this->sendResponse([
            'volunteers' => $formattedVolunteers,
            'total' => $volunteers->count(),
        ], 'All volunteers retrieved successfully.');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:volunteers,email',
            'location' => 'required|string|max:255',
            'level' => 'required|in:1,2,3,4,5',
            'role' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'status' => 'boolean',
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error:", $validator->errors());
        }
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = time() . '.' . $image->getClientOriginalName();
            $image->move(public_path('uploads/volunteers/'), $imagePath);
        }
        $volunteer = Volunteer::create([
            'name' => $request->name,
            'email' => $request->email,
            'location' => $request->location,
            'level' => $request->level,
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'image' => $imagePath,
            'status' => true,
        ]);
        return $this->sendResponse($volunteer, "Volunteer created successfully.");
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:volunteers,email,' . $id,
            'location' => 'sometimes|required|string|max:255',
            'level' => 'sometimes|required|in:1,2,3,4,5',
            'role' => 'sometimes|required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'status' => 'boolean',
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error:", $validator->errors());
        }
        $volunteer = Volunteer::findOrFail($id);
        if(!$volunteer){
            return $this->sendError("Not found volunteer.");
        }
        if ($request->hasFile('image')) {
            if ($volunteer->image) {
                $oldImagePath = public_path($volunteer->image);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }
            $image = $request->file('image');
            $imagePath = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/volunteers/'), $imagePath);
        }
        $volunteer->name = $request->name ?? $volunteer->name;
        $volunteer->email = $request->email ?? $volunteer->email;
        $volunteer->location = $request->location ?? $volunteer->location;
        $volunteer->level = $request->level ?? $volunteer->level;
        $volunteer->role = $request->role ?? $volunteer->role;
        $volunteer->image = $imagePath ?? $volunteer->image;
        $volunteer->status = $request->status ?? $volunteer->status;
        $volunteer->save();
        return $this->sendResponse($volunteer, "Volunteer updated successfully.");
    }
    public function delete($id)
    {
        $volunteer = Volunteer::findOrFail($id);
        if ($volunteer->image) {
            $oldImagePath = public_path($volunteer->image);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }
        }
        $volunteer->delete();
        return $this->sendResponse(null, "Volunteer deleted successfully.");
    }
}
