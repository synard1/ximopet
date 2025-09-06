<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserChatSettingsV2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if table exists
        if (Schema::hasTable('user_chat_settings_v2')) {
            // Insert sample data if table is empty
            if (DB::table('user_chat_settings_v2')->count() == 0) {
                DB::table('user_chat_settings_v2')->insert([
                    [
                        'user_id' => 1,
                        'settings' => json_encode([
                            'provider' => 'openwebui',
                            'model' => 'llama3.2:3b',
                            'ui' => [
                                'theme' => 'auto',
                                'position' => 'bottom-right',
                                'size' => 'medium',
                                'auto_scroll' => true,
                                'show_timestamps' => true,
                                'enable_sounds' => true,
                                'enable_animations' => true
                            ],
                            'chat' => [
                                'auto_save' => true,
                                'max_context_messages' => 10,
                                'enable_markdown' => true,
                                'enable_code_highlighting' => true,
                                'typing_indicator' => true
                            ]
                        ]),
                        'version' => '2.0',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ]);
            }
        }
    }
}