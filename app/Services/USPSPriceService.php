<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class USPSPriceService
{
    protected $apiUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->apiUrl = config('services.usps.api_url');
        $this->accessToken = config('services.usps.access_token');
    }

    public function getBaseRates($data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/prices/v3/base-rates/search', [
                'originZIPCode' => $data['originZIPCode'],
                'destinationZIPCode' => $data['destinationZIPCode'],
                'weight' => $data['weight'],
                'length' => $data['length'],
                'width' => $data['width'],
                'height' => $data['height'],
                'mailClass' => $data['mailClass'],
                'processingCategory' => $data['processingCategory'],
                'destinationEntryFacilityType' => $data['destinationEntryFacilityType'] ?? 'NONE',
                'rateIndicator' => $data['rateIndicator'],
                'priceType' => $data['priceType'],
                'accountType' => $data['accountType'],
                'accountNumber' => $data['accountNumber'],
                'mailingDate' => $data['mailingDate']
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('USPS Price API Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
