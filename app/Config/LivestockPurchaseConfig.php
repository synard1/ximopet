<?php

namespace App\Config;

/**
 * Livestock Purchase Configuration
 * 
 * Konfigurasi komprehensif untuk sistem pembelian ternak yang mencakup:
 * - Workflow dan status management
 * - Validasi dan approval
 * - Batch management
 * - Cost tracking
 * - Document management
 * - Notification settings
 * - Business rules
 */
class LivestockPurchaseConfig
{
    /**
     * Get default livestock purchase configuration
     */
    public static function getConfig(): array
    {
        return [
            'enabled' => true,
            'workflow' => self::getWorkflowConfig(),
            'validation' => self::getValidationConfig(),
            'approval' => self::getApprovalConfig(),
            'batch_management' => self::getBatchManagementConfig(),
            'cost_tracking' => self::getCostTrackingConfig(),
            'document_management' => self::getDocumentManagementConfig(),
            'notification' => self::getNotificationConfig(),
            'business_rules' => self::getBusinessRulesConfig(),
            'reporting' => self::getReportingConfig(),
            'integration' => self::getIntegrationConfig(),
        ];
    }

    /**
     * Get workflow configuration
     */
    public static function getWorkflowConfig(): array
    {
        return [
            'business_flow_type' => 'simple', // simple, standard, complex
            'status_flow' => [
                'draft' => [
                    'enabled' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'requires_approval' => false,
                    'auto_numbering' => false,
                    'next_statuses' => ['pending', 'confirmed'],
                    'description' => 'Status awal saat membuat pembelian. Belum ada konfirmasi atau validasi. Masih bisa diedit/dihapus.',
                    'color' => 'light-gray',
                    'icon' => 'fas fa-edit',
                    'validation_rules' => [
                        'require_basic_info' => true,
                        'require_supplier' => true,
                        'require_items' => true,
                    ],
                    'notifications' => [
                        'on_create' => false,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
                'pending' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => true,
                    'auto_numbering' => true,
                    'next_statuses' => ['confirmed', 'cancelled'],
                    'description' => 'Sudah dibuat tapi menunggu konfirmasi. Menunggu persetujuan dari pihak terkait. Belum bisa diproses lebih lanjut.',
                    'color' => 'yellow',
                    'icon' => 'fas fa-clock',
                    'validation_rules' => [
                        'require_complete_data' => true,
                        'require_approval_workflow' => true,
                        'validate_business_rules' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                        'reminder_enabled' => true,
                        'reminder_interval_hours' => 6,
                    ],
                ],
                'confirmed' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => false,
                    'auto_numbering' => true,
                    'next_statuses' => ['in_transit', 'cancelled'],
                    'description' => 'Sudah dikonfirmasi/disetujui. Siap untuk diproses pengiriman. Belum ada pengiriman ternak.',
                    'color' => 'blue',
                    'icon' => 'fas fa-check-circle',
                    'validation_rules' => [
                        'require_expedition_info' => true,
                        'require_delivery_schedule' => true,
                        'validate_supplier_contract' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
                'in_transit' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => false,
                    'auto_numbering' => true,
                    'next_statuses' => ['arrived', 'cancelled'],
                    'description' => 'Ternak sedang dalam perjalanan. Sudah ada nomor DO/Surat Jalan. Belum sampai di lokasi tujuan.',
                    'color' => 'orange',
                    'icon' => 'fas fa-truck',
                    'validation_rules' => [
                        'require_do_number' => true,
                        'require_expedition_tracking' => true,
                        'validate_delivery_schedule' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => true,
                        'on_delete' => false,
                        'tracking_updates' => true,
                    ],
                ],
                'arrived' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => false,
                    'auto_numbering' => true,
                    'next_statuses' => ['in_coop', 'cancelled'],
                    'description' => 'Ternak sudah sampai di lokasi tujuan. Sudah dilakukan pemeriksaan awal. Siap untuk dipindahkan ke kandang.',
                    'color' => 'green',
                    'icon' => 'fas fa-map-marker-alt',
                    'validation_rules' => [
                        'require_arrival_confirmation' => true,
                        'require_initial_inspection' => true,
                        'require_health_check' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
                'in_coop' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => false,
                    'auto_numbering' => true,
                    'next_statuses' => ['completed'],
                    'description' => 'Ternak sudah dipindahkan ke kandang. Sudah dilakukan pencatatan di sistem. Proses pembelian selesai.',
                    'color' => 'green',
                    'icon' => 'fas fa-home',
                    'validation_rules' => [
                        'require_coop_assignment' => true,
                        'require_batch_creation' => true,
                        'require_final_inspection' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
                'completed' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => false,
                    'auto_numbering' => true,
                    'next_statuses' => [],
                    'description' => 'Seluruh proses selesai. Semua dokumen lengkap. Pembayaran sudah selesai.',
                    'color' => 'dark-gray',
                    'icon' => 'fas fa-flag-checkered',
                    'validation_rules' => [
                        'require_complete_documentation' => true,
                        'require_payment_confirmation' => true,
                        'require_final_report' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
                'cancelled' => [
                    'enabled' => true,
                    'can_edit' => false,
                    'can_delete' => false,
                    'requires_approval' => true,
                    'auto_numbering' => false,
                    'next_statuses' => [],
                    'description' => 'Pembelian dibatalkan. Bisa karena berbagai alasan. Tidak bisa diproses lebih lanjut.',
                    'color' => 'red',
                    'icon' => 'fas fa-times-circle',
                    'validation_rules' => [
                        'require_cancellation_reason' => true,
                        'require_approval_for_cancellation' => true,
                        'validate_cancellation_impact' => true,
                    ],
                    'notifications' => [
                        'on_create' => true,
                        'on_update' => false,
                        'on_delete' => false,
                    ],
                ],
            ],
            'status_labels' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'in_transit' => 'In Transit',
                'arrived' => 'Arrived',
                'in_coop' => 'In Coop',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
            'business_flows' => [
                'simple' => [
                    'name' => 'Simple Flow',
                    'description' => 'Flow sederhana untuk pembelian kecil dengan tracking pengiriman',
                    'statuses' => ['draft', 'confirmed', 'in_transit', 'arrived', 'completed'],
                    'transitions' => [
                        'draft' => ['confirmed'],
                        'confirmed' => ['in_transit', 'arrived'],
                        'in_transit' => ['arrived'],
                        'arrived' => ['completed'],
                        'completed' => [],
                    ],
                    'approval_required' => false,
                    'auto_approval' => true,
                    'batch_creation' => false,
                    'document_requirements' => 'minimal',
                    'tracking_required' => true,
                ],
                'standard' => [
                    'name' => 'Standard Flow',
                    'description' => 'Flow standar untuk pembelian menengah',
                    'statuses' => ['draft', 'pending', 'confirmed', 'in_transit', 'arrived', 'completed'],
                    'transitions' => [
                        'draft' => ['pending'],
                        'pending' => ['confirmed', 'cancelled'],
                        'confirmed' => ['in_transit', 'cancelled'],
                        'in_transit' => ['arrived', 'cancelled'],
                        'arrived' => ['completed', 'cancelled'],
                        'completed' => [],
                        'cancelled' => [],
                    ],
                    'approval_required' => true,
                    'auto_approval' => false,
                    'batch_creation' => true,
                    'document_requirements' => 'standard',
                ],
                'complex' => [
                    'name' => 'Complex Flow',
                    'description' => 'Flow kompleks untuk pembelian besar dengan multiple approvals',
                    'statuses' => ['draft', 'pending', 'confirmed', 'in_transit', 'arrived', 'in_coop', 'completed'],
                    'transitions' => [
                        'draft' => ['pending'],
                        'pending' => ['confirmed', 'cancelled'],
                        'confirmed' => ['in_transit', 'cancelled'],
                        'in_transit' => ['arrived', 'cancelled'],
                        'arrived' => ['in_coop', 'cancelled'],
                        'in_coop' => ['completed'],
                        'completed' => [],
                        'cancelled' => [],
                    ],
                    'approval_required' => true,
                    'auto_approval' => false,
                    'batch_creation' => true,
                    'document_requirements' => 'comprehensive',
                    'multiple_approvals' => true,
                    'quality_checks' => true,
                    'tracking_required' => true,
                ],
            ],
            'auto_transitions' => [
                'draft_to_pending' => [
                    'enabled' => true,
                    'trigger' => 'user_action',
                    'conditions' => ['has_valid_data', 'has_approval'],
                    'auto_validation' => true,
                ],
                'pending_to_confirmed' => [
                    'enabled' => true,
                    'trigger' => 'approval',
                    'conditions' => ['approved_by_manager'],
                    'auto_validation' => true,
                ],
                'confirmed_to_in_transit' => [
                    'enabled' => true,
                    'trigger' => 'user_action',
                    'conditions' => ['has_expedition_info', 'has_do_number'],
                    'auto_validation' => true,
                ],
                'in_transit_to_arrived' => [
                    'enabled' => true,
                    'trigger' => 'user_action',
                    'conditions' => ['has_arrival_confirmation', 'has_initial_inspection'],
                    'auto_validation' => true,
                ],
                'arrived_to_in_coop' => [
                    'enabled' => true,
                    'trigger' => 'user_action',
                    'conditions' => ['has_coop_assignment', 'has_batch_creation'],
                    'auto_validation' => true,
                ],
                'in_coop_to_completed' => [
                    'enabled' => true,
                    'trigger' => 'user_action',
                    'conditions' => ['has_final_inspection', 'has_payment_confirmation'],
                    'auto_validation' => true,
                ],
            ],
            'status_requirements' => [
                'draft' => [
                    'required_fields' => ['tanggal', 'supplier_id', 'items'],
                    'optional_fields' => ['notes', 'expected_delivery_date'],
                    'documents' => [],
                ],
                'pending' => [
                    'required_fields' => ['tanggal', 'supplier_id', 'farm_id', 'coop_id', 'items', 'total_amount'],
                    'optional_fields' => ['notes', 'expected_delivery_date', 'special_requirements'],
                    'documents' => ['purchase_order'],
                ],
                'confirmed' => [
                    'required_fields' => ['expedition_id', 'expected_delivery_date'],
                    'optional_fields' => ['do_number', 'tracking_number', 'special_instructions'],
                    'documents' => ['purchase_order'],
                ],
                'in_transit' => [
                    'required_fields' => ['do_number'],
                    'optional_fields' => ['expedition_tracking', 'estimated_arrival_time', 'route_information'],
                    'documents' => ['delivery_order'],
                ],
                'arrived' => [
                    'required_fields' => ['arrival_time'],
                    'optional_fields' => ['initial_inspection_result', 'weather_conditions', 'transport_notes'],
                    'documents' => ['arrival_confirmation'],
                ],
                'in_coop' => [
                    'required_fields' => ['coop_id', 'batch_number', 'final_inspection_result'],
                    'optional_fields' => ['coop_notes', 'health_status'],
                    'documents' => ['coop_assignment', 'batch_creation', 'final_inspection_report'],
                ],
                'completed' => [
                    'required_fields' => ['completion_date'],
                    'optional_fields' => ['payment_confirmation', 'final_notes', 'performance_rating'],
                    'documents' => ['completion_certificate'],
                ],
                'cancelled' => [
                    'required_fields' => ['cancellation_reason', 'cancellation_date'],
                    'optional_fields' => ['refund_information', 'lessons_learned'],
                    'documents' => ['cancellation_letter', 'refund_documentation'],
                ],
            ],
        ];
    }

    /**
     * Get validation configuration
     */
    public static function getValidationConfig(): array
    {
        return [
            'required_fields' => [
                'tanggal' => true,
                'supplier_id' => true,
                'farm_id' => true,
                'coop_id' => true,
                'invoice_number' => true,
                'items' => [
                    'livestock_strain_id' => true,
                    'quantity' => true,
                    'price_per_unit' => true,
                ],
            ],
            'business_rules' => [
                'min_quantity' => 1,
                'max_quantity' => 50000,
                'min_price_per_unit' => 1000,
                'max_price_per_unit' => 1000000,
                'require_unique_invoice' => true,
                'validate_coop_capacity' => true,
                'validate_farm_availability' => true,
                'validate_supplier_contract' => false,
            ],
            'data_validation' => [
                'tanggal' => [
                    'type' => 'date',
                    'min_date' => 'today',
                    'max_date' => '+30 days',
                    'format' => 'Y-m-d',
                ],
                'quantity' => [
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 50000,
                ],
                'price_per_unit' => [
                    'type' => 'decimal',
                    'min' => 1000,
                    'max' => 1000000,
                    'decimal_places' => 2,
                ],
                'invoice_number' => [
                    'type' => 'string',
                    'min_length' => 3,
                    'max_length' => 50,
                    'pattern' => '/^[A-Z0-9\-_]+$/',
                ],
            ],
            'cross_field_validation' => [
                'farm_coop_combination' => [
                    'enabled' => true,
                    'rule' => 'coop_must_belong_to_farm',
                ],
                'supplier_strain_combination' => [
                    'enabled' => false,
                    'rule' => 'supplier_must_have_strain',
                ],
                'quantity_price_validation' => [
                    'enabled' => true,
                    'rule' => 'total_amount_within_limit',
                    'max_total_amount' => 1000000000, // 1 miliar
                ],
            ],
        ];
    }

    /**
     * Get approval configuration
     */
    public static function getApprovalConfig(): array
    {
        return [
            'enabled' => true,
            'levels' => [
                1 => [
                    'name' => 'Manager Approval',
                    'roles' => ['Manager', 'Supervisor'],
                    'required' => true,
                    'auto_approve' => false,
                    'threshold' => 0,
                ],
                2 => [
                    'name' => 'Director Approval',
                    'roles' => ['Director', 'Administrator'],
                    'required' => false,
                    'auto_approve' => true,
                    'threshold' => 50000000, // 50 juta
                ],
            ],
            'auto_approval' => [
                'enabled' => true,
                'threshold' => 10000000, // 10 juta
                'roles' => ['Manager', 'Supervisor'],
                'conditions' => [
                    'supplier_whitelist' => true,
                    'within_budget' => true,
                    'valid_data' => true,
                ],
            ],
            'bypass_approval' => [
                'enabled' => false,
                'roles' => ['Administrator'],
                'conditions' => [
                    'emergency_purchase' => true,
                    'director_authorization' => true,
                ],
            ],
            'approval_timeout' => [
                'enabled' => true,
                'timeout_hours' => 24,
                'action' => 'escalate', // escalate, auto_approve, cancel
                'escalation_roles' => ['Director'],
            ],
        ];
    }

    /**
     * Get batch management configuration
     */
    public static function getBatchManagementConfig(): array
    {
        return [
            'enabled' => true,
            'auto_create_batch' => [
                'enabled' => true,
                'trigger' => 'status_in_coop',
                'naming_convention' => 'LSP-{FARM}-{COOP}-{DATE}-{SEQ}',
                'include_metadata' => [
                    'supplier' => true,
                    'strain' => true,
                    'purchase_date' => true,
                    'arrival_date' => true,
                ],
            ],
            'batch_tracking' => [
                'enabled' => true,
                'track_age' => true,
                'track_health' => true,
                'track_mortality' => true,
                'track_weight' => true,
                'track_feed_consumption' => true,
            ],
            'batch_validation' => [
                'require_unique_batch_number' => true,
                'validate_batch_capacity' => true,
                'check_batch_overlap' => true,
                'validate_batch_dates' => true,
            ],
            'batch_history' => [
                'enabled' => true,
                'retention_days' => 2555, // 7 tahun
                'track_changes' => true,
                'audit_trail' => true,
            ],
        ];
    }

    /**
     * Get cost tracking configuration
     */
    public static function getCostTrackingConfig(): array
    {
        return [
            'enabled' => true,
            'tracking_methods' => [
                'unit_cost' => [
                    'enabled' => true,
                    'include_transport' => true,
                    'include_tax' => true,
                    'include_handling' => true,
                ],
                'total_cost' => [
                    'enabled' => true,
                    'include_all_expenses' => true,
                    'calculate_per_unit' => true,
                ],
                'operational_cost' => [
                    'enabled' => false,
                    'include_labor' => false,
                    'include_overhead' => false,
                ],
            ],
            'cost_breakdown' => [
                'purchase_price' => true,
                'transport_cost' => true,
                'tax_amount' => true,
                'handling_cost' => true,
                'insurance_cost' => false,
                'inspection_cost' => false,
            ],
            'cost_validation' => [
                'max_unit_cost' => 1000000, // 1 juta per ekor
                'max_total_cost' => 1000000000, // 1 miliar
                'cost_variance_threshold' => 0.1, // 10%
                'require_cost_justification' => true,
            ],
            'cost_reporting' => [
                'enabled' => true,
                'include_historical_comparison' => true,
                'include_supplier_analysis' => true,
                'include_cost_trends' => true,
            ],
        ];
    }

    /**
     * Get document management configuration
     */
    public static function getDocumentManagementConfig(): array
    {
        return [
            'required_documents' => [
                'invoice' => [
                    'required' => true,
                    'validation' => [
                        'format' => ['pdf', 'jpg', 'png'],
                        'max_size' => '5MB',
                        'require_ocr' => false,
                    ],
                ],
                'delivery_order' => [
                    'required' => false,
                    'validation' => [
                        'format' => ['pdf', 'jpg', 'png'],
                        'max_size' => '5MB',
                        'require_ocr' => false,
                    ],
                ],
                'health_certificate' => [
                    'required' => false,
                    'validation' => [
                        'format' => ['pdf', 'jpg', 'png'],
                        'max_size' => '5MB',
                        'require_ocr' => false,
                    ],
                ],
                'vaccination_record' => [
                    'required' => false,
                    'validation' => [
                        'format' => ['pdf', 'jpg', 'png'],
                        'max_size' => '5MB',
                        'require_ocr' => false,
                    ],
                ],
            ],
            'document_workflow' => [
                'auto_attach' => [
                    'enabled' => false,
                    'trigger' => 'status_change',
                ],
                'document_approval' => [
                    'enabled' => false,
                    'roles' => ['Manager'],
                ],
                'document_retention' => [
                    'enabled' => true,
                    'retention_years' => 7,
                    'auto_archive' => true,
                ],
            ],
            'document_validation' => [
                'require_document_upload' => false,
                'validate_document_content' => false,
                'ocr_extraction' => [
                    'enabled' => false,
                    'extract_invoice_number' => false,
                    'extract_amount' => false,
                    'extract_date' => false,
                ],
            ],
        ];
    }

    /**
     * Get notification configuration
     */
    public static function getNotificationConfig(): array
    {
        return [
            'enabled' => true,
            'channels' => [
                'email' => [
                    'enabled' => true,
                    'recipients' => ['manager', 'supervisor'],
                    'templates' => [
                        'purchase_created' => 'emails.livestock-purchase.created',
                        'purchase_approved' => 'emails.livestock-purchase.approved',
                        'purchase_arrived' => 'emails.livestock-purchase.arrived',
                        'purchase_completed' => 'emails.livestock-purchase.completed',
                    ],
                ],
                'sms' => [
                    'enabled' => false,
                    'recipients' => ['manager'],
                    'templates' => [
                        'purchase_urgent' => 'sms.livestock-purchase.urgent',
                    ],
                ],
                'push' => [
                    'enabled' => true,
                    'recipients' => ['manager', 'supervisor'],
                    'templates' => [
                        'purchase_status_change' => 'push.livestock-purchase.status-change',
                    ],
                ],
                'in_app' => [
                    'enabled' => true,
                    'recipients' => ['all_users'],
                    'templates' => [
                        'purchase_notification' => 'in-app.livestock-purchase.notification',
                    ],
                ],
            ],
            'triggers' => [
                'purchase_created' => [
                    'enabled' => true,
                    'channels' => ['email', 'in_app'],
                    'recipients' => ['manager', 'supervisor'],
                ],
                'purchase_approved' => [
                    'enabled' => true,
                    'channels' => ['email', 'push', 'in_app'],
                    'recipients' => ['creator', 'manager'],
                ],
                'purchase_in_transit' => [
                    'enabled' => true,
                    'channels' => ['email', 'in_app'],
                    'recipients' => ['manager', 'supervisor'],
                ],
                'purchase_arrived' => [
                    'enabled' => true,
                    'channels' => ['email', 'push', 'in_app'],
                    'recipients' => ['manager', 'supervisor', 'farm_operator'],
                ],
                'purchase_in_coop' => [
                    'enabled' => true,
                    'channels' => ['email', 'in_app'],
                    'recipients' => ['manager', 'supervisor'],
                ],
                'purchase_completed' => [
                    'enabled' => true,
                    'channels' => ['email', 'push', 'in_app'],
                    'recipients' => ['manager', 'supervisor', 'creator'],
                ],
                'purchase_cancelled' => [
                    'enabled' => true,
                    'channels' => ['email', 'in_app'],
                    'recipients' => ['manager', 'supervisor', 'creator'],
                ],
                'approval_required' => [
                    'enabled' => true,
                    'channels' => ['email', 'push', 'in_app'],
                    'recipients' => ['approver'],
                    'reminder' => [
                        'enabled' => true,
                        'interval_hours' => 6,
                        'max_reminders' => 3,
                    ],
                ],
            ],
            'settings' => [
                'batch_notifications' => true,
                'include_attachments' => false,
                'notification_timeout' => 300, // 5 menit
                'retry_failed_notifications' => true,
                'max_retry_attempts' => 3,
            ],
        ];
    }

    /**
     * Get business rules configuration
     */
    public static function getBusinessRulesConfig(): array
    {
        return [
            'purchase_limits' => [
                'daily_limit' => [
                    'enabled' => true,
                    'max_quantity' => 10000,
                    'max_amount' => 500000000, // 500 juta
                ],
                'monthly_limit' => [
                    'enabled' => true,
                    'max_quantity' => 100000,
                    'max_amount' => 5000000000, // 5 miliar
                ],
                'supplier_limit' => [
                    'enabled' => true,
                    'max_amount_per_supplier' => 1000000000, // 1 miliar
                ],
            ],
            'timing_rules' => [
                'advance_booking' => [
                    'enabled' => true,
                    'min_days_advance' => 7,
                    'max_days_advance' => 90,
                ],
                'delivery_scheduling' => [
                    'enabled' => true,
                    'require_delivery_date' => true,
                    'allow_same_day_delivery' => false,
                    'max_delivery_delay' => 3, // hari
                ],
                'seasonal_restrictions' => [
                    'enabled' => false,
                    'restricted_months' => [],
                    'restricted_dates' => [],
                ],
            ],
            'quality_rules' => [
                'health_requirements' => [
                    'enabled' => true,
                    'require_health_certificate' => false,
                    'require_vaccination_record' => false,
                    'health_inspection_required' => true,
                ],
                'age_requirements' => [
                    'enabled' => true,
                    'min_age_days' => 1,
                    'max_age_days' => 30,
                ],
                'weight_requirements' => [
                    'enabled' => true,
                    'min_weight_grams' => 30,
                    'max_weight_grams' => 100,
                ],
            ],
            'supplier_rules' => [
                'supplier_whitelist' => [
                    'enabled' => false,
                    'allowed_suppliers' => [],
                ],
                'supplier_blacklist' => [
                    'enabled' => true,
                    'blocked_suppliers' => [],
                ],
                'supplier_rating' => [
                    'enabled' => true,
                    'min_rating' => 3.0,
                    'require_rating' => false,
                ],
            ],
            'farm_coop_rules' => [
                'capacity_validation' => [
                    'enabled' => true,
                    'check_coop_capacity' => true,
                    'check_farm_availability' => true,
                    'allow_overflow' => false,
                    'overflow_percentage' => 0.1, // 10%
                ],
                'geographic_rules' => [
                    'enabled' => false,
                    'max_distance_km' => 100,
                    'preferred_suppliers' => [],
                ],
            ],
        ];
    }

    /**
     * Get reporting configuration
     */
    public static function getReportingConfig(): array
    {
        return [
            'enabled' => true,
            'reports' => [
                'purchase_summary' => [
                    'enabled' => true,
                    'frequency' => 'daily',
                    'recipients' => ['manager', 'supervisor'],
                    'include_charts' => true,
                ],
                'supplier_analysis' => [
                    'enabled' => true,
                    'frequency' => 'weekly',
                    'recipients' => ['manager'],
                    'include_charts' => true,
                ],
                'cost_analysis' => [
                    'enabled' => true,
                    'frequency' => 'monthly',
                    'recipients' => ['manager', 'director'],
                    'include_charts' => true,
                ],
                'quality_report' => [
                    'enabled' => true,
                    'frequency' => 'weekly',
                    'recipients' => ['manager', 'supervisor'],
                    'include_charts' => false,
                ],
            ],
            'dashboards' => [
                'purchase_dashboard' => [
                    'enabled' => true,
                    'widgets' => [
                        'purchase_summary',
                        'pending_approvals',
                        'recent_purchases',
                        'cost_trends',
                    ],
                ],
                'supplier_dashboard' => [
                    'enabled' => true,
                    'widgets' => [
                        'supplier_performance',
                        'supplier_ranking',
                        'supplier_issues',
                    ],
                ],
            ],
            'export_formats' => [
                'pdf' => true,
                'excel' => true,
                'csv' => true,
                'json' => false,
            ],
            'data_retention' => [
                'report_data_years' => 7,
                'auto_archive' => true,
                'archive_frequency' => 'monthly',
            ],
        ];
    }

    /**
     * Get integration configuration
     */
    public static function getIntegrationConfig(): array
    {
        return [
            'accounting_integration' => [
                'enabled' => false,
                'system' => 'sap', // sap, oracle, quickbooks
                'auto_create_journal' => false,
                'sync_frequency' => 'daily',
            ],
            'inventory_integration' => [
                'enabled' => true,
                'auto_update_stock' => true,
                'sync_frequency' => 'real_time',
            ],
            'supplier_portal' => [
                'enabled' => false,
                'allow_supplier_submission' => false,
                'supplier_notifications' => false,
            ],
            'api_integration' => [
                'enabled' => false,
                'endpoints' => [
                    'create_purchase' => '/api/livestock-purchase',
                    'update_status' => '/api/livestock-purchase/{id}/status',
                    'get_suppliers' => '/api/suppliers',
                ],
                'authentication' => 'bearer_token',
            ],
        ];
    }

    /**
     * Get configuration for specific section
     */
    public static function getFor(string $section): array
    {
        $config = self::getConfig();
        return $config[$section] ?? [];
    }

    /**
     * Check if feature is enabled
     */
    public static function isEnabled(string $feature): bool
    {
        $config = self::getConfig();
        return $config[$feature]['enabled'] ?? false;
    }

    /**
     * Get status configuration
     */
    public static function getStatusConfig(string $status): array
    {
        $workflow = self::getWorkflowConfig();
        return $workflow['status_flow'][$status] ?? [];
    }

    /**
     * Get next available statuses
     */
    public static function getNextStatuses(string $currentStatus): array
    {
        $statusConfig = self::getStatusConfig($currentStatus);
        return $statusConfig['next_statuses'] ?? [];
    }

    /**
     * Check if status transition is allowed
     */
    public static function canTransitionTo(string $fromStatus, string $toStatus): bool
    {
        $nextStatuses = self::getNextStatuses($fromStatus);
        return in_array($toStatus, $nextStatuses);
    }

    /**
     * Get validation rules for status
     */
    public static function getValidationRulesForStatus(string $status): array
    {
        $validation = self::getValidationConfig();
        $statusConfig = self::getStatusConfig($status);

        $rules = $validation['required_fields'] ?? [];

        // Add status-specific validation rules
        if ($status === 'pending') {
            $rules['approval_required'] = true;
        }

        if ($status === 'in_transit') {
            $rules['expedition_info'] = true;
        }

        if ($status === 'arrived') {
            $rules['arrival_confirmation'] = true;
        }

        return $rules;
    }

    /**
     * Get business flow configuration
     */
    public static function getBusinessFlowConfig(string $flowType = null): array
    {
        $workflow = self::getWorkflowConfig();
        $flowType = $flowType ?? $workflow['business_flow_type'] ?? 'complex';

        return $workflow['business_flows'][$flowType] ?? $workflow['business_flows']['complex'];
    }

    /**
     * Get available business flows
     */
    public static function getAvailableBusinessFlows(): array
    {
        $workflow = self::getWorkflowConfig();
        return $workflow['business_flows'] ?? [];
    }

    /**
     * Get status requirements for specific status
     */
    public static function getStatusRequirements(string $status): array
    {
        $workflow = self::getWorkflowConfig();
        return $workflow['status_requirements'][$status] ?? [];
    }

    /**
     * Get status color and icon
     */
    public static function getStatusDisplay(string $status): array
    {
        $statusConfig = self::getStatusConfig($status);
        return [
            'color' => $statusConfig['color'] ?? 'gray',
            'icon' => $statusConfig['icon'] ?? 'fas fa-circle',
            'label' => $statusConfig['description'] ?? '',
        ];
    }

    /**
     * Check if status transition is allowed based on business flow
     */
    public static function canTransitionToWithFlow(string $fromStatus, string $toStatus, string $flowType = null): bool
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        $transitions = $flowConfig['transitions'] ?? [];

        return in_array($toStatus, $transitions[$fromStatus] ?? []);
    }

    /**
     * Get next available statuses based on business flow
     */
    public static function getNextStatusesWithFlow(string $currentStatus, string $flowType = null): array
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        $transitions = $flowConfig['transitions'] ?? [];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Get business flow summary
     */
    public static function getBusinessFlowSummary(string $flowType = null): array
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);

        return [
            'name' => $flowConfig['name'] ?? 'Unknown Flow',
            'description' => $flowConfig['description'] ?? '',
            'total_statuses' => count($flowConfig['statuses'] ?? []),
            'approval_required' => $flowConfig['approval_required'] ?? false,
            'auto_approval' => $flowConfig['auto_approval'] ?? false,
            'batch_creation' => $flowConfig['batch_creation'] ?? false,
            'document_requirements' => $flowConfig['document_requirements'] ?? 'standard',
            'multiple_approvals' => $flowConfig['multiple_approvals'] ?? false,
            'quality_checks' => $flowConfig['quality_checks'] ?? false,
            'tracking_required' => $flowConfig['tracking_required'] ?? false,
        ];
    }

    /**
     * Get status validation rules for specific status
     */
    public static function getStatusValidationRules(string $status): array
    {
        $statusConfig = self::getStatusConfig($status);
        return $statusConfig['validation_rules'] ?? [];
    }

    /**
     * Get status notification settings
     */
    public static function getStatusNotificationSettings(string $status): array
    {
        $statusConfig = self::getStatusConfig($status);
        return $statusConfig['notifications'] ?? [];
    }

    /**
     * Check if business flow requires approval
     */
    public static function isApprovalRequired(string $flowType = null): bool
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        return $flowConfig['approval_required'] ?? false;
    }

