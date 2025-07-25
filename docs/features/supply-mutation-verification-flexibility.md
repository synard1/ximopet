# Supply Mutation Verification Flexibility

## Overview

Sistem supply mutation telah diperbaiki untuk memungkinkan verifikasi dilakukan secara fleksibel tanpa menghalangi proses supply mutation. Verifikasi dapat dilakukan belakangan dan tidak lagi menjadi prasyarat untuk bypass approval.

## Perubahan Utama

### 1. Konfigurasi Workflow

#### Bypass Approval Configuration

```php
'bypass_approval' => [
    'enabled' => true,
    'require_verification' => false, // Changed from true
    'allow_verification_later' => true, // New
    'bypass_conditions' => [
        'verified_data' => false, // Changed from true
        // ... other conditions
    ],
],
```

#### Verification Configuration

```php
'verification' => [
    'enabled' => true,
    'require_verification_for_bypass' => false, // Changed from true
    'allow_verification_after_completion' => true, // New
    'verification_notes_required' => false, // Changed from true
],
```

### 2. Status Flow yang Fleksibel

```php
'status_flow' => [
    'draft' => ['pending', 'verified', 'completed', 'cancelled'], // Added completed
    'pending' => ['approved', 'rejected', 'verified', 'cancelled'], // Added verified
    'verified' => ['completed', 'cancelled'],
    'approved' => ['completed', 'verified', 'cancelled'], // Added verified
    'rejected' => ['draft', 'cancelled'],
    'completed' => ['verified', 'cancelled'], // Added verified - allow verification after completion
    'cancelled' => [], // Terminal state
],
```

### 3. Model Changes

#### SupplyMutation Model

-   `canBeVerified()`: Sekarang mendukung verifikasi dari multiple status
-   `canBypassApproval()`: Tidak lagi memerlukan verifikasi
-   `requiresVerificationForBypass()`: Selalu mengembalikan false

#### Service Layer

-   `verifyMutation()`: Mendukung verifikasi setelah completion
-   `completeVerifiedMutation()`: Tidak memerlukan verifikasi wajib

## Alur Kerja Baru

### 1. Bypass Approval Tanpa Verifikasi

```
Draft → Completed (bypass approval)
```

-   Operator dapat langsung menyelesaikan mutasi tanpa verifikasi
-   Verifikasi dapat dilakukan belakangan jika diperlukan

### 2. Verifikasi Fleksibel

```
Draft → Verified → Completed
Pending → Verified → Completed
Approved → Verified → Completed
Completed → Verified (verification after completion)
```

### 3. Verifikasi Setelah Completion

```
Completed → Verified
```

-   Mutasi yang sudah selesai dapat diverifikasi belakangan
-   Berguna untuk audit trail dan quality control

## Business Rules

### 1. Bypass Approval Rules

-   **Role Requirements**: admin, supervisor, operator, manager
-   **Quantity Threshold**: ≤ 500 units
-   **Value Threshold**: ≤ 500,000 currency
-   **Conditions**: same farm, small quantity, emergency mutation
-   **Verification**: Tidak wajib

### 2. Verification Rules

-   **Role Requirements**: admin, supervisor, operator
-   **Status Support**: draft, pending, approved, completed
-   **Notes**: Tidak wajib (configurable)
-   **Re-verification**: Diizinkan setelah completion

### 3. Status Transition Rules

-   **Draft**: Dapat langsung ke completed (bypass) atau verified
-   **Pending**: Dapat ke verified atau approved
-   **Approved**: Dapat ke verified atau completed
-   **Completed**: Dapat ke verified (audit trail)
-   **Verified**: Dapat ke completed

## Implementation Details

### 1. Configuration Changes

```php
// config/supply_mutation.php
'workflow' => [
    'bypass_approval' => [
        'require_verification' => false,
        'allow_verification_later' => true,
    ],
    'verification' => [
        'require_verification_for_bypass' => false,
        'allow_verification_after_completion' => true,
        'verification_notes_required' => false,
    ],
],
```

### 2. Model Methods

```php
// app/Models/SupplyMutation.php
public function canBeVerified(): bool
{
    $allowedStatuses = ['draft', 'pending', 'approved', 'completed'];
    return in_array($this->status, $allowedStatuses);
}

public function canBypassApproval(): bool
{
    // Verification not required
    return $this->checkBypassConditions();
}
```

### 3. Service Methods

