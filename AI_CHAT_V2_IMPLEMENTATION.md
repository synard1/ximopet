# AI Chat V2 Implementation

This document outlines the implementation of the AI Chat V2 feature for the Ximopet application.

## Overview

The AI Chat V2 feature provides an enhanced chat interface with the following improvements over the original version:

1. **Simplified UI**: Cleaner, more modular interface with reduced complexity
2. **Improved Stability**: More resilient component architecture that minimizes breaking changes
3. **Enhanced Features**:
   - Settings panel with LLM provider and model selection
   - Chat history access and search functionality
   - Modular, scalable code structure

## Architecture

The implementation follows a modular architecture that separates concerns between UI components, business logic, and data access layers.

### Directory Structure

```
app/AiChatV2/
├── DTOs/                # Data Transfer Objects
├── Http/                # Controllers
├── Livewire/            # Livewire components
├── Models/              # Eloquent models
├── Providers/           # Service providers
└── Services/            # Business logic services

resources/views/ai-chat-v2/
├── index.blade.php      # Main chat interface
├── test.blade.php       # Test page
└── livewire/            # Livewire component views
    ├── chat-bubble.blade.php
    ├── chat-interface.blade.php
    ├── session-list.blade.php
    └── settings-panel.blade.php

config/
└── ai-chat-v2.php       # Configuration file

routes/
└── ai-chat-v2.php       # Route definitions

database/
├── migrations/
│   └── 2025_09_04_165632_create_user_chat_settings_v2_table.php
└── seeders/
    └── UserChatSettingsV2Seeder.php
```

## Key Components

### 1. Configuration

The feature is configured through `config/ai-chat-v2.php` which defines:
- Provider settings (OpenWebUI and Ollama)
- Default models
- UI settings
- History configuration

### 2. Services

#### ChatSessionService
Handles chat session operations:
- Creating new sessions
- Sending messages and getting AI responses
- Managing session messages
- Handling session archiving/restoration

#### SettingsService
Manages user settings:
- Getting/setting user preferences
- Provider and model management
- Resetting to defaults

### 3. Livewire Components

#### ChatBubble
Provides a floating chat bubble interface for quick interactions.

#### ChatInterface
Main chat interface with full conversation capabilities.

#### SessionList
Manages chat history with search and filtering.

#### SettingsPanel
Allows users to configure providers, models, and UI preferences.

### 4. Database

Uses the existing chat sessions and messages tables, with an additional `user_chat_settings_v2` table for user preferences.

## Routes

The feature is accessible through the following routes:
- `/ai-chat-v2` - Main chat interface
- `/ai-chat-v2/session/{sessionId}` - Specific chat session
- `/ai-chat-v2/test` - Test page

## Environment Variables

The following environment variables can be configured:
- `AI_CHAT_V2_ENABLED` - Enable/disable the feature
- `AI_CHAT_V2_DEFAULT_PROVIDER` - Default AI provider
- `AI_CHAT_V2_DEFAULT_MODEL` - Default model

## Testing

Feature tests are included in `tests/Feature/AiChatV2Test.php` to verify:
- Chat session creation
- Interface accessibility
- Authentication requirements
- Settings management

## Usage

To use the AI Chat V2 feature:
1. Navigate to `/ai-chat-v2` to access the main interface
2. Use the chat bubble for quick interactions
3. Switch between chat, sessions, and settings tabs as needed
4. Configure provider and model settings in the settings panel

## Future Improvements

Potential enhancements for future versions:
- Enhanced conversation context management
- Advanced search capabilities for chat history
- Integration with more AI providers
- Improved UI customization options