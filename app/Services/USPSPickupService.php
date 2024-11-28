<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class USPSPickupService
{
    protected $apiUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->apiUrl = config('services.usps.api_url', 'https://api-cat.usps.com');
        $this->accessToken = config('services.usps.access_token');
    }

    public function createPickup($data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/pickup/v3/carrier-pickup', [
                'pickupDate' => $data['pickupDate'],
                'pickupAddress' => [
                    'firstName' => $data['firstName'],
                    'lastName' => $data['lastName'],
                    'firm' => $data['firm'],
                    'address' => [
                        'streetAddress' => $data['streetAddress'],
                        'secondaryAddress' => $data['secondaryAddress'] ?? '',
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'ZIPCode' => $data['zipCode']
                    ],
                    'contact' => [
                        ['email' => $data['email']]
                    ]
                ],
                'packages' => [
                    [
                        'packageType' => $data['packageType'],
                        'packageCount' => $data['packageCount']
                    ]
                ],
                'estimatedWeight' => $data['weight'],
                'pickupLocation' => [
                    'packageLocation' => $data['location'],
                    'specialInstructions' => $data['instructions'] ?? ''
                ]
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('USPS Pickup Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
