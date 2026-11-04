<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $baseUrl = Setting::getBaseUrl();
        
        return response()->json([
            'meta' => [
                'status' => 'success',
                'message' => 'Settings retrieved successfully'
            ],
            'data' => [
                'base_url' => $baseUrl
            ]
        ]);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'base_url' => 'required|url'
        ]);
        
        Setting::setBaseUrl($request->base_url);
        
        return response()->json([
            'meta' => [
                'status' => 'success',
                'message' => 'BASE_URL updated successfully'
            ],
            'data' => [
                'base_url' => $request->base_url
            ]
        ]);
    }
}
