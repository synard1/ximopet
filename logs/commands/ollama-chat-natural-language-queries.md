# OllamaChatCommand Natural Language Query Detection

## Tanggal: 2024-12-19

## Status: ✅ Completed

## Fitur Baru: Natural Language Query Detection

### 🎯 **Overview**

Command sekarang mendukung auto-detection natural language queries dan mengkonversinya secara otomatis ke complex queries, sehingga user tidak perlu lagi menggunakan syntax yang kompleks.

### 🔍 **Auto-Detection Features**

#### 1. **Purchase-Related Queries**

```bash
# Time-based queries
"ada brapa pembelian selama bulan mei 2025"
"berapa pembelian tahun 2024"
"pembelian terbaru"

# Auto-converts to:
feed date>=2025-05-01 AND date<=2025-05-31
feed date>=2024-01-01 AND date<=2024-12-31
feed status=completed
```

#### 2. **Livestock-Related Queries**

```bash
# Status queries
"berapa ternak yang aktif saat ini"
"ternak yang sudah selesai"
"livestock yang berjalan"

# Auto-converts to:
livestock status=active
livestock status=completed
livestock status=active
```

#### 3. **Analytics Queries**

```bash
# Analytics queries
"berikan laporan analisis bisnis"
"statistik performa"
"laporan kinerja"

# Auto-converts to:
analytics overview
analytics performance
analytics overview
```

### 📋 **Supported Natural Language Patterns**

#### 1. **Time-Based Detection**

```bash
# Month patterns
"bulan januari 2025" → date>=2025-01-01 AND date<=2025-01-31
"bulan mei 2024" → date>=2024-05-01 AND date<=2024-05-31
"month may 2025" → date>=2025-05-01 AND date<=2025-05-31

# Year patterns
"tahun 2024" → date>=2024-01-01 AND date<=2024-12-31
"year 2025" → date>=2025-01-01 AND date<=2025-12-31
```

#### 2. **Status-Based Detection**

```bash
# Active status
"aktif" → status=active
"active" → status=active
"berjalan" → status=active
"running" → status=active

# Completed status
"selesai" → status=completed
"completed" → status=completed
"finished" → status=completed
```

#### 3. **Recent/Current Detection**

```bash
# Recent patterns
"terbaru" → status=completed
"recent" → status=completed
"latest" → status=completed
"terakhir" → status=completed
"last" → status=completed
```

### 🧪 **Testing Results**

#### ✅ **Purchase Queries Tests**

1. **Month query**: "ada brapa pembelian selama bulan mei 2025" ✅

    - Auto-detected: `feed date>=2025-05-01 AND date<=2025-05-31`
    - Result: Found 2 records

2. **Year query**: "pembelian tahun 2024" ✅

    - Auto-detected: `feed date>=2024-01-01 AND date<=2024-12-31`

3. **Recent query**: "pembelian terbaru" ✅
    - Auto-detected: `feed status=completed`

#### ✅ **Livestock Queries Tests**

1. **Active query**: "berapa ternak yang aktif saat ini" ✅

    - Auto-detected: `livestock status=active`
    - Result: Found 1 record with 12000 units

2. **Completed query**: "ternak yang sudah selesai" ✅
    - Auto-detected: `livestock status=completed`

#### ✅ **Analytics Queries Tests**

1. **Analytics query**: "berikan laporan analisis bisnis" ✅

    - Auto-detected: `analytics overview`
    - Result: Comprehensive business analytics

2. **Performance query**: "statistik performa" ✅
    - Auto-detected: `analytics performance`

### 🔧 **Technical Implementation**

#### 1. **Natural Language Detection Engine**

```php
private function detectNaturalLanguageQuery(string $prompt, bool $debug): ?string
{
    $promptLower = strtolower($prompt);

    // Detect purchase-related queries
    if (preg_match('/(pembelian|purchase|beli|buy)/i', $promptLower)) {
        // Time-based detection
        if (preg_match('/(bulan|month)\s+(januari|februari|...)\s+(\d{4})/i', $promptLower, $matches)) {
            $month = $this->convertMonthToNumber($matches[2]);
            $year = $matches[3];
            return "feed date>={$year}-{$month}-01 AND date<={$year}-{$month}-31";
        }

        // Year-based detection
        if (preg_match('/(tahun|year)\s+(\d{4})/i', $promptLower, $matches)) {
            $year = $matches[2];
            return "feed date>={$year}-01-01 AND date<={$year}-12-31";
        }

        return "feed status=completed";
    }

    // Detect livestock-related queries
    if (preg_match('/(ternak|livestock|ayam|chicken)/i', $promptLower)) {
        if (preg_match('/(aktif|active|berjalan|running)/i', $promptLower)) {
            return "livestock status=active";
        }
        return "livestock status=active";
    }

    // Detect analytics queries
    if (preg_match('/(analisis|analytics|laporan|report)/i', $promptLower)) {
        return "analytics overview";
    }

    return null;
}
```

