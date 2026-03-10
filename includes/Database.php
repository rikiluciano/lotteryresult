<?php
/**
 * Supabase Database Wrapper
 * Handles connections and basic CRUD via Supabase REST API (PostgREST)
 */

class Database
{
    private $url;
    private $key;

    public function __construct()
    {
        // En producción usaremos variables de entorno o un archivo config.php protegido
        $this->loadConfig();
    }

    private function loadConfig()
    {
        if (file_exists(__DIR__ . '/../config.php')) {
            require_once __DIR__ . '/../config.php';
            $this->url = SUPABASE_URL;
            $this->key = SUPABASE_KEY;
        }
        else {
            // Fallback for local development or initial setup
            $this->url = getenv('SUPABASE_URL');
            $this->key = getenv('SUPABASE_KEY');
        }
    }

    /**
     * Fetch data from a table with filters
     */
    public function fetch($table, $params = [])
    {
        $queryString = http_build_query($params);
        $endpoint = "{$this->url}/rest/v1/{$table}?{$queryString}";

        return $this->request('GET', $endpoint);
    }

    /**
     * Insert data into a table
     */
    public function insert($table, $data)
    {
        $endpoint = "{$this->url}/rest/v1/{$table}";
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Generic request handler
     */
    private function request($method, $url, $data = null)
    {
        $ch = curl_init($url);

        $headers = [
            "apikey: {$this->key}",
            "Authorization: Bearer {$this->key}",
            "Content-Type: application/json",
            "Prefer: return=representation"
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Supabase API Error ($httpCode): " . $response);
        }

        return json_decode($response, true);
    }
}
