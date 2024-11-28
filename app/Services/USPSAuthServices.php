<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class USPSAuthService
{
    protected $consumerKey;
    protected $consumerSecret;

    public function __construct()
    {
        $this->consumerKey = config('services.usps.consumer_key');
        $this->consumerSecret = config('services.usps.consumer_secret');
    }

    public function getAuthToken()
    {
        // Tạo base64 encoded string từ consumer key và secret
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        // Gọi API để lấy token
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->post('https://api.usps.com/oauth2/token', [
            'grant_type' => 'client_credentials'
        ]);

        return $response->json()['access_token'];
    }
}
