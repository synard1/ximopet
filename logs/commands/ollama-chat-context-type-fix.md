# OllamaChatCommand --context-type Parameter Fix

**Date:** December 2024  
**Status:** ✅ FIXED  
**Issue:** Database context data not being fetched with --context-type parameter

## Problem Description

When using the `--context-type` parameter, the command was not fetching any database context data and was sending the raw prompt to Ollama instead of the enhanced prompt with database context.

### Example of the Issue:

```bash
php artisan ollama:chat "ada brapa pembelian feed" --model=gemma2:2b --context-type=feed --debug
```

**Expected Behavior:**

-   Fetch feed purchase data from database
-   Include database context in the prompt
-   Send enhanced prompt to Ollama

**Actual Behavior:**

-   No database context fetched
-   Raw prompt sent to Ollama
-   Response without database context

## Root Cause Analysis

The issue was in the `buildEnhancedPrompt` method in `OllamaChatCommand.php`. The method was only checking for the `$context` parameter but not the `$contextType` parameter:

```php
// OLD CODE - Only checked $context
if (!empty($context)) {
    $this->logInfo("🔍 Fetching context data for: {$context}", $debug);
    $contextData = $this->getContextDataWithCache($context, $contextType, $contextLimit, $useCache, $cacheTtl, $noCache, $debug);
}
```

When only `--context-type` was provided (without `--context`), the condition `!empty($context)` was false, so no context data was fetched.

## Solution Implemented

### 1. Updated `buildEnhancedPrompt` Method

**File:** `app/Console/Commands/OllamaChatCommand.php`

**Changes:**

```php
// NEW CODE - Check both $context and $contextType
if (!empty($context) || !empty($contextType)) {
    $contextParam = !empty($context) ? $context : $contextType;
    $this->logInfo("🔍 Fetching context data for: {$contextParam}", $debug);
    $contextData = $this->getContextDataWithCache($context, $contextType, $contextLimit, $useCache, $cacheTtl, $noCache, $debug);
}
```

### 2. Updated `getContextDataWithCache` Method

**Changes:**

```php
// Determine if we should use contextType or context
$contextToUse = !empty($contextType) ? $contextType : $context;

// Determine if context is a type or specific data
$isContextType = !empty($contextType) || in_array(strtolower($contextToUse), ['livestock', 'feed', 'supply', 'recent', 'latest']);
```

This ensures that when `--context-type` is provided, it takes precedence over `--context` and is properly used to fetch context data.

## Testing Results

### ✅ Test Case 1: Feed Context Type

```bash
php artisan ollama:chat "ada brapa pembelian feed" --model=gemma2:2b --context-type=feed --debug
```

**Result:**

-   ✅ Context data fetched: `🔍 Fetching context data for: feed`
-   ✅ Database context included in prompt
-   ✅ Feed purchase data retrieved: `=== RECENT FEED PURCHASES ===`

### ✅ Test Case 2: Livestock Context Type

```bash
php artisan ollama:chat "ada brapa pembelian selama bulan mei 2025" --context-type=livestock --model=gemma2:2b --no-cache --debug
```

**Result:**

-   ✅ Context data fetched: `🔍 Fetching context data for: livestock`
-   ✅ Cache disabled: `🚫 Cache disabled, fetching fresh data`
-   ✅ Database context included in prompt
-   ✅ Livestock data retrieved: `PR-Farm01-K2F1-01052025` with 12,000 units

### ✅ Test Case 3: Natural Language with Context Type

```bash
php artisan ollama:chat "berapa ternak yang aktif saat ini" --context-type=livestock --model=gemma2:2b --debug
```

**Result:**

-   ✅ Context data fetched: `🔍 Fetching context data for: livestock`
-   ✅ Database context included in prompt
-   ✅ Ollama responded with context-aware answer about active livestock

## Code Changes Summary

### Files Modified:

1. **`app/Console/Commands/OllamaChatCommand.php`**
    - Updated `buildEnhancedPrompt()` method
    - Updated `getContextDataWithCache()` method

### Key Improvements:

1. **Proper Parameter Handling**: Both `--context` and `--context-type` parameters are now properly handled
2. **Context Type Precedence**: When both parameters are provided, `--context-type` takes precedence
3. **Better Logging**: Clear indication of which context parameter is being used
4. **Consistent Behavior**: All context-related features now work with both parameter types

## Usage Examples

### Using --context-type Parameter:

```bash
# Fetch feed purchase data
php artisan ollama:chat "Question" --context-type=feed --model=gemma2:2b

# Fetch livestock data
php artisan ollama:chat "Question" --context-type=livestock --model=gemma2:2b

# Fetch supply data
php artisan ollama:chat "Question" --context-type=supply --model=gemma2:2b

# Fetch recent data
php artisan ollama:chat "Question" --context-type=recent --model=gemma2:2b
```

### Using --context Parameter (still works):

```bash
# Fetch specific livestock by name
php artisan ollama:chat "Question" --context="PR-Farm01-K2F1-01052025" --model=gemma2:2b

# Fetch specific feed purchase by invoice
php artisan ollama:chat "Question" --context="00001" --model=gemma2:2b
```

## Conclusion

The `--context-type` parameter is now working correctly and properly fetches database context data. The command provides:

-   ✅ **Proper database context integration**
-   ✅ **Enhanced prompts with relevant data**
-   ✅ **Context-aware responses from Ollama**
-   ✅ **Flexible parameter usage**
-   ✅ **Consistent behavior across all context types**

The fix ensures that users can easily fetch different types of database context using the `--context-type` parameter, making the command more user-friendly and functional.
