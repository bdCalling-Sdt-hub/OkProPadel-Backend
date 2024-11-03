<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve all active clubs, paginated (18 per page)
        $clubs = Club::where('status', true)
            ->orderBy('id', 'desc')
            ->paginate(18);

        // Check if clubs exist
        if ($clubs->isEmpty()) {
            return $this->sendError('No Clubs Found.', []);
        }

        // Format the clubs data
        $formattedClubs = $clubs->map(function ($club) {
            $banners = collect(json_decode($club->banners, true) ?? [])
            ->map(fn($banner) => url('uploads/banners/' . $banner))
            ->toArray();
            return [
                'id'         => $club->id,
                'club_name'  => $club->club_name,
                'description'=> $club->description,
                'location'   => $club->location ?? '',
                'latitude'   => $club->latitude,
                'longitude'  => $club->longitude,
                'website'    => $club->website,
                'status'     => $club->status,
                'banners'    => $banners ? $banners : url('avatar','profile.jpg'),
                'activities' => json_decode($club->activities) ?? [],
                'created_at' => $club->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $club->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        // Prepare paginated response with formatted data
        $data = [
            'clubs' => $formattedClubs,
            'pagination' => [
                'total'       => $clubs->total(),
                'per_page'    => $clubs->perPage(),
                'current_page'=> $clubs->currentPage(),
                'last_page'   => $clubs->lastPage(),
                'from'        => $clubs->firstItem(),
                'to'          => $clubs->lastItem(),
            ],
        ];

        return $this->sendResponse($data, "Clubs retrieved successfully.");
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'banners'    => 'required|array',
            // 'banners.*'  => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'description'=> 'nullable|string|max:255',
            'activities' => 'nullable|array',
            'club_name'  => 'required|string|unique:clubs,club_name',
            'latitude'   => 'required|string',
            'longitude'  => 'required|string',
            'website'    => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }
        if ($request->hasFile('banners')) {
            $imagePaths = [];
            foreach ($request->file('banners') as $banner) {
                $imageName = time() . '_' . $banner->getClientOriginalName();
                $banner->move(public_path('uploads/banners'), $imageName);
                $imagePaths[] = $imageName;
            }
        }
        $activities = $request->activities;
        $client = new Client();
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $location = $this->getLocationFromCoordinates($client, $request->latitude, $request->longitude, $apiKey);
        try {
            $club = Club::create([
                'banners' => json_encode($imagePaths) ?? null,
                'description'=> $request->description ?? null,
                'activities' =>json_encode($activities) ?? null,
                'club_name' => $request->club_name,
                'location' => $location,
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
                'website' => $request->website,
                'status' => true,
            ]);
            return $this->sendResponse($club, "Club Created Successfully.");
        } catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), 500);
        }
    }
    private function getLocationFromCoordinates($client, $latitude, $longitude, $apiKey)
    {
        try {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0]['formatted_address'];
            } else {
                return 'Location not found';
            }
        } catch (RequestException $e) {
            return 'Error retrieving location: ' . $e->getMessage();
        }
    }

    public function update(Request $request, $id)
    {
        // return $request;
        $club = Club::find($id);
        if (!$club) {
            return $this->sendError('Club not found.', [], 404);
        }
        $validator = Validator::make($request->all(), [
            // 'banners'    => 'nullable|array',
            // 'banners.*'  => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'description'=> 'nullable|string|max:255',
            'activities' => 'nullable|array',
            'club_name'  => 'required|string|unique:clubs,club_name,' . $id, // Exclude current club's ID
            'latitude'   => 'required|string',
            'longitude'  => 'required|string',
            'website'    => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }
        if ($request->hasFile('banners')) {
            $this->deleteOldBanners(json_decode($club->banners, true));
            $imagePaths = [];
            foreach ($request->file('banners') as $banner) {
                $imageName = time() . '_' . $banner->getClientOriginalName();
                $banner->move(public_path('uploads/banners'), $imageName);
                $imagePaths[] = $imageName;
            }
            $club->banners = json_encode($imagePaths) ?? $club->banners;
        }
        $activities = $request->activities;
        $client = new Client();
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $location = $this->getLocationFromCoordinates($client, $request->latitude, $request->longitude, $apiKey);

        try {
            $club->update([
                'description' => $request->description ?? $club->description,
                'activities'  => json_encode($activities) ?? $club->activities,
                'club_name'   => $request->club_name ?? $club->club_name,
                'location'    => $location ?? $club->location,
                'latitude'    => $request->latitude ?? $club->latitude,
                'longitude'   => $request->longitude ?? $club->longitude,
                'website'     => $request->website ?? $club->website,
                'status'      => $club->status,
            ]);
            return $this->sendResponse($club, 'Club updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), [], 500);
        }
    }
    private function deleteOldBanners(?array $banners)
    {
        if ($banners) {
            foreach ($banners as $banner) {
                $bannerPath = public_path($banner);
                if (file_exists($bannerPath)) {
                    unlink($bannerPath);
                }
            }
        }
    }

    public function delete($id)
    {
        try {
            $club = Club::find($id);
            if (!$club) {
                return $this->sendError('Club not found.', [], 404);
            }
            if ($club->banners) {
                $banners = json_decode($club->banners, true);

                foreach ($banners as $banner) {
                    $bannerPath = public_path($banner);
                    if (file_exists($bannerPath)) {
                        unlink($bannerPath);
                    }
                }
            }
            $club->delete();
            return $this->sendResponse([], 'Club deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while deleting the club.', ['error' => $e->getMessage()], 500);
        }
    }
}
