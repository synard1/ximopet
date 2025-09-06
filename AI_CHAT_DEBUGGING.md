# AI Chat Debugging Commands

This documentation explains how to use the Artisan commands for debugging AI chat functionality in the XiMoPet system.

## Overview

The AI chat debugging feature provides a set of Artisan commands that allow developers to test and debug AI chat functionality directly from the command line, using the same services, methods, and helpers as the web-based chat interface.

## Available Commands

### 1. ai:chat-debug - Main Debugging Command

The primary command for testing AI chat functionality.

#### Usage
```bash
php artisan ai:chat-debug {message} [options]
```

#### Arguments
- `message`: The message to send to the AI (required)

#### Options
- `--user=ID`: User ID to simulate for authentication context (default: first user)
- `--provider=ollama|openwebui`: AI provider to use (default: from config)
- `--model=MODEL`: Specific AI model to use (default: from config)
- `--session=ID`: Session ID to continue an existing chat
- `--context-type=TYPE`: Type of context to build (default: 'general')
- `--no-context`: Disable context building
- `--debug`: Enable detailed logging
- `--verbose`: Enable verbose output
- `--interactive`: Enable interactive mode for continuous conversation
- `--inspect-context`: Show exactly what context is being sent
- `--analyze`: Analyze response quality

#### Examples
```bash
# Send a simple message
php artisan ai:chat-debug "Hello, how can you help me?"

# Send a message with specific user context
php artisan ai:chat-debug "Show me livestock data" --user=123

# Use a specific AI provider and model
php artisan ai:chat-debug "Analyze feed usage" --provider=ollama --model=llama2

# Continue an existing chat session
php artisan ai:chat-debug "Tell me more" --session=abc123

# Disable context building
php artisan ai:chat-debug "General question" --no-context

# Enable detailed logging
php artisan ai:chat-debug "Help me" --debug

# Enter interactive mode
php artisan ai:chat-debug "Hello" --interactive

# Inspect the context that would be sent
php artisan ai:chat-debug "Show livestock data" --inspect-context

# Analyze response quality
php artisan ai:chat-debug "Analyze this data" --analyze
```

### 2. ai:chat-status - Provider Status Check

Check the status and configuration of AI providers.

#### Usage
```bash
php artisan ai:chat-status [options]
```

#### Options
- `--provider=ollama|openwebui`: Check status of specific provider
- `--models`: List available models
- `--debug`: Enable detailed logging

#### Examples
```bash
# Check status of all providers
php artisan ai:chat-status

# Check status of specific provider
php artisan ai:chat-status --provider=ollama

# List available models
php artisan ai:chat-status --models

# Enable detailed logging
php artisan ai:chat-status --debug
```

### 3. ai:chat-sessions - Session Management

Manage AI chat sessions.

#### Usage
```bash
php artisan ai:chat-sessions [options]
```

#### Options
- `--list`: List chat sessions
- `--info=ID`: Show details for specific session
- `--delete=ID`: Delete specific session
- `--user=ID`: User ID to filter sessions (default: first user)
- `--limit=NUMBER`: Limit number of sessions to show (default: 20)
- `--all`: Show all sessions (not just active)
- `--debug`: Enable detailed logging

#### Examples
```bash
# List chat sessions
php artisan ai:chat-sessions --list

# Show details for specific session
php artisan ai:chat-sessions --info=abc123

# Delete a session
php artisan ai:chat-sessions --delete=abc123

# List sessions for specific user
php artisan ai:chat-sessions --list --user=123

# Show all sessions (including inactive)
php artisan ai:chat-sessions --list --all
```

### 4. ai:chat-benchmark - Performance Testing

Benchmark AI chat performance.

#### Usage
```bash
php artisan ai:chat-benchmark [options]
```

#### Options
- `--requests=NUMBER`: Number of requests to send (default: 5)
- `--concurrent=NUMBER`: Number of concurrent requests (default: 1)
- `--user=ID`: User ID to simulate
- `--provider=ollama|openwebui`: AI provider to use
- `--model=MODEL`: Specific AI model to use
- `--message=TEXT`: Test message to send (default: "Hello, how can you help me today?")
- `--debug`: Enable detailed logging

#### Examples
```bash
# Run basic benchmark
php artisan ai:chat-benchmark

# Run benchmark with 10 requests
php artisan ai:chat-benchmark --requests=10

# Run benchmark with specific message
php artisan ai:chat-benchmark --message="Analyze livestock data"

# Run benchmark with specific provider
php artisan ai:chat-benchmark --provider=ollama --model=llama2
```

## Logging and Debugging

All commands support detailed logging through the `--debug` option. When enabled, you'll see additional information about:

1. **Session Management**:
   - Session creation/retrieval
   - Session metadata
   - Activity updates

2. **Message Processing**:
   - Message saving
   - Processing time
   - Token count

3. **Context Building**:
   - Context type and filters
   - Data access permissions
   - Retrieved data summary
   - Context size

4. **AI Provider Interaction**:
   - Request payload
   - Response handling
   - Error conditions

5. **Performance Metrics**:
   - Processing time breakdown
   - Context building time
   - AI response time

## Security Considerations

1. **Authentication Context**:
   - Commands properly simulate user authentication
   - Company scoping is maintained
   - Permission validation is enforced

2. **Data Access**:
   - Same permission checking as web interface
   - Secure context building
   - No bypass of security mechanisms

3. **Command Access**:
   - Limited to developers/administrators with CLI access
   - No exposure of sensitive data in logs by default

## Best Practices

1. **Use the `--debug` option** when troubleshooting issues
2. **Test with different users** to verify permission handling
3. **Use `--inspect-context`** to understand what data is being sent to the AI
4. **Run benchmarks** periodically to monitor performance
5. **Check provider status** before running extensive tests

## Troubleshooting

### Common Issues

1. **"User not found" error**:
   - Ensure the specified user ID exists in the database
   - Use `--user` with a valid user ID or omit to use the first user

2. **Connection errors**:
   - Verify AI providers are running and accessible
   - Use `ai:chat-status` to check provider connectivity

3. **Permission errors**:
   - Ensure the test user has appropriate permissions
   - Check user roles and company scoping

### Getting Help

For additional help with any command, use the built-in help:
```bash
php artisan help ai:chat-debug
php artisan help ai:chat-status
php artisan help ai:chat-sessions
php artisan help ai:chat-benchmark
```