    /**
     * Check if business flow has auto approval
     */
    public static function hasAutoApproval(string $flowType = null): bool
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        return $flowConfig['auto_approval'] ?? false;
    }

    /**
     * Check if business flow requires batch creation
     */
    public static function requiresBatchCreation(string $flowType = null): bool
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        return $flowConfig['batch_creation'] ?? false;
    }

    /**
     * Get document requirements level
     */
    public static function getDocumentRequirementsLevel(string $flowType = null): string
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);
        return $flowConfig['document_requirements'] ?? 'standard';
    }

    /**
     * Get business flow complexity level
     */
    public static function getFlowComplexity(string $flowType = null): string
    {
        $flowConfig = self::getBusinessFlowConfig($flowType);

        if ($flowConfig['multiple_approvals'] ?? false) {
            return 'high';
        } elseif ($flowConfig['approval_required'] ?? false) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get recommended business flow based on purchase criteria
     */
    public static function getRecommendedFlow(array $criteria): string
    {
        $amount = $criteria['amount'] ?? 0;
        $quantity = $criteria['quantity'] ?? 0;
        $supplier_rating = $criteria['supplier_rating'] ?? 5;
        $is_urgent = $criteria['is_urgent'] ?? false;
        $requires_tracking = $criteria['requires_tracking'] ?? false;

        // Simple flow for small purchases with basic tracking
        if ($amount <= 10000000 && $quantity <= 1000 && $supplier_rating >= 4 && !$is_urgent) {
            return 'simple';
        }

        // Complex flow for large purchases
        if ($amount >= 100000000 || $quantity >= 10000 || $supplier_rating < 3 || $is_urgent || $requires_tracking) {
            return 'complex';
        }

        // Standard flow for everything else
        return 'standard';
    }

    /**
     * Get business flow comparison
     */
    public static function getBusinessFlowComparison(): array
    {
        $flows = self::getAvailableBusinessFlows();
        $comparison = [];

        foreach ($flows as $type => $config) {
            $comparison[$type] = [
                'name' => $config['name'],
                'description' => $config['description'],
                'total_statuses' => count($config['statuses']),
                'approval_required' => $config['approval_required'],
                'auto_approval' => $config['auto_approval'],
                'batch_creation' => $config['batch_creation'],
                'document_requirements' => $config['document_requirements'],
                'complexity' => self::getFlowComplexity($type),
                'suitable_for' => self::getSuitableFor($type),
            ];
        }

        return $comparison;
    }

    /**
     * Get suitable use cases for business flow
     */
    private static function getSuitableFor(string $flowType): array
    {
        switch ($flowType) {
            case 'simple':
                return [
                    'Pembelian kecil (< 10 juta)',
                    'Supplier terpercaya (rating >= 4)',
                    'Tracking pengiriman dasar',
                    'Proses cepat',
                    'Dokumentasi minimal',
                ];
            case 'standard':
                return [
                    'Pembelian menengah (10-100 juta)',
                    'Supplier dengan rating normal',
                    'Memerlukan approval',
                    'Batch creation',
                    'Dokumentasi standar',
                ];
            case 'complex':
                return [
                    'Pembelian besar (> 100 juta)',
                    'Supplier baru/bermasalah',
                    'Multiple approvals',
                    'Quality checks',
                    'Tracking lengkap',
                    'Dokumentasi komprehensif',
                ];
            default:
                return [];
        }
    }
}
