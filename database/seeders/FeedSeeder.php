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
        $user = User::first();
        $userId = $user?->id ?? Str::uuid()->toString();

        // Ambil company_id dari model User (user pertama)
        $companyId = $user?->company_id ?? null;
        if (!$companyId) {
            $this->command->warn('FeedSeeder: `company_id` not found, skipping.');
            return;
        }

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
