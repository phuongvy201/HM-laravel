<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;

class USPSException extends \Exception
{
    public static function fromResponse($response)
    {
        Log::error('USPS API Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return new static($response->json()['error'] ?? 'Unknown USPS API error');
    }
}
