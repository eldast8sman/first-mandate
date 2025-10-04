<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class FlutterwaveService
{
    private $app_token;
    private $client_id;
    private $client_secret;
    private $base_url;
    private $token_url;

    public function __construct()
    {
        $environment = config('services.flutterwave.environment', 'DEVELOPMENT');
        
        if ($environment === 'DEVELOPMENT') {
            $this->client_id = config('services.flutterwave.dev.client_id');
            $this->client_secret = config('services.flutterwave.dev.client_secret');
            $this->base_url = config('services.flutterwave.dev.base_url');
        } else {
            $this->client_id = config('services.flutterwave.prod.client_id');
            $this->client_secret = config('services.flutterwave.prod.client_secret');
            $this->base_url = config('services.flutterwave.prod.base_url');
        }

        $this->token_url = 'https://idp.flutterwave.com/realms/flutterwave/protocol/openid-connect/token';

        // Cache token for 8 minutes (480 seconds) to allow for refresh before expiry
        // JWT tokens typically expire after 24 hours, but we refresh earlier for safety
        $this->app_token = Cache::remember('flutterwave_token', 480, function() {
            return $this->getToken();
        });
    }

    /**
     * Get OAuth2 token from Flutterwave
     * 
     * @return string|false
     */
    public function getToken()
    {
        try {
            $response = Http::asForm()
                ->post($this->token_url, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret
                ]);

            if ($response->failed()) {
                Log::error('Flutterwave Token Request Failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'client_id' => $this->client_id
                ]);
                return false;
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::error('Flutterwave Token Response Missing access_token', [
                    'response' => $data
                ]);
                return false;
            }

            Log::info('Flutterwave token retrieved successfully');
            
            return $data['access_token'];

        } catch (Exception $e) {
            Log::error('Flutterwave Token Request Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the current app token
     * 
     * @return string|false
     */
    public function getAppToken()
    {
        return $this->app_token;
    }

    /**
     * Force refresh the token (clears cache and gets new token)
     * 
     * @return string|false
     */
    public function refreshToken()
    {
        Cache::forget('flutterwave_token');
        $this->app_token = $this->getToken();
        
        if ($this->app_token) {
            Cache::put('flutterwave_token', $this->app_token, 480);
        }
        
        return $this->app_token;
    }

    /**
     * Make authenticated request to Flutterwave API
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response|false
     */
    public function makeRequest($method = 'GET', $endpoint = '', $data = [])
    {
        if (!$this->app_token) {
            Log::error('No valid Flutterwave token available');
            return false;
        }

        try {
            $url = rtrim($this->base_url, '/') . '/' . ltrim($endpoint, '/');
            
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->app_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            switch (strtoupper($method)) {
                case 'GET':
                    $response = $httpClient->get($url, $data);
                    break;
                case 'POST':
                    $response = $httpClient->post($url, $data);
                    break;
                case 'PUT':
                    $response = $httpClient->put($url, $data);
                    break;
                case 'DELETE':
                    $response = $httpClient->delete($url, $data);
                    break;
                default:
                    Log::error('Unsupported HTTP method: ' . $method);
                    return false;
            }

            if ($response->failed()) {
                Log::error('Flutterwave API Request Failed', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            $responseData = $response->json();
            if(isset($responseData['status']) && $responseData['status'] !== 'success') {
                Log::error('Flutterwave API Request Error', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'error' => $responseData['message'] ?? 'Unknown error'
                ]);
                return false;
            }

            return $responseData['data'] ?? $responseData;

        } catch (Exception $e) {
            Log::error('Flutterwave API Request Exception', [
                'endpoint' => $endpoint,
                'method' => $method,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get list of banks for a specific country
     * 
     * @param string $country Country code (default: 'NG' for Nigeria)
     * @return array|false
     */
    public function get_banks($country = 'NG')
    {
        $response = $this->makeRequest('GET', 'banks', ['country' => $country]);

        if (!$response) {
            return false;
        }

        return $response;
    }

    public function create_customer(array $data){
        $response = $this->makeRequest('POST', 'customers', $data);

        if (!$response) {
            return false;
        }

        return $response;
    }
}