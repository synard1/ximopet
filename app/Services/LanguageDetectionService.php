<?php

namespace App\Services;

use LanguageDetection\Language;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Language Detection Service
 * 
 * This service provides robust language detection capabilities using the
 * patrickschur/language-detection library with fallback mechanisms and caching.
 * 
 * Features:
 * - Uses advanced N-gram analysis for accurate detection
 * - Supports 110+ languages
 * - Caching for performance optimization
 * - Fallback to keyword-based detection
 * - Confidence scoring
 * - Whitelist support for specific languages
 */
class LanguageDetectionService
{
    protected $detector;
    protected $cachePrefix = 'lang_detect_';
    protected $cacheTtl = 3600; // 1 hour
    
    /**
     * Configuration from config/chat.php
     */
    protected $config;
    
    /**
     * Supported languages for the chat system
     */
    protected $supportedLanguages;
    
    /**
     * Indonesian keywords for fallback detection
     */
    protected $indonesianKeywords = [
        'tampilkan', 'perusahaan', 'berapa', 'cara', 'bagaimana', 'apa', 'siapa',
        'dimana', 'kapan', 'mengapa', 'kenapa', 'yang', 'dan', 'atau', 'dengan',
        'untuk', 'dari', 'ke', 'di', 'pada', 'dalam', 'saya', 'kami', 'kita',
        'ternak', 'ayam', 'pakan', 'kandang', 'keuangan', 'data', 'informasi',
        'jumlah', 'total', 'daftar', 'laporan', 'status', 'bisa', 'dapat',
        'harus', 'akan', 'sudah', 'telah', 'sedang', 'tidak', 'belum',
        'optimasi', 'server', 'sistem', 'aplikasi', 'ada', 'farm', 'peternakan',
        'semua', 'terdaftar', 'list', 'bagaimana', 'mana', 'oleh', 'sebagai',
        'ini', 'itu', 'mereka', 'dia', 'beliau', 'masih', 'jangan'
    ];
    
    /**
     * English keywords for fallback detection
     */
    protected $englishKeywords = [
        'show', 'display', 'list', 'how many', 'what', 'where', 'when', 'why',
        'how', 'can you', 'please', 'give me', 'tell me', 'farms', 'livestock',
        'chicken', 'poultry', 'feed', 'financial', 'company', 'companies',
        'active', 'inactive', 'all', 'total', 'count', 'number', 'only',
        'the', 'and', 'or', 'but', 'with', 'for', 'from', 'to', 'in', 'on',
        'at', 'by', 'as', 'this', 'that', 'they', 'we', 'you', 'he', 'she',
        'it', 'can', 'will', 'would', 'should', 'could', 'have', 'has', 'had',
        'is', 'are', 'was', 'were', 'been', 'being', 'do', 'does', 'did'
    ];
    
    public function __construct()
    {
        // Load configuration
        $this->config = config('chat.language_detection', []);
        $this->supportedLanguages = $this->config['supported_languages'] ?? ['en', 'id'];
        $this->cacheTtl = $this->config['cache_ttl'] ?? 3600;
        
        try {
            if ($this->config['enabled'] ?? true) {
                $this->detector = new Language($this->supportedLanguages);
            } else {
                $this->detector = null;
            }
        } catch (Exception $e) {
            Log::warning('LanguageDetectionService: Failed to initialize detector', [
                'error' => $e->getMessage()
            ]);
            $this->detector = null;
        }
    }
    
