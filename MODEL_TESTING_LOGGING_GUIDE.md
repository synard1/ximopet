# Model Testing Logging Guide

## Overview
Script `test_model_comparison.php` sekarang dilengkapi dengan sistem logging komprehensif yang menyimpan semua aktivitas testing ke dalam log file Laravel untuk kemudahan debugging dan monitoring.

## Log Configuration

### Log Channel
- **Channel Name**: `model_testing`
- **Driver**: `daily` (rotasi harian)
- **Path**: `storage/logs/model-testing-YYYY-MM-DD.log`
- **Level**: `debug` (dapat dikonfigurasi via `LOG_MODEL_TESTING_LEVEL`)
- **Retention**: 30 hari
- **Permission**: 0664

### Environment Variable
Tambahkan ke `.env` untuk mengatur level logging:
```
LOG_MODEL_TESTING_LEVEL=debug
```

## Log Structure

### 1. Session Start
```json
{
  "level": "info",
  "message": "Model testing session started",
  "context": {
    "session_id": "test_session_xxxxx",
    "test_query": "tampilkan semua data dari perusahaan Demo Company",
    "user_id": "9fcbe4b3-7d32-4b66-9286-1e1cae1be21d",
    "models_to_test": ["gemma3:1b", "gemma3:270m", ...],
    "timestamp": "2025-09-06T15:10:18.997550Z"
  }
}
```

### 2. Individual Model Testing
```json
{
  "level": "info",
  "message": "Model test completed",
  "context": {
    "session_id": "test_session_xxxxx",
    "model": "qwen3:0.6b",
    "success": true,
    "processing_time_ms": 12576.08,
    "response_length": 743,
    "contains_company_data": true,
    "contains_farm_data": true,
    "error": null,
    "timestamp": "2025-09-06T15:10:18.997550Z"
  }
}
```

### 3. Debug Information
- Context data retrieval
- PRR execution status
- OpenWebUI request details
- Response analysis

### 4. Error Logging
```json
{
  "level": "error",
  "message": "Model test failed",
  "context": {
    "session_id": "test_session_xxxxx",
    "model": "model_name",
    "error": "Error message",
    "processing_time_ms": 1000,
    "stack_trace": "..."
  }
}
```

### 5. Session Summary
```json
{
  "level": "info",
  "message": "Model testing session completed",
  "context": {
    "session_id": "test_session_xxxxx",
    "total_models_tested": 6,
    "successful_models": 6,
    "failed_models": 0,
    "best_model": "qwen3:0.6b",
    "best_model_score": 150.5,
    "successful_models_details": [...],
    "failed_models_details": [...]
  }
}
```

### 6. Performance Statistics
```json
{
  "level": "info",
  "message": "Performance statistics",
  "context": {
    "session_id": "test_session_xxxxx",
    "avg_processing_time_ms": 24885.34,
    "min_processing_time_ms": 3484.13,
    "max_processing_time_ms": 55452.45,
    "avg_response_length": 365.67,
    "models_with_company_data": 5,
    "models_with_farm_data": 1
  }
}
```

## Benefits

### 1. Debugging
- Trace setiap langkah testing process
- Identifikasi bottleneck performa
- Analisis error patterns
- Monitor data completeness

### 2. Monitoring
- Track model performance over time
- Compare different testing sessions
- Identify trending issues
- Performance benchmarking

### 3. Audit Trail
- Complete record of all testing activities
- Session-based tracking
- Timestamp untuk setiap event
- User dan query tracking

## Usage

### Running Tests
```bash
php test_model_comparison.php
```

### Viewing Logs
```bash
# View today's log
tail -f storage/logs/model-testing-$(date +%Y-%m-%d).log

# View specific session
grep "test_session_xxxxx" storage/logs/model-testing-*.log

# View only errors
grep '"level":"error"' storage/logs/model-testing-*.log

# View performance stats
grep "Performance statistics" storage/logs/model-testing-*.log
```

### Log Analysis
```bash
# Count successful vs failed tests
grep -c '"success":true' storage/logs/model-testing-*.log
grep -c '"success":false' storage/logs/model-testing-*.log

# Find best performing models
grep "best_model" storage/logs/model-testing-*.log

# Monitor processing times
grep "processing_time_ms" storage/logs/model-testing-*.log
```

## Log Rotation

Log files akan otomatis di-rotate setiap hari dan disimpan selama 30 hari. File lama akan otomatis dihapus untuk menghemat disk space.

## Troubleshooting

### Log File Tidak Terbuat
1. Periksa permission folder `storage/logs/`
2. Pastikan log channel `model_testing` sudah dikonfigurasi
3. Periksa level logging di environment

### Log Kosong
1. Periksa `LOG_MODEL_TESTING_LEVEL` di `.env`
2. Pastikan Laravel logging service berjalan
3. Periksa disk space yang tersedia

### Performance Impact
Logging level `debug` akan mencatat semua detail. Untuk production, gunakan level `info` atau `warning` untuk mengurangi overhead.

## Best Practices

1. **Regular Monitoring**: Review log files secara berkala
2. **Error Analysis**: Investigasi pattern error yang berulang
3. **Performance Tracking**: Monitor trend performa model
4. **Disk Management**: Monitor ukuran log files
5. **Log Level**: Sesuaikan level logging dengan kebutuhan

---

**Note**: Semua log menggunakan format JSON untuk kemudahan parsing dan analysis dengan tools seperti jq, ELK stack, atau log analysis tools lainnya.