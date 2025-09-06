<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiChatService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;

class TestCasualDetection extends Command
{
    protected $signature = 'test:casual-detection';
    protected $description = 'Test casual conversation detection logic';

    public function handle()
    {
        $user = User::first();
        if (!$user) {
            $this->error('No user found');
            return 1;
        }

        Auth::login($user);

        $this->info("Testing casual conversation detection logic");

        $chatService = app(AiChatService::class);

        // Use reflection to access private method
        $reflection = new ReflectionClass($chatService);
        $method = $reflection->getMethod('isSimpleCasualConversation');
        $method->setAccessible(true);

        // Test cases
        $testCases = [
            // Should be detected as casual
            'hi' => true,
            'hello' => true,
            'how are you?' => true,
            'thanks' => true,
            'bye' => true,
            'hai' => true,
            'terima kasih' => true,
            // Removed 'ok' and 'oke' as they are too common in normal conversation

            // Should NOT be detected as casual
            'berapa ternak ayam?' => false,
            'tampilkan data keuangan' => false,
            'how many chickens do we have?' => false,
            'show me the financial report' => false,
            'status kandang hari ini' => false,
            'berapa pakan yang tersisa?' => false,
        ];

        $this->info("\n=== Testing Casual Conversation Detection ===");

        foreach ($testCases as $message => $expectedCasual) {
            $isCasual = $method->invoke($chatService, $message);

            $status = $isCasual === $expectedCasual ? '✅' : '❌';
            $detection = $isCasual ? 'CASUAL' : 'BUSINESS';
            $expected = $expectedCasual ? 'CASUAL' : 'BUSINESS';

            $this->line("{$status} '{$message}' → Detected: {$detection} | Expected: {$expected}");

            if ($isCasual !== $expectedCasual) {
                $this->warn("   ⚠️ Detection mismatch!");
            }
        }

        // Test prompt building
        $this->info("\n=== Testing Prompt Building ===");

        $buildMethod = $reflection->getMethod('buildPromptWithContext');
        $buildMethod->setAccessible(true);

        // Create a real session for testing
        $session = new \App\Models\ChatSession();
        $session->id = 'test-session';

        $casualMessage = 'hi';
        $businessMessage = 'berapa ternak?';
        $context = 'Farm management context data here...';

        $casualPrompt = $buildMethod->invoke($chatService, $casualMessage, $context, $session);
        $businessPrompt = $buildMethod->invoke($chatService, $businessMessage, $context, $session);

        $this->info("Casual message prompt: '" . $casualPrompt . "'");
        $this->info("Casual message prompt length: " . strlen($casualPrompt));
        $this->info("Business message prompt length: " . strlen($businessPrompt));

        if (strlen($casualPrompt) < strlen($businessPrompt)) {
            $this->info("✅ Casual prompts are shorter (as expected)");
        } else {
            $this->warn("❌ Casual prompts should be shorter than business prompts");
        }

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}
