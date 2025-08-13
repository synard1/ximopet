<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company; // sesuaikan namespace model-mu
use Illuminate\Support\Str;

class SystemCompanySeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan idempotent (tidak dobel kalau di-run berulang)
        $code = config('app.template_company_code', env('TEMPLATE_COMPANY_CODE', 'SYSTEM'));

        $payload = [
            'name'      => 'System Template',
            'type'      => 'system',
            'status'    => 'active',
            'is_locked' => true,
            'metadata'  => [
                'description' => 'Default template company for seeding master/template data',
                'created_by'  => 'seeder',
            ],
        ];

        // Jika kamu ingin memaksa UUID tetap, boleh generate sekali:
        $existing = Company::where('code', $code)->first();

        if ($existing) {
            $existing->update($payload);
        } else {
            // generate manual UUID agar konsisten lintas environment jika perlu
            Company::create(array_merge($payload, [
                'id'   => (string) Str::uuid(),
                'code' => $code,
            ]));
        }
    }
}
