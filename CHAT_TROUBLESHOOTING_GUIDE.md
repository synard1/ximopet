# Chat System Troubleshooting Guide

## Issues Fixed in This Session

### 1. Chat History Not Persisting After Refresh
**Problem**: Sessions disappear after page refresh
**Root Causes Identified**:
- Sessions were only loaded when chat widget was explicitly opened
- No session restoration mechanism on page load
- Current session ID was not being maintained across page refreshes

**Solutions Implemented**:
- Added session loading during component initialization (not just on open)
- Added `restoreLastSession()` method to automatically restore the most recent session
- Enhanced session loading with better error handling and logging
- Modified `openChat()` to prefer existing sessions over creating new ones

### 2. Default Model Not Properly Selected
**Problem**: Model dropdown shows long Google model names instead of configured default
**Root Causes Identified**:
- API might not be returning expected model list
- No fallback mechanism when API is unavailable
- Configuration reference issues

**Solutions Implemented**:
- Enhanced model loading with proper fallback mechanism
- Added `getFallbackModels()` method with common model names
- Better error handling and logging for model selection
- Fixed configuration references (e.g., `chat.system.max_sessions_per_user`)

### 3. Configuration Issues
**Problem**: Missing configuration sections causing errors
**Solutions Implemented**:
- Added `sessions` configuration section to `config/chat.php`
- Fixed configuration references in `AiChatService.php`
- Updated `.env` with proper model configuration

## Testing Instructions

### 1. Test Session Persistence
1. Open the chat widget
2. Send a message to create a session
3. Refresh the page
4. Open the chat widget again
5. **Expected**: Previous session should be automatically restored with message history

### 2. Test Default Model Selection
1. Open chat settings
2. Check the Model dropdown
3. **Expected**: Should show `qwen2.5:3b` as selected (or first available model)
4. **Not Expected**: Should not show long Google model names

### 3. Test Session Management
1. Create multiple chat sessions by clicking "New Chat"
2. Switch between sessions using the history panel
3. Delete individual sessions
4. **Expected**: All operations should work smoothly

### 4. Debug Information
- A temporary debug button has been added to the settings panel
- Click it to log current state to application logs
- Check `storage/logs/laravel.log` for detailed debug information

## Configuration Files Updated

### `.env` Changes
```env
# Updated model configuration
OPENWEBUI_DEFAULT_MODEL="qwen2.5:3b"

# Added session management
CHAT_SESSION_RETENTION_DAYS=30
CHAT_MAX_SESSIONS_PER_USER=10
CHAT_MAX_SESSIONS_DISPLAY=10
CHAT_ENABLE_SESSION_DELETE=true
CHAT_CONFIRM_SESSION_DELETE=true
CHAT_ENABLE_SESSION_CLEANUP=true
```

### `config/chat.php` Changes
- Added `sessions` configuration section
- Enhanced `system` and `ui` sections with environment variables

## Code Changes Made

### `app/Livewire/AiChatWidget.php`
- Added `restoreLastSession()` method
- Enhanced `initializeComponent()` to load sessions on mount
- Improved `loadAvailableModels()` with fallback mechanism
- Added `getFallbackModels()` method
- Enhanced error handling and logging throughout
- Added `debugState()` method for troubleshooting

### `app/Services/AiChatService.php`
- Fixed configuration references
- Added `clearAllUserSessions()` method
- Enhanced error handling

### `resources/views/livewire/ai-chat-widget.blade.php`
- Added temporary debug button
- Enhanced session panel display

## Common Issues and Solutions

### Issue: "No models available"
**Solution**: Check if OpenWebUI service is accessible at the configured URL

### Issue: "Sessions not showing"
**Solution**: 
1. Check database connection
2. Verify user authentication
3. Check company scoping (if applicable)
4. Use debug button to see what's happening

### Issue: "Models showing wrong names"
**Solution**: 
1. Check API response format
2. Verify OpenWebUI API compatibility
3. Fallback models should still work

## Next Steps if Issues Persist

1. **Check Logs**: Always check `storage/logs/laravel.log` for detailed error information
2. **Use Debug Button**: Click the debug button in settings to get current state
3. **Verify Database**: Check if sessions are actually being created in the database
4. **API Testing**: Test OpenWebUI API directly to verify model availability
5. **Configuration**: Double-check all environment variables are properly set

## Cleanup

After testing is complete, remove the debug button and method:
1. Remove `debugState()` method from `AiChatWidget.php`
2. Remove debug button from the Blade template