# AiDatabaseService Refactor Design Document

## File Backup

Before making any changes to the existing `AiDatabaseService.php` file, a backup will be created at `app/Services/AiDatabaseService.backup.php` to preserve the original implementation.

The backup will be created by copying the original file to preserve the implementation before refactoring.

## Refactored Implementation

The refactored `AiDatabaseService` will be implemented with the following key changes:

## Implementation

The refactored `AiDatabaseService` will be implemented with the following changes:

1. Removed all text formatting and language detection methods
2. Simplified data structures to return raw data only
3. Added comprehensive PHPDoc comments to all methods
4. Maintained all existing functionality for data retrieval
5. Preserved SuperAdmin bypass functionality for data access

### Key Changes

1. **Removed Methods**:
   - `formatDataForAI()`: Text formatting should be handled by AI models
   - `getLanguageHint()`: Language detection is not the service's responsibility

2. **Modified Return Types**:
   - All methods now return structured data arrays without any text formatting
   - Error handling returns empty arrays/defaults instead of formatted error messages

3. **Improved Documentation**:
   - Added detailed PHPDoc comments to all methods
   - Added comments to explain important variables and logic

4. **Preserved Functionality**:
   - All data retrieval logic remains intact
   - SuperAdmin vs regular user data access permissions are maintained
   - Database query logic is unchanged

## 1. Overview

This document outlines the design for refactoring the `AiDatabaseService` to focus purely on data retrieval and output without any text manipulation or formatting. The refactored service will provide raw data in JSON format that can be consumed by AI models or other services for further processing and natural language generation.

The refactored service will remove all text formatting and language detection methods, focusing solely on providing structured data that can be consumed by AI models for natural language processing and response generation.

## 2. Current Issues

The current `AiDatabaseService` has several concerns that need to be addressed:

1. **Text Formatting**: The service includes methods like `formatDataForAI()` that manipulate text and context, which should be handled by the AI model or presentation layer.
2. **Mixed Responsibilities**: The service handles both data retrieval and presentation formatting.
3. **Language Detection**: Contains logic for language detection that should be handled by the consuming AI service.
4. **Complex Output**: Returns formatted strings instead of structured data.

## 3. Refactored Design Goals

1. **Pure Data Focus**: The service will only retrieve and return raw data in structured formats (JSON/array).
2. **No Text Manipulation**: Remove all text formatting, language detection, and context manipulation.
3. **Clear Separation of Concerns**: Data retrieval logic separated from presentation logic.
4. **Consistent Output Format**: All methods will return structured data arrays.
5. **Improved Documentation**: Add comprehensive comments to all methods and important variables.

## 4. Architecture Changes

### 4.1 Class Structure

The refactored `AiDatabaseService` class will maintain the same structure but with significant changes to method implementations to remove text formatting and focus purely on data retrieval.

```php
class AiDatabaseService
{
    // Private helper methods for checking user permissions
    private function isSuperAdmin(User $user): bool
    
    // Data retrieval methods that return structured data only
    public function getLivestockSummary(): array
    public function getFinancialSummary(): array
    public function searchData(string $query, array $filters = []): array
    public function getCompanyData(User $user): array
    
    // Private data retrieval methods
    private function getTotalLivestock($companyId): int
    private function getActiveBatches($companyId): int
    private function getRecentMortality($companyId): array
    private function getFeedConsumption($companyId): array
    private function getMonthlyExpenses($companyId, $month): float
    private function getMonthlyRevenue($companyId, $month): float
    private function getFeedCosts($companyId, $month): float
    private function getRecentPurchases($companyId): array
    private function getFeedData($companyId): array
    private function getCounts($companyId): array
    private function getUserCount($companyId): int
    private function getLivestockDetails($companyId): array
    private function getFeedUsageDetails($companyId): array
    private function getFarmDetails($companyId): array
    private function getBatchDetails($companyId): array
}
```

### 4.2 Method Return Types

All methods will return structured data:
- **Counts and numbers**: Return integers or floats directly
- **Collections**: Return arrays of structured data
- **Complex data**: Return associative arrays with clearly defined keys

### 4.3 Removed Methods

The following methods will be removed from the refactored service:
- `formatDataForAI()`: Text formatting should be handled by AI models
- `getLanguageHint()`: Language detection is not the service's responsibility