    /**
     * Detect language of the given text
     * 
     * @param string $text The text to analyze
     * @param bool $useCache Whether to use caching
     * @return array Detection result with language code and confidence
     */
    public function detect(string $text, bool $useCache = true): array
    {
        if (empty(trim($text))) {
            return $this->createResult('en', 0.0, 'empty_text');
        }
        
        $cacheKey = $this->cachePrefix . md5($text);
        
        // Try to get from cache first
        if ($useCache && ($this->config['cache_enabled'] ?? true)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $result = $this->performDetection($text);
        
        // Cache the result
        if ($useCache && ($this->config['cache_enabled'] ?? true)) {
            Cache::put($cacheKey, $result, $this->cacheTtl);
        }
        
        return $result;
    }
    
    /**
     * Detect if text is in Indonesian language
     * 
     * @param string $text The text to analyze
     * @return bool True if Indonesian, false otherwise
     */
    public function isIndonesian(string $text): bool
    {
        $result = $this->detect($text);
        return $result['language'] === 'id';
    }
    
    /**
     * Detect if text is in English language
     * 
     * @param string $text The text to analyze
     * @return bool True if English, false otherwise
     */
    public function isEnglish(string $text): bool
    {
        $result = $this->detect($text);
        return $result['language'] === 'en';
    }
    
    /**
     * Get the most likely language code
     * 
     * @param string $text The text to analyze
     * @return string Language code (en, id)
     */
    public function getLanguage(string $text): string
    {
        $result = $this->detect($text);
        return $result['language'];
    }
    
    /**
     * Get detection confidence score
     * 
     * @param string $text The text to analyze
     * @return float Confidence score between 0.0 and 1.0
     */
    public function getConfidence(string $text): float
    {
        $result = $this->detect($text);
        return $result['confidence'];
    }
    
    /**
     * Perform the actual language detection
     * 
     * @param string $text The text to analyze
     * @return array Detection result
     */
    protected function performDetection(string $text): array
    {
        // Try advanced detection first
        if ($this->detector !== null) {
            try {
                $result = $this->detector->detect($text)
                    ->whitelist('en', 'id')
                    ->close();
                
                if (!empty($result)) {
                    $mostLikely = array_key_first($result);
                    $confidence = $result[$mostLikely];
                    
                    // Map language codes
                    $language = $this->mapLanguageCode($mostLikely);
                    
                    return $this->createResult($language, $confidence, 'advanced_detection');
                }
            } catch (Exception $e) {
                Log::warning('LanguageDetectionService: Advanced detection failed', [
                    'error' => $e->getMessage(),
                    'text_preview' => substr($text, 0, 100)
                ]);
            }
        }
        
        // Fallback to keyword-based detection
        return $this->keywordBasedDetection($text);
    }
    
    /**
     * Keyword-based language detection as fallback
     * 
     * @param string $text The text to analyze
     * @return array Detection result
     */
    protected function keywordBasedDetection(string $text): array
    {
        $text = strtolower(trim($text));
        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return $this->createResult('en', 0.0, 'no_words');
        }
        
        $indonesianMatches = 0;
        $englishMatches = 0;
        
        // Count keyword matches
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:"\'\'');
            
            if (in_array($word, $this->indonesianKeywords)) {
                $indonesianMatches++;
            }
            
            if (in_array($word, $this->englishKeywords)) {
                $englishMatches++;
            }
        }
        
        // Calculate confidence based on keyword density
        $indonesianRatio = $indonesianMatches / $totalWords;
        $englishRatio = $englishMatches / $totalWords;
        
        // Get thresholds from config
        $keywordThreshold = $this->config['keyword_density_threshold'] ?? 0.1;
        $confidenceThreshold = $this->config['confidence_threshold'] ?? 0.3;
        $defaultLanguage = $this->config['default_language'] ?? 'en';
        
        // Determine language based on keyword matches
        if ($indonesianMatches > $englishMatches && $indonesianRatio > $keywordThreshold) {
            $confidence = min(0.9, $indonesianRatio * 2); // Cap at 0.9 for keyword-based
            return $this->createResult('id', $confidence, 'keyword_based');
        } elseif ($englishMatches > 0 && $englishRatio > $keywordThreshold) {
            $confidence = min(0.9, $englishRatio * 2);
            return $this->createResult('en', $confidence, 'keyword_based');
        }
        
        // Default to configured default language with low confidence
        return $this->createResult($defaultLanguage, $confidenceThreshold, 'default_fallback');
    }
    
    /**
     * Map language codes to supported codes
     * 
     * @param string $code The detected language code
     * @return string Mapped language code
     */
    protected function mapLanguageCode(string $code): string
    {
        $mapping = [
            'id' => 'id',
            'indonesian' => 'id',
            'en' => 'en',
            'english' => 'en'
        ];
        
        return $mapping[$code] ?? 'en';
    }
    
    /**
     * Create a standardized result array
     * 
     * @param string $language The detected language code
     * @param float $confidence The confidence score
     * @param string $method The detection method used
     * @return array Standardized result
     */
    protected function createResult(string $language, float $confidence, string $method): array
    {
        return [
            'language' => $language,
            'confidence' => round($confidence, 3),
            'method' => $method,
            'timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Clear language detection cache
     * 
     * @return bool Success status
     */
    public function clearCache(): bool
    {
        try {
            // For file-based cache, we'll use Cache::flush() or try to clear specific keys
            // This is a simplified approach that works with different cache drivers
            Cache::flush();
            return true;
        } catch (Exception $e) {
            Log::error('LanguageDetectionService: Failed to clear cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            // For file-based cache, we can't easily count entries
            // So we'll return configuration info instead
            return [
                'cache_enabled' => $this->config['cache_enabled'] ?? true,
                'cache_prefix' => $this->cachePrefix,
                'cache_ttl' => $this->cacheTtl,
                'cache_driver' => config('cache.default'),
                'supported_languages' => $this->supportedLanguages,
                'detector_available' => $this->detector !== null
            ];
        } catch (Exception $e) {
            return [
                'cache_enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}