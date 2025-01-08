<?php

namespace App\Http\Controllers;

use App\Models\Locations;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LocationsController extends Controller
{
    public function addLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'location_url' => 'required | url'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }
        $location = Locations::create([
            'user_id' => Auth::id(),
            'location_name' => $request->location_name,
            'location_url' => $request->location_url
        ]);

        return ResponseFormatter::success('the location add successfully',$location,200);

    }

    public function getUserLocations()
    {
        $location = Locations::query()->where('user_id',Auth::id())->get();
        return ResponseFormatter::success('get user location successfully',$location,200);
    }

    public function getLastUsedLocation()
    {
        $lastOrder = Order::query()->where('user_id', Auth::id())->latest()->first();

        if (!$lastOrder || !$lastOrder->location_id) {
            return ResponseFormatter::error('the last used location not found',null,404);
        }

        $location = Locations::query()->find($lastOrder->location_id);

        return ResponseFormatter::success('get last used location successfully',$location,200);
    }


    public function getDefaultUserLocation()
    {
        $lastUsedLocationResponse = $this->getLastUsedLocation();

        // Check if the last used location was found
        if ($lastUsedLocationResponse->getStatusCode() !== 200) {
            $firstUserLocation = Locations::query()
                ->where('user_id', Auth::id())
                ->first();

            if (!$firstUserLocation) {
                return ResponseFormatter::error('No locations found for this user', null, 404);
            }

            return ResponseFormatter::success('Get default user location successfully', $firstUserLocation, 200);
        }

        // Return the last used location if found
        return $lastUsedLocationResponse;
    }

    public function deleteLocation($id)
    {
        $location=Locations::query()->find($id);
        $location->delete();
        return ResponseFormatter::success('deleted location successfully',$location,200);

    }


}
