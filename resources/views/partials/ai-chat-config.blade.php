/**
 * AI Chat Configuration Script
 * This file is auto-generated from PHP configuration
 */
window.aiChatConfig = {
    autoOpen: false, // Forced to false to ensure bubble shows first
    defaultPosition: '{{ config('chat.ui.default_position', 'bottom-right') }}',
    theme: '{{ config('chat.ui.theme', 'auto') }}',
    enableSounds: {{ config('chat.ui.enable_sounds', true) ? 'true' : 'false' }},
    enableAnimations: {{ config('chat.ui.enable_animations', true) ? 'true' : 'false' }},
    showSessionsByDefault: {{ config('chat.ui.show_sessions_by_default', true) ? 'true' : 'false' }}
};

console.log('AI Chat configuration loaded', window.aiChatConfig);