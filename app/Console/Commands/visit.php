<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class visit extends Command
{
    protected $signature = 'visit
        {path? : Full URL or relative path (optional when using --name)}
        {--name= : Laravel route name to visit}
        {--method=GET : HTTP method (GET, POST, PUT, DELETE, PATCH)}
        {--data= : JSON or query string for request body or params}';

    protected $description = 'Send an HTTP request to a URL or to a Laravel route name';

    public function handle()
    {
        $path = $this->argument('path');
        $name = $this->option('name');
        $method = strtoupper($this->option('method'));
        $dataOption = $this->option('data');

        if ($name) {
            if (!Route::has($name)) {
                $this->error("Route name '{$name}' does not exist.");
                return 1;
            }

            $data = $this->parseData($dataOption);
            $path = route($name, $data, false);
            $this->info("Route name '{$name}' is {$path}");
        } else {
            $data = $this->parseData($dataOption);
        }

        $url = $this->isFullUrl($path)
            ? $path
            : config('app.url') . '/' . ltrim($path, '/');

        $this->info("Sending {$method} request to: {$url}");

        try {
            $response = Http::timeout(10)->retry(2, 200)->send($method, $url, [
                'query' => $method === 'GET' ? $data : null,
                'json' => in_array($method, ['POST', 'PUT', 'PATCH']) ? $data : null,
            ]);

            $this->info('Status: ' . $response->status());
            $this->line('Response:');
            $this->line($response->body());
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }

        return 0;
    }

    protected function isFullUrl(?string $path): bool
    {
        return $path && (Str::startsWith($path, 'http://') || Str::startsWith($path, 'https://'));
    }

    protected function parseData(?string $data): array
    {
        if (!$data) return [];

        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        parse_str($data, $array);
        return $array;
    }
}
