<?php

namespace App\Http\Controllers;

use App\Services\USPSPickupService;
use Illuminate\Http\Request;

class USPSPickupController extends Controller
{
    protected $pickupService;

    public function __construct(USPSPickupService $pickupService)
    {
        $this->pickupService = $pickupService;
    }

    public function createPickup(Request $request)
    {
        $validated = $request->validate([
            'pickupDate' => 'required|date',
            'firstName' => 'required',
            'lastName' => 'required',
            'firm' => 'required',
            'streetAddress' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipCode' => 'required',
            'email' => 'required|email',
            'packageType' => 'required',
            'packageCount' => 'required|integer',
            'weight' => 'required|numeric',
            'location' => 'required'
        ]);

        try {
            $result = $this->pickupService->createPickup($validated);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create pickup',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
