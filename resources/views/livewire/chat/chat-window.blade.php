<?php
    // Chat window template will be moved here
?>

<div class="chat-window-container"
     x-show="isOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">
     
    <!-- Chat window content will be moved here -->
    <div class="chat-window card">
        <!-- Existing chat window content -->
    </div>
</div>

<style>
.chat-window-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
}

.chat-window {
    width: 420px;
    height: 550px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border-radius: 12px;
    overflow: hidden;
    background: white;
}

@media (max-width: 480px) {
    .chat-window {
        width: calc(100vw - 40px);
        height: calc(100vh - 100px);
        max-width: 420px;
    }
}
</style>