```php
// app/Services/SupplyMutationService.php
public function verifyMutation(SupplyMutation $mutation, ?string $notes = null): void
{
    // Only update status if not already completed
    if ($mutation->status !== 'completed') {
        $updateData['status'] = 'verified';
    }
    // Always update verification tracking
    $updateData['verified_by'] = auth()->id();
    $updateData['verified_at'] = now();
}

public function completeVerifiedMutation(SupplyMutation $mutation, ?string $notes = null): void
{
    // Verification not required for completion
    if (!$mutation->canBypassApproval()) {
        throw new Exception('Mutation cannot bypass approval');
    }
    // Complete mutation
}
```

## Benefits

### 1. Operational Efficiency

-   **Faster Processing**: Mutasi dapat langsung diselesaikan tanpa menunggu verifikasi
-   **Flexible Workflow**: Verifikasi dapat dilakukan sesuai kebutuhan
-   **Emergency Support**: Bypass approval untuk situasi darurat

### 2. Quality Control

-   **Audit Trail**: Verifikasi tetap dapat dilakukan untuk audit
-   **Post-Completion Review**: Verifikasi setelah completion untuk quality control
-   **Configurable Requirements**: Notes dan role requirements dapat dikonfigurasi

### 3. User Experience

-   **Reduced Bottlenecks**: Tidak ada blocking pada verifikasi
-   **Role-Based Access**: Fleksibilitas berdasarkan role
-   **Clear Status Flow**: Status transitions yang jelas dan logis

## Migration Guide

### 1. Environment Variables

```bash
# Set these in .env
SUPPLY_MUTATION_REQUIRE_VERIFICATION=false
SUPPLY_MUTATION_VERIFICATION_ENABLED=true
SUPPLY_MUTATION_BYPASS_APPROVAL=true
```

### 2. Database Considerations

-   Existing mutations dengan status 'verified' tetap valid
-   New mutations dapat menggunakan alur fleksibel
-   No database migration required

### 3. User Training

-   **Operators**: Dapat langsung complete mutasi tanpa verifikasi
-   **Supervisors**: Dapat verify mutasi yang sudah completed
-   **Admins**: Full access to all status transitions

## Monitoring and Audit

### 1. Logging

```php
Log::info('Supply mutation verified', [
    'mutation_id' => $mutation->id,
    'verified_by' => auth()->id(),
    'previous_status' => $mutation->getOriginal('status'),
    'new_status' => $mutation->status
]);
```

### 2. Audit Trail

-   Semua status changes dicatat dengan user dan timestamp
-   Verification tracking tetap lengkap
-   Bypass approval reasons dicatat

### 3. Reporting

-   Mutasi yang bypass approval tanpa verifikasi
-   Mutasi yang diverifikasi setelah completion
-   Verification compliance metrics

## Security Considerations

### 1. Role-Based Access

-   Bypass approval hanya untuk role tertentu
-   Verification tetap memerlukan role verification
-   Clear separation of concerns

### 2. Audit Trail

-   Semua actions dicatat dengan user context
-   Verification history tetap lengkap
-   Bypass approval reasons tracked

### 3. Configuration Security

-   Sensitive settings dapat dikonfigurasi per environment
-   Role requirements dapat disesuaikan
-   Threshold values dapat diatur

## Testing Scenarios

### 1. Bypass Approval Without Verification

```php
// Test: Operator can complete mutation without verification
$mutation = SupplyMutation::factory()->create(['status' => 'draft']);
$user = User::factory()->create()->assignRole('operator');

// Should succeed
$mutation->update(['status' => 'completed']);
```

### 2. Verification After Completion

```php
// Test: Supervisor can verify completed mutation
$mutation = SupplyMutation::factory()->create(['status' => 'completed']);
$user = User::factory()->create()->assignRole('supervisor');

// Should succeed
$mutation->update(['verified_by' => $user->id, 'verified_at' => now()]);
```

### 3. Status Transition Validation

```php
// Test: Invalid transitions are blocked
$mutation = SupplyMutation::factory()->create(['status' => 'completed']);

// Should fail
$this->expectException(Exception::class);
$mutation->update(['status' => 'pending']);
```

## Conclusion

Perubahan ini memberikan fleksibilitas maksimal dalam proses supply mutation sambil tetap mempertahankan kontrol kualitas melalui verifikasi opsional. Sistem sekarang mendukung:

1. **Operational Efficiency**: Bypass approval tanpa verifikasi wajib
2. **Quality Control**: Verifikasi dapat dilakukan belakangan
3. **Audit Trail**: Tracking lengkap untuk semua actions
4. **Flexible Workflow**: Status transitions yang fleksibel
5. **Role-Based Access**: Kontrol akses berdasarkan role

Implementasi ini siap untuk production dan dapat disesuaikan lebih lanjut berdasarkan kebutuhan bisnis.
