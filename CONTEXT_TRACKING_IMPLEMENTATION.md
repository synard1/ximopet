# Context Tracking Implementation

## Overview
Implementasi tracking context yang dikirim ke LLM untuk debugging dan analisis akurasi AI response.

## Perubahan yang Dilakukan

### 1. Modifikasi AiChatService.php

#### A. Penambahan Context Tracking di Metadata
- Menambahkan `context_sent_to_llm` ke metadata ChatMessage
- Menambahkan `user_query` untuk tracking query asli user
- Menggunakan `finalContextSentToLLM` yang berisi context lengkap yang dikirim ke LLM

#### B. Modifikasi Struktur Return Method
- `processPRROptimizedRequest()` sekarang return array dengan:
  - `response`: AI response
  - `final_context`: Context lengkap yang dikirim ke LLM
- `processTraditionalRequest()` sekarang return array dengan:
  - `response`: AI response  
  - `final_context`: Context lengkap yang dikirim ke LLM

#### C. Tracking Context di Main Flow
```php
// Store final context that will be sent to LLM
$finalContextSentToLLM = $context;

// Process request and get both response and final context
$aiResponseData = $this->processPRROptimizedRequest($message, $context, $session, $prrResult);
$aiResponse = $aiResponseData['response'];
$finalContextSentToLLM = $aiResponseData['final_context'] ?? $context;

// Save to metadata
$messageMetadata = [
    'provider' => $session->ai_provider,
    'model' => $session->model_name,
    'context_type' => $contextType,
    'context_data_size' => strlen($finalContextSentToLLM),
    'context_sent_to_llm' => $finalContextSentToLLM, // Full context for debugging
    'user_query' => $message // Original user query for tracking
];
```

## Struktur Metadata Baru

### Sebelum:
```json
{
    "provider": "openwebui",
    "model": "llama3.2:3b",
    "context_type": "livestock_management",
    "context_data_size": 281
}
```

### Sesudah:
```json
{
    "provider": "openwebui",
    "model": "llama3.2:3b",
    "context_type": "livestock_management",
    "context_data_size": 1234,
    "context_sent_to_llm": "You are a helpful AI assistant for XiMoPet...\n\nCurrent System Data:\n{\"companies\":{...}}",
    "user_query": "tampilkan semua daftar perusahaan"
}
```

## Manfaat Implementation

### 1. Debugging AI Response
- Dapat melihat exact context yang dikirim ke LLM
- Memudahkan identifikasi mengapa AI memberikan response tertentu
- Tracking user query asli untuk analisis pattern

### 2. Analisis Akurasi
- Membandingkan context dengan response untuk menilai akurasi
- Identifikasi apakah data sudah tersedia di context tapi AI tidak mendeteksi
- Optimasi prompt berdasarkan data tracking

### 3. Performance Monitoring
- Monitoring ukuran context yang dikirim
- Analisis efisiensi context building
- Identifikasi bottleneck di data processing

## Cara Testing

### 1. Manual Testing
1. Kirim pesan baru melalui chat interface
2. Check database ChatMessage untuk metadata terbaru
3. Verifikasi `context_sent_to_llm` dan `user_query` tersimpan

### 2. Database Query
```sql
SELECT 
    id, 
    JSON_EXTRACT(metadata, '$.user_query') as user_query,
    JSON_EXTRACT(metadata, '$.context_data_size') as context_size,
    SUBSTRING(JSON_EXTRACT(metadata, '$.context_sent_to_llm'), 1, 100) as context_preview,
    created_at
FROM chat_messages 
WHERE message_type = 'assistant' 
ORDER BY created_at DESC 
LIMIT 5;
```

### 3. Laravel Tinker
```php
$message = ChatMessage::where('message_type', 'assistant')
    ->latest()
    ->first();
    
dd($message->metadata);
```

## Troubleshooting

### Issue: Context tidak tersimpan
**Solusi:**
1. Pastikan ChatMessage model memiliki `metadata` di fillable
2. Verify `metadata` cast sebagai 'array' di model
3. Check database column `metadata` bertipe JSON/TEXT

### Issue: Context terlalu besar
**Solusi:**
1. Increase MySQL `max_allowed_packet`
2. Implement context truncation untuk metadata
3. Store context di file terpisah, simpan path di metadata

### Issue: Performance impact
**Solusi:**
1. Add config flag untuk enable/disable context tracking
2. Implement async context saving
3. Use queue untuk heavy metadata processing

## Next Steps

1. **Add Configuration**: Buat config untuk enable/disable context tracking
2. **Context Analysis Tool**: Buat tool untuk analisis context vs response accuracy
3. **Context Optimization**: Implement smart context truncation
4. **Monitoring Dashboard**: Dashboard untuk monitoring context tracking metrics

## Files Modified

- `app/Services/AiChatService.php` - Main implementation
- `test_context_tracking.php` - Test script (created)
- `CONTEXT_TRACKING_IMPLEMENTATION.md` - Documentation (this file)

## Backward Compatibility

Implementation ini backward compatible:
- Existing metadata structure tetap berfungsi
- Hanya menambah field baru, tidak mengubah yang existing
- Fallback mechanism jika context tracking gagal