#### 2. **Month Conversion**

```php
private function convertMonthToNumber(string $month): string
{
    $months = [
        'januari' => '01', 'jan' => '01',
        'februari' => '02', 'feb' => '02',
        'maret' => '03', 'mar' => '03',
        'april' => '04', 'apr' => '04',
        'mei' => '05', 'may' => '05',
        'juni' => '06', 'jun' => '06',
        'juli' => '07', 'jul' => '07',
        'agustus' => '08', 'aug' => '08',
        'september' => '09', 'sep' => '09',
        'oktober' => '10', 'oct' => '10',
        'november' => '11', 'nov' => '11',
        'desember' => '12', 'dec' => '12'
    ];

    $monthLower = strtolower($month);
    return $months[$monthLower] ?? '01';
}
```

#### 3. **Auto-Detection Integration**

```php
// Auto-detect natural language queries and convert to complex queries
if (empty($query) && empty($queryType) && empty($context) && empty($contextType)) {
    $autoQuery = $this->detectNaturalLanguageQuery($prompt, $debug);
    if (!empty($autoQuery)) {
        $this->logInfo("🔍 Auto-detected query: {$autoQuery}", $debug);
        $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $context, $contextType, $contextLimit, $autoQuery, $queryType, $useCache, $cacheTtl, $noCache, $debug);
    }
}
```

### 🚀 **Benefits**

#### 1. **User Experience**

-   No need to learn complex query syntax
-   Natural language input
-   Automatic query conversion
-   Intuitive interaction

#### 2. **Developer Experience**

-   Easy to extend patterns
-   Flexible detection rules
-   Comprehensive error handling
-   Debug information

#### 3. **Performance**

-   Fast pattern matching
-   Efficient query conversion
-   Caching support
-   Minimal overhead

### 📈 **Usage Examples**

#### Example 1: Purchase Queries

```bash
# Natural language
php artisan ollama:chat "ada brapa pembelian selama bulan mei 2025" --model=gemma2:2b --cache --debug

# Auto-converts to complex query
feed date>=2025-05-01 AND date<=2025-05-31
```

#### Example 2: Livestock Queries

```bash
# Natural language
php artisan ollama:chat "berapa ternak yang aktif saat ini" --model=gemma2:2b --cache --debug

# Auto-converts to complex query
livestock status=active
```

#### Example 3: Analytics Queries

```bash
# Natural language
php artisan ollama:chat "berikan laporan analisis bisnis" --model=gemma2:2b --cache --debug

# Auto-converts to complex query
analytics overview
```

### 🐛 **Known Issues & Solutions**

#### 1. **Ambiguous Queries**

**Issue**: Some natural language queries might be ambiguous
**Solution**:

-   Use more specific keywords
-   Provide fallback options
-   Add context hints

#### 2. **Language Variations**

**Issue**: Different ways to express the same query
**Solution**:

-   Support multiple synonyms
-   Extend pattern matching
-   Add language detection

#### 3. **Complex Queries**

**Issue**: Very complex natural language queries
**Solution**:

-   Use explicit `--query` parameter
-   Break down into simpler queries
-   Use raw SQL for complex cases

### 🔗 **Related Files**

-   `app/Console/Commands/OllamaChatCommand.php` - Main command with natural language detection
-   `logs/commands/ollama-chat-complex-queries-caching.md` - Complex queries documentation
-   `logs/commands/ollama-chat-context-data.md` - Context data documentation
-   `logs/commands/ollama-chat-streaming.md` - Streaming documentation

### 🎯 **Impact**

#### Before

-   Required complex query syntax
-   Manual query construction
-   Learning curve for users
-   Limited accessibility

#### After

-   Natural language input
-   Automatic query detection
-   User-friendly interaction
-   Enhanced accessibility
-   Improved user experience

### 📈 **Future Enhancements**

-   [ ] Advanced language processing
-   [ ] Multi-language support
-   [ ] Context-aware detection
-   [ ] Query suggestions
-   [ ] Voice input support
-   [ ] Query templates
-   [ ] Machine learning integration
-   [ ] Query optimization
