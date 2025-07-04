<?php

namespace App\Models;

use App\Config\CompanyConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'id',
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'domain',
        'database',
        'package',
        'config',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array'
    ];

    /**
     * Get company configuration with defaults
     */
    public function getConfig(): array
    {
        $defaultConfig = CompanyConfig::getDefaultConfig();
        $companyConfig = $this->config ?? [];

        return array_merge_recursive_distinct($defaultConfig, $companyConfig);
    }

    /**
     * Get specific configuration section
     */
    public function getConfigSection(string $section): array
    {
        $config = $this->getConfig();
        return $config[$section] ?? [];
    }

    /**
     * Update specific configuration section
     */
    public function updateConfigSection(string $section, array $config): self
    {
        $currentConfig = $this->config ?? [];
        $currentConfig[$section] = $config;

        $this->config = $currentConfig;
        $this->save();

        return $this;
    }

    /**
     * Update entire configuration
     */
    public function updateConfig(array $config): self
    {
        $this->config = $config;
        $this->save();

        return $this;
    }

    /**
     * Reset configuration to defaults
     */
    public function resetConfig(): self
    {
        $this->config = CompanyConfig::getDefaultConfig();
        $this->save();

        return $this;
    }

    /**
     * Get mutation configuration
     */
    public function getMutationConfig(): array
    {
        return $this->getConfigSection('mutation');
    }

    /**
     * Get livestock configuration
     */
    public function getLivestockConfig(): array
    {
        return $this->getConfigSection('livestock');
    }

    /**
     * Get feed configuration
     */
    public function getFeedConfig(): array
    {
        return $this->getConfigSection('feed');
    }

    /**
     * Get supply configuration
     */
    public function getSupplyConfig(): array
    {
        return $this->getConfigSection('supply');
    }

    /**
     * Get notification configuration
     */
    public function getNotificationConfig(): array
    {
        return $this->getConfigSection('notification');
    }

    /**
     * Get reporting configuration
     */
    public function getReportingConfig(): array
    {
        return $this->getConfigSection('reporting');
    }

    /**
     * Get livestock recording configuration
     */
    public function getLivestockRecordingConfig(): array
    {
        $config = $this->getLivestockConfig();
        return $config['recording_method'] ?? [];
    }

    /**
     * Check if company uses batch recording method
     */
    public function usesBatchRecording(): bool
    {
        $config = $this->getLivestockRecordingConfig();
        return ($config['type'] ?? 'batch') === 'batch';
    }

    /**
     * Check if company allows multiple batches
     */
    public function allowsMultipleBatches(): bool
    {
        $config = $this->getLivestockRecordingConfig();
        return $config['allow_multiple_batches'] ?? true;
    }

    /**
     * Get batch settings for livestock recording
     */
    public function getLivestockBatchSettings(): array
    {
        $config = $this->getLivestockRecordingConfig();
        return $config['batch_settings'] ?? [];
    }

    /**
     * Get total settings for livestock recording
     */
    public function getLivestockTotalSettings(): array
    {
        $config = $this->getLivestockRecordingConfig();
        return $config['total_settings'] ?? [];
    }

    /**
     * Update livestock recording configuration
     */
    public function updateLivestockRecordingConfig(array $config): self
    {
        $currentConfig = $this->getLivestockConfig();
        $currentConfig['recording_method'] = $config;

        return $this->updateConfigSection('livestock', $currentConfig);
    }

    /**
     * Check if company can be deleted (no users mapped)
     */
    public function canBeDeleted(): bool
    {
        // Cek apakah masih ada user yang terhubung ke company ini
        return !\App\Models\CompanyUser::where('company_id', $this->id)->exists();
    }
}
