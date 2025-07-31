<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Livestock;
use App\Models\FeedPurchase;
use App\Models\SupplyPurchase;

class OllamaChatService
{
    /**
     * Check if server is accessible
     */
    public function checkServerConnection(string $url): bool
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if model is available
     */
    public function checkModelAvailability(string $url, string $model): bool
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            if (!$response->successful()) {
                return false;
            }

            $models = $response->json()['models'] ?? [];
            $availableModels = array_column($models, 'name');

            return in_array($model, $availableModels);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models as array
     */
    public function getAvailableModelsArray(string $url): array
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return array_column($models, 'name');
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        return [];
    }

    /**
     * Suggest similar model based on name similarity
     */
    public function suggestSimilarModel(string $requestedModel, array $availableModels): ?string
    {
        $requestedModel = strtolower($requestedModel);
        $requestedBase = preg_replace('/:latest$|:\d+\.\d+.*$/', '', $requestedModel);

        foreach ($availableModels as $model) {
            $modelLower = strtolower($model);
            $modelBase = preg_replace('/:latest$|:\d+\.\d+.*$/', '', $modelLower);

            // Exact base name match
            if ($modelBase === $requestedBase) {
                return $model;
            }

            // Partial match (e.g., qwen2.5vl -> qwen3)
            if (strpos($modelBase, $requestedBase) !== false || strpos($requestedBase, $modelBase) !== false) {
                return $model;
            }

            // Special case for qwen variants
            if (strpos($requestedBase, 'qwen') !== false && strpos($modelBase, 'qwen') !== false) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Get popular models
     */
    public function getPopularModels(): array
    {
        return [
            'llama2:latest',
            'llama3.1:latest',
            'qwen3:8b',
            'gemma3n:latest',
            'deepseek-coder:latest'
        ];
    }

    /**
     * Detect natural language queries and convert to complex queries
     */
    public function detectNaturalLanguageQuery(string $prompt): ?string
    {
        $promptLower = strtolower($prompt);

        // Enhanced purchase-related queries with specific types
        if (preg_match('/(pembelian|purchase|beli|buy)/i', $promptLower)) {
            // Detect specific purchase types
            if (preg_match('/(pakan|feed|makanan)/i', $promptLower)) {
                return $this->buildPurchaseQuery('feed', $promptLower);
            }

            if (preg_match('/(ternak|livestock|ayam|chicken|sapi|cow)/i', $promptLower)) {
                return $this->buildPurchaseQuery('livestock', $promptLower);
            }

            if (preg_match('/(supply|suplai|perlengkapan|equipment)/i', $promptLower)) {
                return $this->buildPurchaseQuery('supply', $promptLower);
            }

            // Generic purchase query
            return $this->buildPurchaseQuery('all', $promptLower);
        }

        // Detect livestock-related queries (non-purchase)
        if (preg_match('/(ternak|livestock|ayam|chicken|sapi|cow)/i', $promptLower)) {
            // Detect status queries
            if (preg_match('/(aktif|active|berjalan|running)/i', $promptLower)) {
                return "livestock status=active";
            }

            if (preg_match('/(selesai|completed|finished)/i', $promptLower)) {
                return "livestock status=completed";
            }

            // Default to active livestock
            return "livestock status=active";
        }

        // Detect analytics queries
        if (preg_match('/(analisis|analytics|laporan|report|statistik|statistics)/i', $promptLower)) {
            return "analytics overview";
        }

        // Detect performance queries
        if (preg_match('/(performa|performance|kinerja|efficiency)/i', $promptLower)) {
            return "analytics performance";
        }

        return null;
    }

    /**
     * Build purchase query based on type and criteria
     */
    private function buildPurchaseQuery(string $type, string $promptLower): string
    {
        // Detect time-based queries
        if (preg_match('/(bulan|month)\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+(\d{4})/i', $promptLower, $matches)) {
            $month = $this->convertMonthToNumber($matches[2]);
            $year = $matches[3];
            $startDate = "{$year}-{$month}-01";
            $endDate = "{$year}-{$month}-31";

            if ($type === 'all') {
                return "pembelian bulan {$matches[2]} {$year}";
            }
            return "pembelian {$type} bulan {$matches[2]} {$year}";
        }

        // Detect year-based queries
        if (preg_match('/(tahun|year)\s+(\d{4})/i', $promptLower, $matches)) {
            $year = $matches[2];
            if ($type === 'all') {
                return "pembelian tahun {$year}";
            }
            return "pembelian {$type} tahun {$year}";
        }

        // Detect recent queries
        if (preg_match('/(terbaru|recent|latest|terakhir|last)/i', $promptLower)) {
            if ($type === 'all') {
                return "pembelian terbaru";
            }
            return "pembelian {$type} terbaru";
        }

        // Default to recent purchases
        if ($type === 'all') {
            return "pembelian terbaru";
        }
        return "pembelian {$type} terbaru";
    }

    /**
     * Convert month name to number
     */
    public function convertMonthToNumber(string $month): string
    {
        $months = [
            'januari' => '01',
            'jan' => '01',
            'februari' => '02',
            'feb' => '02',
            'maret' => '03',
            'mar' => '03',
            'april' => '04',
            'apr' => '04',
            'mei' => '05',
            'may' => '05',
            'juni' => '06',
            'jun' => '06',
            'juli' => '07',
            'jul' => '07',
            'agustus' => '08',
            'aug' => '08',
            'september' => '09',
            'sep' => '09',
            'oktober' => '10',
            'oct' => '10',
            'november' => '11',
            'nov' => '11',
            'desember' => '12',
            'dec' => '12'
        ];

        $monthLower = strtolower($month);
        return $months[$monthLower] ?? '01';
    }

    /**
     * Generate cache key
     */
    public function generateCacheKey(string $type, ?string $param1, ?string $param2, int $limit): string
    {
        return "ollama_context_{$type}_" . md5($param1 . $param2 . $limit);
    }

    /**
     * Get data from cache
     */
    public function getFromCache(string $key): ?string
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set data in cache
     */
    public function setCache(string $key, string $data, int $ttl): void
    {
        try {
            Cache::put($key, $data, $ttl);
        } catch (\Exception $e) {
            // Ignore cache errors
        }
    }
}
