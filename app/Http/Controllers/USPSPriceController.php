<?php

namespace App\Http\Controllers;

use App\Services\USPSPriceService;
use Illuminate\Http\Request;

class USPSPriceController extends Controller
{
    protected $priceService;

    public function __construct(USPSPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function getBaseRates(Request $request)
    {
        $validated = $request->validate([
            'originZIPCode' => 'required|string',
            'destinationZIPCode' => 'required|string',
            'weight' => 'required|numeric',
            'length' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'mailClass' => 'required|string',
            'processingCategory' => 'required|string',
            'rateIndicator' => 'required|string',
            'priceType' => 'required|string',
            'accountType' => 'required|string',
            'accountNumber' => 'required|string',
            'mailingDate' => 'required|date'
        ]);

        try {
            $result = $this->priceService->getBaseRates($validated);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get rates',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
