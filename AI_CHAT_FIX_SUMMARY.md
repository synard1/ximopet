# AI Chat System Fixes - Indonesian Language Detection & Context Enforcement

## Issues Identified
Based on the logs and user report, the AI chat system had several critical issues:

1. **Poor Indonesian Language Detection**: The system only checked for 3 keywords (`tampilkan`, `perusahaan`, `berapa`) but missed common Indonesian words like `cara`, `optimasi`, etc.

2. **Insufficient Context Enforcement**: The AI was responding to general IT questions (like "cara optimasi linux server") instead of redirecting to livestock management topics.

3. **Inconsistent System Prompts**: Different code paths had different levels of context enforcement.

## Fixes Implemented

### 1. Enhanced Language Detection (AiPlanningService.php)

**Before:**
```php
// Only checked 3 keywords
if (strpos($message, 'tampilkan') !== false ||
    strpos($message, 'perusahaan') !== false ||
    strpos($message, 'berapa') !== false) {
    return 'indonesian';
}
```

**After:**
```php
// Extended Indonesian keywords for better detection
$indonesianKeywords = [
    'tampilkan', 'perusahaan', 'berapa', 'cara', 'bagaimana', 'apa', 'siapa',
    'dimana', 'kapan', 'mengapa', 'kenapa', 'yang', 'dan', 'atau', 'dengan',
    'untuk', 'dari', 'ke', 'di', 'pada', 'dalam', 'saya', 'kami', 'kita',
    'ternak', 'ayam', 'pakan', 'kandang', 'keuangan', 'data', 'informasi',
    'jumlah', 'total', 'daftar', 'laporan', 'status', 'bisa', 'dapat',
    'harus', 'akan', 'sudah', 'telah', 'sedang', 'tidak', 'belum',
    'optimasi', 'server', 'sistem', 'aplikasi'
];
```

### 2. Non-Farm Query Detection (AiPlanningService.php)

**New Feature:**
```php
private function isNonFarmQuery(string $message): bool
{
    // Non-farm technology keywords that should be redirected
    $nonFarmKeywords = [
        'linux', 'server', 'windows', 'computer', 'laptop', 'software',
        'programming', 'coding', 'database', 'network', 'internet',
        'website', 'browser', 'email', 'password', 'security',
        'backup', 'optimization', 'performance', 'system', 'hardware',
        'cpu', 'memory', 'disk', 'cloud', 'aws', 'docker'
    ];
    
    // Check for farm-related keywords first
    // If no farm context and has non-farm keywords, return true
}
```

### 3. Enhanced System Prompts (AiPlanningService.php)

**Before:**
```php
$prompt = $this->getLanguageInstruction($language);
$prompt .= "Give a brief, direct response. ";
```

**After:**
```php
$prompt = "SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";
$prompt .= $this->getLanguageInstruction($language);
$prompt .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
$prompt .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
$prompt .= "you MUST redirect them to ask about farm-related topics instead. ";
```

### 4. Improved Context Enforcement (AiChatService.php)

**Before:**
```php
// For general conversations, return message as-is for natural flow
if (empty($context) || strlen($context) < 50) {
    return $message;
}
```

**After:**
```php
// Always add farm management context even for general conversations
$farmPrompt = "You are an AI assistant for XiMoPet livestock management system. ";
$farmPrompt .= "Focus only on livestock management, poultry farming, and farm operations. ";
$farmPrompt .= "If users ask about non-farm topics, politely redirect them to farm-related questions. ";

return $farmPrompt . "\n\nUser: " . $message;
```

### 5. Enhanced OpenWebUI Service Context (OpenWebUIService.php)

**Before:**
```php
} else {
    // Default system message for general queries
    $messages[] = [
        'role' => 'system',
        'content' => 'You are a helpful assistant. Respond directly...'
    ];
}
```

**After:**
```php
} else {
    // Add livestock management system context for all queries
    $systemMessage = "You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";
    
    // CRITICAL: Enforce livestock management scope
    $systemMessage .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
    $systemMessage .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
    $systemMessage .= "you MUST redirect them to ask about farm-related topics instead. ";
}
```

### 6. Language Detection in AiChatService

**New Feature:**
```php
private function detectIndonesianLanguage(string $message): bool
{
    // Extended Indonesian keywords for better detection
    $indonesianKeywords = [
        'tampilkan', 'perusahaan', 'berapa', 'cara', 'bagaimana', 'apa', 'siapa',
        // ... full list of Indonesian keywords
    ];
    
    // Check if any Indonesian keyword is present
    foreach ($indonesianKeywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}
```

## Expected Results

After these fixes, when a user asks "cara optimasi linux server":

1. **Language Detection**: ✅ Detected as Indonesian (due to "cara" and "optimasi")
2. **Query Categorization**: ✅ Categorized as "help_guidance" 
3. **Non-Farm Detection**: ✅ Detected as non-farm query (due to "linux", "server", "optimasi")
4. **System Response**: ✅ Should redirect to farm management topics instead of providing Linux server advice

## Test Results

The AI should now respond with something like:
> "Saya adalah asisten AI untuk sistem manajemen ternak XiMoPet. Saya hanya dapat membantu dengan topik yang berkaitan dengan manajemen ternak, peternakan unggas, manajemen pakan, dan operasi peternakan. Untuk pertanyaan tentang server atau teknologi IT, silakan bertanya tentang topik peternakan seperti manajemen ayam, pakan, kandang, atau keuangan peternakan."

Instead of providing Linux server optimization advice.

## Files Modified

1. `app/Services/AiPlanningService.php`
   - Enhanced `detectLanguage()` method
   - Added `isNonFarmQuery()` method
   - Improved `buildOptimizedPrompt()` method
   - Updated query categorization

2. `app/Services/AiChatService.php`
   - Enhanced `buildPromptWithContext()` method
   - Added `detectIndonesianLanguage()` method
   - Improved data-related query handling
   - Fixed casual conversation context

3. `app/Services/OpenWebUIService.php`
   - Enhanced `formatMessages()` method
   - Added livestock management context enforcement
   - Improved scope limitation instructions
   - Updated all conversation types

## Verification

To verify the fixes are working:

1. Clear cache: `php artisan config:clear`
2. Test with the problematic query: "cara optimasi linux server"
3. Check logs for proper language detection
4. Verify AI response redirects to farm management topics

The system should now properly detect Indonesian language and enforce livestock management context for all queries.