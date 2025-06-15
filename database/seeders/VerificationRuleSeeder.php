<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VerificationRule;

class VerificationRuleSeeder extends Seeder
{
    public function run()
    {
        // Livestock Purchase Verification Rules
        VerificationRule::create([
            'name' => 'livestock_purchase_documents',
            'description' => 'Required documents for livestock purchase verification',
            'type' => 'document',
            'requirements' => [
                'required' => [
                    'invoice' => 'Invoice number must be provided',
                    'delivery_order' => 'Delivery order must be provided'
                ],
                'optional' => [
                    'contract' => 'Contract document if available'
                ]
            ],
            'is_active' => true
        ]);

        VerificationRule::create([
            'name' => 'livestock_purchase_data',
            'description' => 'Required data fields for livestock purchase verification',
            'type' => 'data',
            'requirements' => [
                'required' => [
                    'farm_id' => 'Farm must be selected',
                    'kandang_id' => 'Kandang must be selected',
                    'supplier_id' => 'Supplier must be selected',
                    'items' => 'At least one item must be added'
                ]
            ],
            'is_active' => true
        ]);

        VerificationRule::create([
            'name' => 'livestock_purchase_capacity',
            'description' => 'Kandang capacity verification rules',
            'type' => 'validation',
            'requirements' => [
                'rules' => [
                    'total_quantity' => 'Total quantity must not exceed kandang capacity',
                    'item_quantity' => 'Each item quantity must be greater than 0'
                ]
            ],
            'is_active' => true
        ]);
    }
}
