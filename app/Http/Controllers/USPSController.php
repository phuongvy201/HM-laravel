<?php

namespace App\Http\Controllers;

use App\Services\USPSService;
use App\Services\USPSServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class USPSController extends Controller
{
    protected $uspsService;

    public function __construct(USPSServices $uspsService)
    {
        $this->uspsService = $uspsService;
    }

    public function validateAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|size:2',
            'zip' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->uspsService->validateAddress($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
