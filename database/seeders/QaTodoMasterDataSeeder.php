<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QaTodoList;
use App\Models\User;
use Illuminate\Support\Str;

class QaTodoMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the assigned users
        $mhdIqbal = User::where('name', 'Mhd Iqbal Syahputra')->first();
        $novaIndah = User::where('name', 'Nova Indah')->first();

        if (!$mhdIqbal || !$novaIndah) {
            $this->command->info('Skipping QaTodoMasterDataSeeder: Assigned users not found. Please create Mhd Iqbal Syahputra and Nova Indah users first.');
            return;
        }

        $masterDataItems = [
            'Farm',
            'Kandang',
            'Supplier',
            'Pembeli',
            'Ekspedisi',
            'Pakan',
            'Pekerja',
        ];

        $environments = ['dev', 'staging'];

        $this->command->info('Seeding QA Todo Master Data...');

        foreach ($environments as $environment) {
            // Determine assigned user based on environment
            if ($environment === 'staging') {
                $assignedToId = $novaIndah->id;
            } elseif ($environment === 'dev') {
                $assignedToId = $mhdIqbal->id;
            } else {
                // Skip if environment is neither dev nor staging (shouldn't happen with current setup)
                continue;
            }

            foreach ($masterDataItems as $item) {
                QaTodoList::create([
                    'id' => (string) Str::uuid(),
                    'module_name' => 'Master Data',
                    'feature_name' => $item,
                    'description' => "QA task for Master Data: {$item} in {$environment} environment.",
                    'environment' => $environment,
                    'priority' => 'medium', // Default priority
                    'status' => 'pending', // Default status
                    'assigned_to' => $assignedToId,
                    'created_by' => $mhdIqbal->id, // Assuming Mhd Iqbal runs the seeder or is the system user
                    'due_date' => now()->addDays(rand(7, 30)), // Random due date in the next 7-30 days
                    'notes' => "Auto-generated todo for {$item} in {$environment}.",
                ]);
                $this->command->info("Created todo for {$item} in {$environment}, assigned to {" . ($environment === 'staging' ? $novaIndah->name : $mhdIqbal->name) . "}.");
            }
        }

        $this->command->info('QA Todo Master Data seeding completed.');
    }
}
