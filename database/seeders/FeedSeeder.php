<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Support\Str;

class FeedSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $userId = $user?->id ?? Str::uuid()->toString();

        $companyId = config('seeder.current_company_id');
        if (!$companyId) {
            $this->command->warn('FeedSeeder: `company_id` not found, skipping.');
            return;
        }

        $feeds = [
            ['code' => 'FD001', 'name' => 'Feed Starter'],
            ['code' => 'FD002', 'name' => 'Feed Grower'],
        ];

        foreach ($feeds as $data) {
            Feed::firstOrCreate(
                [
                    'code' => $data['code'],
                    'company_id' => $companyId
                ],
                [
                    'name' => $data['name'],
                    'created_by' => $userId,
                    'company_id' => $companyId
                ]
            );
        }
    }
}
