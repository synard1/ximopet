# OllamaChatCommand Streaming Feature

## Tanggal: 2024-12-19

## Status: ✅ Completed

## Fitur Streaming Baru

### 🌊 **Streaming Mode**

Command sekarang mendukung streaming response seperti ChatGPT/Gemini dengan response yang muncul secara real-time.

### 📋 **Command Usage**

#### Streaming Mode

```bash
# Basic streaming
php artisan ollama:chat "Your prompt here" --stream

# Streaming dengan model spesifik
php artisan ollama:chat "Your prompt here" --model=llama2:latest --stream

# Streaming dengan debug mode
php artisan ollama:chat "Your prompt here" --model=llama2:latest --stream --debug
```

#### Non-Streaming Mode (Default)

```bash
# Traditional mode (tanpa --stream)
php artisan ollama:chat "Your prompt here"
```

### 🔧 **Technical Implementation**

#### Request Format

```json
{
    "model": "llama2:latest",
    "prompt": "Your prompt here",
    "stream": true,
    "options": {
        "num_thread": 56,
        "temperature": 0.7,
        "top_p": 0.9
    }
}
```

#### Streaming Response Format

Ollama mengirim response dalam format JSON lines:

```json
{"response": "Hello", "done": false}
{"response": " there", "done": false}
{"response": "!", "done": true}
```

### 🎯 **Features**

#### 1. Real-time Token Display

-   ✅ Tokens muncul secara real-time saat diterima
-   ✅ Tidak perlu menunggu response selesai
-   ✅ Pengalaman seperti ChatGPT/Gemini

#### 2. Progress Tracking

-   ✅ Token counter
-   ✅ Duration tracking
-   ✅ Character/word/line statistics

#### 3. Error Handling

-   ✅ Timeout handling
-   ✅ Connection error handling
-   ✅ Premature connection close handling
-   ✅ Model suggestion for unavailable models

#### 4. Debug Information

-   ✅ Request payload logging
-   ✅ Response statistics
-   ✅ Performance metrics

#### 5. Smart Model Suggestions

-   ✅ Auto-suggest similar models
-   ✅ Popular model recommendations
-   ✅ Intelligent name matching

### 📊 **Output Examples**

#### Streaming Output

```
🚀 Starting Ollama Chat Command
📝 Prompt: What is AI?
🤖 Model: llama2:latest
🌐 Server URL: http://172.16.15.6:11434
🔍 Checking server connection...
🔍 Checking model availability...
📤 Sending request to Ollama...
🌊 Streaming mode enabled - Response will appear in real-time

🤖 Ollama Response (Streaming):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Artificial Intelligence (AI) is a branch of computer science that aims to create intelligent machines that can perform tasks that typically require human intelligence...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Streaming completed successfully!
📊 Streaming Statistics:
   • Duration: 15.23 seconds
   • Tokens received: 45
   • Characters: 1250
   • Words: 180
   • Lines: 15
```

#### Model Not Found Error

```
❌ Model 'qwen2.5vl:latest' is not available on the server
💡 Available models: deepseek-coder:latest, codegemma:latest, llama2:latest, llama3.1:latest, qwen3:8b, gemma3n:latest, deepseek-r1:latest
💡 Did you mean: --model=qwen3:8b
💡 Or try one of these popular models:
   • llama2:latest
   • llama3.1:latest
   • qwen3:8b
   • gemma3n:latest
   • deepseek-coder:latest
```

#### Timeout Error

```
❌ cURL Error: Operation timed out after 120012 milliseconds
⏰ Streaming timeout - Response was too slow
💡 Try using a smaller model or shorter prompt
```

### ⚙️ **Configuration**

#### Environment Variables

-   `OLLAMA_HOST` - Server URL (default: http://172.16.15.6:11434)
-   `OLLAMA_NUM_THREADS` - Number of threads (default: 16)
-   `OLLAMA_TEMPERATURE` - Temperature setting (default: 0.7) - Currently disabled
-   `OLLAMA_TOP_P` - Top-p setting (default: 0.9) - Currently disabled

#### Timeout Settings

-   **Debug mode**: 120 seconds
-   **Production mode**: 300 seconds
-   **Connection timeout**: 30 seconds

### 🔍 **Technical Details**

#### cURL Configuration

```php
CURLOPT_TIMEOUT => $timeout,
CURLOPT_CONNECTTIMEOUT => 30,
CURLOPT_TCP_KEEPALIVE => 1,
CURLOPT_TCP_KEEPIDLE => 60,
CURLOPT_TCP_KEEPINTVL => 60,
```

#### Write Function

-   Parses JSON lines from streaming response
-   Extracts tokens and displays them immediately
-   Tracks completion status
-   Handles connection errors gracefully

### 🚀 **Performance Benefits**

#### 1. User Experience

-   ✅ Immediate feedback
-   ✅ No waiting for complete response
-   ✅ Interactive feel like modern AI chat

#### 2. Resource Management

-   ✅ Lower memory usage
-   ✅ Better timeout handling
-   ✅ Connection keep-alive

#### 3. Debugging

-   ✅ Real-time monitoring
-   ✅ Detailed statistics
-   ✅ Error tracking

### 🐛 **Known Issues & Solutions**

#### 1. Connection Timeout

**Issue**: `Operation timed out after 120012 milliseconds`
**Solution**:

-   Use smaller models
-   Shorter prompts
-   Check server performance

#### 2. Premature Connection Close

**Issue**: `transfer closed with outstanding read data remaining`
**Solution**:

-   This is normal for streaming
-   Response may be complete
-   Check if full response received

#### 3. Model Loading Time

**Issue**: Slow initial response
**Solution**:

-   Use pre-loaded models
-   Check server resources
-   Consider model size

#### 4. Model Not Found

**Issue**: `Model 'qwen2.5vl:latest' is not available on the server`
**Solution**:

-   Use suggested similar model (e.g., qwen3:8b)
-   Try popular models from the list
-   Check available models with `--debug` flag

### 📈 **Future Enhancements**

-   [ ] Add conversation history
-   [ ] Support for multiple models comparison
-   [ ] Add response caching
-   [ ] Implement retry mechanism
-   [ ] Add streaming progress bar
-   [ ] Support for custom streaming formats
-   [ ] Add response filtering
-   [ ] Implement streaming to file

### 🧪 **Testing Results**

-   ✅ Streaming works with llama2:latest
-   ✅ Real-time token display functional
-   ✅ Error handling works correctly
-   ✅ Statistics tracking accurate
-   ✅ Debug mode provides detailed info
-   ✅ Timeout handling robust
-   ✅ Connection error handling works

### 📝 **Usage Tips**

1. **For quick responses**: Use `--stream` with short prompts
2. **For debugging**: Use `--debug` to see detailed logs
3. **For large models**: Be patient with initial loading
4. **For stability**: Use non-streaming mode for critical tasks
5. **For performance**: Monitor token count and duration

### 🔗 **Related Files**

-   `app/Console/Commands/OllamaChatCommand.php` - Main command file
-   `logs/commands/ollama-chat-refactor.md` - Previous refactoring log
-   Environment configuration in `.env`