## 5. Implementation Details

### 5.1 Data Structure Examples

#### getLivestockSummary()
```php
// Returns:
[
    'total_livestock' => 1500,
    'active_batches' => 12,
    'recent_mortality' => [
        'count' => 25,
        'period' => '30 days'
    ],
    'feed_consumption' => [
        'total_kg' => 4500.5,
        'period' => '7 days'
    ]
]
```

#### getCompanyData()
```php
// Returns:
[
    'total_companies' => 1,
    'companies' => [
        [
            'id' => 1,
            'code' => 'COMP001',
            'name' => 'Sample Company',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'info@company.com',
            'status' => 'active',
            'type' => 'poultry',
            'created_at' => '2023-01-15 10:30:00'
        ]
    ]
]
```

#### searchData()
```php
// Returns:
[
    'companies' => [
        'total_companies' => 1,
        'companies' => [
            [
                'id' => 1,
                'code' => 'COMP001',
                'name' => 'Sample Company',
                'status' => 'active',
                'type' => 'poultry',
                'created_at' => '2023-01-15 10:30:00'
            ]
        ]
    ],
    'livestock' => [
        'total_livestock' => 1500,
        'active_batches' => 12
    ]
]
```

#### getFinancialSummary()
```php
// Returns:
[
    'monthly_expenses' => 50000.00,
    'monthly_revenue' => 75000.00,
    'feed_costs' => 20000.00,
    'recent_purchases' => [
        [
            'created_at' => '2023-05-15 10:30:00',
            'amount' => 5000.00,
            'type' => 'livestock'
        ]
    ]
]
```

### 5.2 Key Improvements

1. **Cleaner Data Structures**: All returned data will be in clean, structured formats without any text formatting.
2. **Better Error Handling**: Maintain existing error handling but return empty arrays/defaults instead of formatted error messages.
3. **Consistent Naming**: Use consistent naming conventions for data keys.
4. **Comprehensive Comments**: Add detailed PHPDoc comments to all methods explaining parameters, return values, and functionality.

### 5.3 Method Implementation Details

#### isSuperAdmin(User $user)
This helper method will remain unchanged as it only performs a role check and returns a boolean value.

#### getLivestockSummary()
This method will continue to aggregate livestock data but will return raw structured data without any text formatting.

#### getFinancialSummary()
This method will continue to aggregate financial data but will return raw structured data without any text formatting.

#### searchData(string $query, array $filters = [])
This method will continue to analyze queries and fetch relevant data but will return raw structured data without any text formatting.

#### getCompanyData(User $user)
This method will be modified to remove the `message` field and formatted date fields, returning only raw company data.

#### Removed Methods
The following methods will be completely removed from the refactored implementation:
- `formatDataForAI()`: This method will be removed as text formatting should be handled by AI models
- `getLanguageHint()`: This method will be removed as language detection should be handled by consuming services

## 6. Migration Plan

### 6.1 File Backup

Before implementing changes, backup the existing file:
- Backup `app/Services/AiDatabaseService.php` to `app/Services/AiDatabaseService.backup.php`

### 6.2 Implementation Steps

1. Create a backup of the original `AiDatabaseService.php` file
2. Create a new implementation of `AiDatabaseService` with the refactored design
3. Update all method return types to provide raw data only
4. Remove text formatting methods (`formatDataForAI()` and `getLanguageHint()`)
5. Add comprehensive documentation and comments to all methods
6. Maintain all existing database query logic and SuperAdmin bypass functionality
7. Ensure error handling returns appropriate empty structures instead of formatted messages
8. Test all methods to verify they return structured data without text formatting

### 6.3 Testing Considerations

- Verify all data retrieval methods return correct structured data
- Confirm no text formatting or language manipulation occurs
- Test SuperAdmin vs regular user data access permissions
- Validate error handling returns appropriate empty structures
- Ensure all methods return consistent data types (arrays, integers, floats)
- Verify that removed methods are no longer accessible
- Test that all database queries continue to function correctly
- Confirm that SuperAdmin bypass functionality is preserved
- Validate that company-scoped data access is maintained for regular users

## 7. Impact Assessment

### 7.1 Affected Components

- Any components that consume `AiDatabaseService` will need to handle the raw data output
- AI chat components that rely on formatted output will need updates
- Any reporting features that use this service data

