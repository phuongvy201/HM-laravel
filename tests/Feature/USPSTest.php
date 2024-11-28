<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\USPSService;
use Illuminate\Support\Facades\Http;

class USPSApiTest extends TestCase
{
    protected $uspsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uspsService = app(USPSService::class);
    }

    /** @test */
    public function can_verify_address()
    {
        $address = [
            'street1' => '1234 Main St',
            'city' => 'Anytown',
            'state' => 'NY',
            'zip5' => '12345'
        ];

        $response = $this->uspsService->verifyAddress($address);
        
        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('address', $response->json());
    }

    /** @test */
    public function can_calculate_shipping_rates()
    {
        $package = [
            'service' => 'PRIORITY',
            'weight' => '2',
            'from_zip' => '12345',
            'to_zip' => '54321'
        ];

        $response = $this->uspsService->getShippingRates($package);
        
        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('rate', $response->json());
    }

    /** @test */
    public function handles_invalid_address()
    {
        $address = [
            'street1' => 'Invalid Address',
            'city' => 'Nowhere',
            'state' => 'XX',
            'zip5' => '00000'
        ];

        $response = $this->uspsService->verifyAddress($address);
        
        $this->assertFalse($response->successful());
        $this->assertEquals(400, $response->status());
    }
}
