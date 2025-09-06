<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class PrrContextValidator
{
    /**
     * Validate PRR context structure and data availability
     */
    public static function validateContext(array $context, string $queryType, string $userMessage): array
    {
        $validation = [
            'is_valid' => true,
            'warnings' => [],
            'errors' => [],
            'data_summary' => []
        ];

        try {
            // Check basic context structure
            $requiredFields = ['user_id', 'session_id'];
            foreach ($requiredFields as $field) {
                if (!isset($context[$field])) {
                    $validation['errors'][] = "Missing required field: {$field}";
                    $validation['is_valid'] = false;
                }
            }

            // Validate data availability based on query type
            switch ($queryType) {
                case 'data_company':
                    if (!isset($context['companies']) || empty($context['companies'])) {
                        $validation['warnings'][] = 'Company data requested but not available in context';
                        Log::warning('PrrContextValidator: Company data missing for company query', [
                            'user_message' => substr($userMessage, 0, 100),
                            'context_keys' => array_keys($context)
                        ]);
                    } else {
                        $validation['data_summary']['companies'] = count($context['companies']);
                    }
                    break;

                case 'data_livestock':
                    if (!isset($context['livestock']) || empty($context['livestock'])) {
                        $validation['warnings'][] = 'Livestock data requested but not available in context';
                    } else {
                        $validation['data_summary']['livestock'] = count($context['livestock']);
                    }
                    break;

                case 'data_financial':
                    if (!isset($context['financial']) || empty($context['financial'])) {
                        $validation['warnings'][] = 'Financial data requested but not available in context';
                    } else {
                        $validation['data_summary']['financial'] = count($context['financial']);
                    }
                    break;
            }

            // Log validation results if there are issues
            if (!empty($validation['warnings']) || !empty($validation['errors'])) {
                Log::info('PrrContextValidator: Context validation completed', [
                    'is_valid' => $validation['is_valid'],
                    'warnings_count' => count($validation['warnings']),
                    'errors_count' => count($validation['errors']),
                    'query_type' => $queryType,
                    'user_message' => substr($userMessage, 0, 50)
                ]);
            }

        } catch (Exception $e) {
            $validation['errors'][] = 'Validation failed: ' . $e->getMessage();
            $validation['is_valid'] = false;
            
            Log::error('PrrContextValidator: Validation exception', [
                'error' => $e->getMessage(),
                'query_type' => $queryType
            ]);
        }

        return $validation;
    }

    /**
     * Suggest context improvements based on validation results
     */
    public static function suggestImprovements(array $validation, string $queryType): array
    {
        $suggestions = [];

        foreach ($validation['warnings'] as $warning) {
            if (str_contains($warning, 'Company data')) {
                $suggestions[] = 'Ensure AiDatabaseService::getCompanyData() is called before PRR execution';
            }
            if (str_contains($warning, 'Livestock data')) {
                $suggestions[] = 'Ensure livestock data is fetched and included in context';
            }
            if (str_contains($warning, 'Financial data')) {
                $suggestions[] = 'Ensure financial data is fetched and included in context';
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Check if context has sufficient data for confident response
     */
    public static function hasConfidentData(array $context, string $queryType): bool
    {
        switch ($queryType) {
            case 'data_company':
                return isset($context['companies']) && !empty($context['companies']);
            case 'data_livestock':
                return isset($context['livestock']) && !empty($context['livestock']);
            case 'data_financial':
                return isset($context['financial']) && !empty($context['financial']);
            default:
                return true; // For general queries, assume confident
        }
    }
}