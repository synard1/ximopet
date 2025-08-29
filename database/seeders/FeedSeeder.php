<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feed;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Support\Str;
use Database\Seeders\Helpers\FeedHelper;

class FeedSeeder extends Seeder
{
    public function run()
    {
        // Get company ID from config (for direct seeding)
        $companyId = config('seeder.current_company_id');

        if (!$companyId) {
            // Fallback: try to get from first user
            $user = User::first();
            $companyId = $user?->company_id;

            if (!$companyId) {
                $this->command->error('FeedSeeder: No company_id found in config or user table.');
                return;
            }
        }

        // Get user ID
        $userId = User::where('company_id', $companyId)->first()?->id
            ?? User::first()?->id
            ?? Str::uuid()->toString();

        $unitKg = Unit::where('name', 'KG')->first();
        $unitSak = Unit::where('name', 'SAK')->first();

        $feeds = [
            ['code' => 'FD001', 'name' => 'SP10'],
            ['code' => 'FD002', 'name' => 'S11'],
            ['code' => 'FD003', 'name' => 'S12'],
        ];

        foreach ($feeds as $data) {
            FeedHelper::createFeedWithConversions(
                $data['code'],
                $data['name'],
                $unitKg,
                $unitSak,
                $userId,
                $companyId
            );
        }
    }
}
