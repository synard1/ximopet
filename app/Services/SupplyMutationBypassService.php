<?php

namespace App\Services;

use App\Models\SupplyMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SupplyMutationBypassService
{
    /**
     * Check if mutation can bypass approval
     */
    public function canBypassApproval(SupplyMutation $mutation): bool
    {
        $config = config('supply_mutation.workflow.bypass_approval');

        if (!$config['enabled']) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Check if user has bypass role
        $hasBypassRole = false;
        foreach ($config['bypass_roles'] as $role) {
            if ($user->hasRole($role)) {
                $hasBypassRole = true;
                break;
            }
        }

        if (!$hasBypassRole) {
            return false;
        }

        // Check bypass conditions
        $totalQuantity = $mutation->supplyMutationItems->sum('quantity');
        $totalValue = $mutation->supplyMutationItems->sum(function ($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });

        // Check quantity threshold
        if ($totalQuantity > $config['bypass_quantity_threshold']) {
            return false;
        }

        // Check value threshold
        if ($totalValue > $config['bypass_value_threshold']) {
            return false;
        }

        // Check same farm mutation
        if (
            $config['bypass_conditions']['same_farm_mutation'] &&
            $mutation->from_farm_id === $mutation->to_farm_id
        ) {
            return true;
        }

        // Check small quantity
        if (
            $config['bypass_conditions']['small_quantity'] &&
            $totalQuantity <= $config['bypass_quantity_threshold']
        ) {
            return true;
        }

        // Check emergency mutation
        if ($config['bypass_conditions']['emergency_mutation']) {
            return true;
        }

        // Verification is no longer required for bypass
        // Removed verified_data check

        return false;
    }

    /**
     * Check if mutation requires verification for bypass
     */
    public function requiresVerificationForBypass(SupplyMutation $mutation): bool
    {
        return false; // Verification is no longer required for bypass
    }

    /**
     * Verify mutation for bypass approval
     * 
     * @param SupplyMutation $mutation
     * @param string|null $notes
     * @return bool
     */
    public function verifyMutation(SupplyMutation $mutation, ?string $notes = null): bool
    {
        try {
            if (!$mutation->canBeVerified()) {
                throw new Exception('Mutation cannot be verified in current status');
            }

            if (!$this->canBypassApproval($mutation)) {
                throw new Exception('Mutation cannot bypass approval');
            }

            $mutation->update([
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            Log::info('Supply mutation verified for bypass', [
                'mutation_id' => $mutation->id,
                'verified_by' => Auth::id(),
                'notes' => $notes
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to verify mutation for bypass', [
                'mutation_id' => $mutation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Complete verified mutation (bypass approval)
     * 
     * @param SupplyMutation $mutation
     * @param string|null $notes
     * @return bool
     */
    public function completeVerifiedMutation(SupplyMutation $mutation, ?string $notes = null): bool
    {
        try {
            if (!$mutation->isVerified()) {
                throw new Exception('Mutation must be verified before completion');
            }

            if (!$this->canBypassApproval($mutation)) {
                throw new Exception('Mutation cannot bypass approval');
            }

            $mutation->update([
                'status' => 'completed',
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            Log::info('Supply mutation completed (bypass approval)', [
                'mutation_id' => $mutation->id,
                'completed_by' => Auth::id(),
                'notes' => $notes
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to complete verified mutation', [
                'mutation_id' => $mutation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get available status transitions for user
     * 
     * @param SupplyMutation $mutation
     * @return array
     */
    public function getAvailableStatusTransitions(SupplyMutation $mutation): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $currentStatus = $mutation->status;
        $availableTransitions = [];

        // Get base transitions from config
        $configTransitions = config('supply_mutation.workflow.status_flow');
        $baseTransitions = $configTransitions[$currentStatus] ?? [];

        foreach ($baseTransitions as $status) {
            // Check if user can perform this transition
            if ($this->canPerformTransition($mutation, $status)) {
                $availableTransitions[] = $status;
            }
        }

        return $availableTransitions;
    }

    /**
     * Check if user can perform specific transition
     * 
     * @param SupplyMutation $mutation
     * @param string $newStatus
     * @return bool
     */
    private function canPerformTransition(SupplyMutation $mutation, string $newStatus): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Check bypass approval for draft -> verified
        if ($mutation->status === 'draft' && $newStatus === 'verified') {
            return $this->canBypassApproval($mutation);
        }

        // Check verification completion for verified -> completed
        if ($mutation->status === 'verified' && $newStatus === 'completed') {
            return $this->canBypassApproval($mutation);
        }

        // For other transitions, check user permissions
        return $user->can('update supply mutation');
    }
}
