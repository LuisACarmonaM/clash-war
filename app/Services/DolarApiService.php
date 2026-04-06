<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DolarApiService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://ve.dolarapi.com/', // ← Asegúrate que termine con slash
            'timeout' => 10,
            'verify' => false,
        ]);
    }

    public function getTasas()
    {
        try {
            // Ahora usa 'v1/tasas' sin el slash inicial
            $response = $this->client->get('v1/dolares/oficial');

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }

            Log::error('DolarAPI responded with status: ' . $response->getStatusCode());
            return null;
        } catch (\Exception $e) {
            Log::error('Error DolarAPI tasas: ' . $e->getMessage());
            return null;
        }
    }

    public function getEstado()
    {
        try {
            $response = $this->client->get('v1/estado');

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error DolarAPI estado: ' . $e->getMessage());
            return null;
        }
    }
}
