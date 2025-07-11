<?php

namespace App\Config;

class SupplyUsageBypassConfig
{
    // Default config (future-proof, bisa di-migrate ke DB)
    public static function getConfig(): array
    {
        return [
            'allow_direct_to_in_process' => true,
            'allow_direct_to_completed' => true,
            'skip_approval_for_operator' => true,
            'skip_approval_for_supervisor' => false,
            'allow_operator_status_changes' => [
                'draft' => ['pending', 'cancelled'],
                'pending' => ['in_process', 'cancelled'],
                'in_process' => ['completed', 'partially_used', 'cancelled']
            ],
            'allow_supervisor_status_changes' => [
                'draft' => ['pending', 'in_process', 'cancelled'],
                'pending' => ['in_process', 'under_review', 'cancelled'],
                'in_process' => ['completed', 'partially_used', 'cancelled'],
                'under_review' => ['in_process', 'rejected', 'cancelled']
            ],
            'allow_manager_status_changes' => [
                'draft' => ['pending', 'in_process', 'completed', 'cancelled'],
                'pending' => ['in_process', 'under_review', 'completed', 'cancelled'],
                'in_process' => ['completed', 'partially_used', 'cancelled'],
                'under_review' => ['in_process', 'rejected', 'completed', 'cancelled'],
                'rejected' => ['draft', 'cancelled']
            ],
            'auto_approve_for_roles' => ['Manager', 'Administrator'],
            'require_notes_for_status_change' => false,
            'enable_audit_trail' => true,
            'stock_impact_bypass' => [
                'draft_to_in_process',
                'draft_to_completed'
            ]
        ];
    }

    // Helper untuk migrasi ke DB di masa depan
    public static function getKeyList(): array
    {
        return array_keys(self::getConfig());
    }

    public static function getAllowedTransitions(string $role, string $currentStatus): array
    {
        $config = self::getConfig();
        $transitions = [];

        if ($role === 'Operator') {
            $transitions = $config['allow_operator_status_changes'][$currentStatus] ?? [];
            // Inject direct flags
            if ($currentStatus === 'draft') {
                if (!in_array('in_process', $transitions) && ($config['allow_direct_to_in_process'] ?? false)) {
                    $transitions[] = 'in_process';
                }
                if (!in_array('completed', $transitions) && ($config['allow_direct_to_completed'] ?? false)) {
                    $transitions[] = 'completed';
                }
            }
        } elseif ($role === 'Supervisor') {
            $transitions = $config['allow_supervisor_status_changes'][$currentStatus] ?? [];
        } elseif (in_array($role, ['Manager', 'Administrator'])) {
            $transitions = $config['allow_manager_status_changes'][$currentStatus] ?? [];
        }
        // Unik dan urutkan
        return array_values(array_unique($transitions));
    }
}
