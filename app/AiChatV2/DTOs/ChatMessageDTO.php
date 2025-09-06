<?php

namespace App\AiChatV2\DTOs;

class ChatMessageDTO
{
    public string $sessionId;
    public string $userId;
    public string $message;
    public string $provider;
    public string $model;

    public function __construct(array $data)
    {
        $this->sessionId = $data['sessionId'];
        $this->userId = $data['userId'];
        $this->message = $data['message'];
        $this->provider = $data['provider'] ?? 'openwebui';
        $this->model = $data['model'] ?? 'llama3.2:3b';
    }

    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'userId' => $this->userId,
            'message' => $this->message,
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }
}