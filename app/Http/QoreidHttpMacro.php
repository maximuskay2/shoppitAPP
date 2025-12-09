<?php

namespace App\Http;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QoreidHttpMacro
{
    /**
     * Make an API call using Laravel's HTTP client.
     *
     * @param string $url    The URL for the API endpoint.
     * @param string $method The HTTP method (GET, POST, PUT, etc.).
     * @param array  $data   The data to send in the request body.
     *
     * @return array|mixed
     */
    public static function makeApiCall(string $url, string $base_url, string $method = 'GET', array $data = [], array $params = [])
    {
        try {
            $accessTokens = self::generateAccessToken($base_url);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessTokens['accessToken'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->{$method}($url, $data);
            
            if ($response->failed()) {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseHeaders = $response->headers();
                
                throw new Exception("Qoreid API request failed with status code $statusCode. Response body: $responseBody, Headers: " . json_encode($responseHeaders));
            }
    
            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private static function generateAccessToken (string $base_url) 
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->{'POST'}($base_url . '/token', [
                'clientId' => config('services.qoreid.mode') === 'live' ? config('services.qoreid.client_id') : config('services.qoreid.test_client_id'),
                'secret' => config('services.qoreid.mode') === 'live' ? config('services.qoreid.secret_key') : config('services.qoreid.test_secret_key'),
            ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $responseBody = $response->body();
                $responseHeaders = $response->headers();

                throw new Exception("Qoreid API request failed with status code $statusCode. Response body: $responseBody, Headers: " . json_encode($responseHeaders));
            }
            return $response->json();
        } catch (Exception $e) {
            throw $e;
        }
    }
}

