<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PokeApiService
{
    private Client $client;
    private const BASE_URL = 'https://pokeapi.co/api/v2/';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => 5.0,
        ]);
    }

    public function fetchPokemon(int|string $id): ?array
    {
        try {
            $response = $this->client->get("pokemon/{$id}");
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {
            Log::error("PokeApiService::fetchPokemon({$id}) error: " . $e->getMessage());
            return null;
        }
    }

    public function fetchMove(string $name): ?array
    {
        try {
            $response = $this->client->get("move/{$name}");
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {
            Log::warning("PokeApiService::fetchMove({$name}) error: " . $e->getMessage());
            return null;
        }
    }

    public function fetchUrl(string $url): ?array
    {
        try {
            $response = $this->client->get($url);
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {
            Log::error("PokeApiService::fetchUrl({$url}) error: " . $e->getMessage());
            return null;
        }
    }

    public function fetchPokemonList(int $limit, int $offset): ?array
    {
        try {
            $response = $this->client->get("pokemon", [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {
            Log::error("PokeApiService::fetchPokemonList error: " . $e->getMessage());
            return null;
        }
    }
}