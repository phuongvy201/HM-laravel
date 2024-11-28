<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class USPSServices
{
    protected $client;
    protected $apiUrl;
    protected $userId;
    protected $accessToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('services.usps.api_url', 'https://api-cat.usps.com'); // Sandbox URL
        $this->userId = config('services.usps.user_id');
        $this->accessToken = config('services.usps.access_token');
    }

    // 1. Xác thực địa chỉ
    public function validateAddress($address)
    {
        try {
            $response = $this->client->get($this->apiUrl . '/addresses/v3/address', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-user-id' => $this->userId,
                    'authorization' => 'Bearer ' . $this->accessToken
                ],
                'query' => [
                    'streetAddress' => $address['street'],
                    'secondaryAddress' => $address['secondary'] ?? '',
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'ZIPCode' => $address['zip'],
                    'ZIPPlus4' => $address['zipPlus4'] ?? ''
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('USPS Address Validation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // 2. Tính giá vận chuyển
    public function calculateShipping($package)
    {
        try {
            $response = $this->client->get($this->apiUrl . '/rates/v3/domestic', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-user-id' => $this->userId,
                    'authorization' => 'Bearer ' . $this->accessToken
                ],
                'query' => [
                    'originZIPCode' => $package['origin_zip'],
                    'destinationZIPCode' => $package['destination_zip'],
                    'weightInOunces' => $package['weight'] * 16,
                    'serviceType' => $package['service_type'] ?? 'PRIORITY'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('USPS Rate Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // 3. Tạo pickup request
    public function createPickup($pickupDetails)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/pickup/v3/schedule', [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'x-user-id' => $this->userId,
                    'authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => [
                    'pickupDate' => $pickupDetails['pickup_date'],
                    'pickupLocation' => $pickupDetails['location'] ?? 'FRONT_DOOR',
                    'contactDetails' => [
                        'firstName' => $pickupDetails['first_name'],
                        'lastName' => $pickupDetails['last_name'],
                        'phone' => $pickupDetails['phone'],
                        'email' => $pickupDetails['email'] ?? null
                    ],
                    'address' => [
                        'streetAddress' => $pickupDetails['address'],
                        'secondaryAddress' => $pickupDetails['suite'] ?? '',
                        'city' => $pickupDetails['city'],
                        'state' => $pickupDetails['state'],
                        'ZIPCode' => $pickupDetails['zip']
                    ],
                    'packages' => [
                        [
                            'serviceType' => $pickupDetails['service_type'],
                            'count' => $pickupDetails['package_count']
                        ]
                    ],
                    'specialInstructions' => $pickupDetails['instructions'] ?? ''
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('USPS Pickup Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