### 7.2 Benefits

- Cleaner separation of concerns
- More maintainable codebase
- Better performance by removing unnecessary text processing
- More flexible data consumption by other services

## 8. Conclusion

The refactored `AiDatabaseService` will provide a cleaner, more focused data retrieval service without any text manipulation or formatting. This approach aligns better with separation of concerns principles and allows AI models to handle the natural language processing and response generation.

By removing text formatting and language detection from the data service, we achieve:
- Cleaner separation of concerns between data retrieval and presentation
- More maintainable codebase with focused responsibilities
- Better performance by removing unnecessary text processing
- More flexible data consumption by AI models and other services
- Consistent structured data output that can be easily consumed and transformed

The refactored service will maintain all existing functionality while providing a more robust foundation for AI-driven applications.

## 9. Sample Implementation

Below is a sample implementation of the refactored `getCompanyData` method:

```php
/**
 * Get company data based on user permissions
 *
 * @param User $user The authenticated user
 * @return array Company data in structured format
 */
public function getCompanyData(User $user): array
{
    try {
        $userRoles = $user->getRoleNames()->toArray();
        $isSuperAdmin = !empty(array_intersect(
            array_map('strtolower', $userRoles),
            ['superadmin', 'super-admin', 'system', 'admin']
        ));

        Log::info('AiDatabaseService: Getting company data', [
            'user_id' => $user->id,
            'user_roles' => $userRoles,
            'is_super_admin' => $isSuperAdmin,
            'user_company_id' => $user->company_id
        ]);

        $companies = collect();

        if ($isSuperAdmin) {
            // SuperAdmin can see all companies
            $companies = DB::table('companies')
                ->select('id', 'code', 'name', 'address', 'phone', 'email', 'status', 'type', 'created_at')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('AiDatabaseService: SuperAdmin accessing all companies', [
                'total_companies' => $companies->count()
            ]);
        } else {
            // Regular users can only see their own company
            if ($user->company_id) {
                $companies = DB::table('companies')
                    ->select('id', 'code', 'name', 'address', 'phone', 'email', 'status', 'type', 'created_at')
                    ->where('id', $user->company_id)
                    ->whereNull('deleted_at')
                    ->get();

                Log::info('AiDatabaseService: Regular user accessing own company', [
                    'company_id' => $user->company_id,
                    'found_company' => $companies->count() > 0
                ]);
            } else {
                Log::warning('AiDatabaseService: User has no company association', [
                    'user_id' => $user->id
                ]);
            }
        }

        if ($companies->isEmpty()) {
            return [
                'total_companies' => 0,
                'companies' => []
            ];
        }

        // Return raw company data without formatting
        $companyData = $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'code' => $company->code,
                'name' => $company->name,
                'address' => $company->address,
                'phone' => $company->phone,
                'email' => $company->email,
                'status' => $company->status,
                'type' => $company->type,
                'created_at' => $company->created_at
            ];
        });

        $result = [
            'total_companies' => $companies->count(),
            'companies' => $companyData->toArray()
        ];

        Log::info('AiDatabaseService: Company data retrieval successful', [
            'total_companies' => $companies->count()
        ]);

        return $result;

    } catch (Exception $e) {
        Log::error('AiDatabaseService: Error getting company data', [
            'error' => $e->getMessage(),
            'user_id' => $user->id,
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'total_companies' => 0,
            'companies' => []
        ];
    }
}
```

Below is a sample implementation of the refactored `getLivestockSummary` method:

```php
/**
 * Get livestock summary for the current user/company
 *
 * @return array Livestock summary data in structured format
 */
public function getLivestockSummary(): array
{
    try {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Example queries - adjust table names based on your schema
        $summary = [
            'total_livestock' => $this->getTotalLivestock($companyId),
            'active_batches' => $this->getActiveBatches($companyId),
            'recent_mortality' => $this->getRecentMortality($companyId),
            'feed_consumption' => $this->getFeedConsumption($companyId),
        ];

        return $summary;

    } catch (Exception $e) {
        Log::error('AiDatabaseService: Error getting livestock summary', [
            'error' => $e->getMessage(),
            'user_id' => Auth::id()
        ]);
        return [];
    }
}
```
