<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    public function geocode(string $address, ?string $city = null, ?string $state = null, ?string $zipcode = null): ?array
    {
        // 1. Try CEP first — most accurate for Brazilian delivery radius checks
        if ($zipcode) {
            $result = $this->geocodeByCep($zipcode);
            if ($result) return $result;
        }

        // 2. Try full address with Nominatim
        $result = $this->nominatimSearch($address, $city, $state);
        if ($result) return $result;

        // 3. Try just the city center
        if ($city) {
            $result = $this->nominatimSearch($city, $state, 'Brasil');
            if ($result) return $result;
        }

        return null;
    }

    public function geocodeCity(string $city, ?string $state = null): ?array
    {
        // Check local map first
        $local = $this->localCityCoords($city, $state);
        if ($local) return $local;

        // Try Nominatim with city + state
        $result = $this->nominatimSearch($city, $state, 'Brasil');
        if ($result) return $result;

        return null;
    }

    private function nominatimSearch(string ...$parts): ?array
    {
        $parts = array_filter($parts);
        if (empty($parts)) return null;
        $query = implode(', ', $parts);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SaasMesa/1.0 (contato@saasmesa.com)',
                'Accept-Language' => 'pt-BR',
            ])->timeout(5)->get(self::NOMINATIM_URL, [
                'q' => $query,
                'format' => 'json',
                'limit' => 3,
                'addressdetails' => 1,
            ]);

            $data = $response->json();
            if (!empty($data)) {
                foreach ($data as $place) {
                    if (isset($place['lat'], $place['lon'])) {
                        $lat = (float) $place['lat'];
                        $lng = (float) $place['lon'];
                        $type = $place['type'] ?? '';
                        $category = $place['category'] ?? '';

                        // Prefer city/administrative results, but accept any
                        if ($type === 'city' || $type === 'administrative' || empty($result)) {
                            $result = [
                                'lat' => $lat,
                                'lng' => $lng,
                                'display_name' => $place['display_name'] ?? null,
                            ];
                            if ($type === 'city') break;
                        }
                    }
                }
                if (!empty($result)) return $result;
            }
        } catch (\Throwable $e) {
            Log::warning('Nominatim failed: ' . $e->getMessage(), ['query' => $query]);
        }

        return null;
    }

    public function geocodeByCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return null;

        // 1. Try API with coordinates direct (exact CEP point)
        $result = $this->cepWithCoordinates($cep);
        if ($result) return $result;

        // 2. ViaCEP + Nominatim address lookup
        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
            $data = json_decode($response, true);
            if (!$data || isset($data['erro'])) return null;

            $result = $this->nominatimSearch(
                $data['logradouro'] . ', ' . $data['bairro'],
                $data['localidade'],
                $data['uf']
            );
            if ($result) return $result;

            // 3. City center fallback (small cities)
            return $this->geocodeCity($data['localidade'], $data['uf']);
        } catch (\Throwable $e) {
            Log::warning('ViaCEP geocoding failed: ' . $e->getMessage(), ['cep' => $cep]);
            return null;
        }
    }

    public function cepWithCoordinates(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return null;

        try {
            $response = file_get_contents("https://cep.awesomeapi.com.br/json/{$cep}");
            $data = json_decode($response, true);
            if (!$data || isset($data['code']) || empty($data['lat']) || empty($data['lng'])) return null;

            return [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
                'display_name' => trim(($data['address'] ?? '') . ', ' . ($data['city'] ?? '')),
            ];
        } catch (\Throwable $e) {
            Log::warning('CepCoordinates geocoding failed: ' . $e->getMessage(), ['cep' => $cep]);
            return null;
        }
    }

    private function localCityCoords(string $city, ?string $state = null): ?array
    {
        $key = strtolower(trim($city . '-' . ($state ?? '')));
        $map = [
            'guaiçara-sp' => ['lat' => -21.6869, 'lng' => -49.7989],
            'presidente prudente-sp' => ['lat' => -22.1250, 'lng' => -51.3889],
            'lins-sp' => ['lat' => -21.6786, 'lng' => -49.7428],
            'birigui-sp' => ['lat' => -21.2889, 'lng' => -50.3400],
            'aracatuba-sp' => ['lat' => -21.2089, 'lng' => -50.4325],
            'sao paulo-sp' => ['lat' => -23.5505, 'lng' => -46.6333],
            'rio de janeiro-rj' => ['lat' => -22.9068, 'lng' => -43.1729],
            'belo horizonte-mg' => ['lat' => -19.9167, 'lng' => -43.9345],
            'curitiba-pr' => ['lat' => -25.4290, 'lng' => -49.2671],
            'salvador-ba' => ['lat' => -12.9718, 'lng' => -38.5011],
            'brasilia-df' => ['lat' => -15.7975, 'lng' => -47.8919],
            'fortaleza-ce' => ['lat' => -3.7319, 'lng' => -38.5267],
            'recife-pe' => ['lat' => -8.0543, 'lng' => -34.8811],
            'porto alegre-rs' => ['lat' => -30.0346, 'lng' => -51.2177],
            'manaus-am' => ['lat' => -3.1190, 'lng' => -60.0217],
        ];

        if (isset($map[$key])) {
            return $map[$key] + ['display_name' => $city];
        }

        return null;
    }

    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function isWithinRadius(float $restaurantLat, float $restaurantLng, float $customerLat, float $customerLng, float $radiusKm): bool
    {
        return self::haversineDistance($restaurantLat, $restaurantLng, $customerLat, $customerLng) <= $radiusKm;
    }
}
