<?php

namespace App\Config;

class CompanyConfig
{
    /**
     * Get default template config (all possible config, for dev/future-proof)
     */
    public static function getDefaultTemplateConfig(): array
    {
        return [
            // Semua kemungkinan config, termasuk yang belum siap
            'purchasing' => self::getDefaultPurchasingConfig(),
            'mutation' => self::getDefaultMutationConfig(),
            'usage' => self::getDefaultUsageConfig(),
            'notification' => self::getDefaultNotificationConfig(),
            'reporting' => self::getDefaultReportingConfig(),
            // Tambahkan config lain yang belum siap diintegrasi di sini
            'integration' => [
                'external_api' => [
                    'enabled' => false,
                    'api_key' => '',
                    'endpoint' => '',
                ],
                'future_feature' => [
                    'enabled' => false,
                ],
            ],
        ];
    }

    /**
     * Get default active config (only config allowed for user/system)
     */
    public static function getDefaultActiveConfig(): array
    {
        // Hanya config yang sudah siap dan boleh dipakai user
        return [
            'purchasing' => self::getDefaultPurchasingConfig(),
            'livestock' => self::getDefaultLivestockConfig(),
            // 'mutation' => self::getDefaultMutationConfig(),
            // 'usage' => self::getDefaultUsageConfig(),
            // 'notification' => self::getDefaultNotificationConfig(),
            // 'reporting' => self::getDefaultReportingConfig(),
        ];
    }

    /**
     * Get default config (alias for getDefaultActiveConfig)
     */
    public static function getDefaultConfig(): array
    {
        return self::getDefaultActiveConfig();
    }

    /**
     * Get default purchasing configuration
     */
    public static function getDefaultPurchasingConfig(): array
    {
        return [
            'livestock_purchase' => [
                'enabled' => true,
                'validation_rules' => [
                    'require_strain' => true,
                    'require_strain_standard' => false,
                    'require_initial_weight' => true,
                    'require_initial_price' => true,
                    'require_supplier' => true,
                    'require_expedition' => false,
                    'require_do_number' => false,
                    'require_invoice' => true,
                ],
                'batch_creation' => [
                    'auto_create_batch' => true,
                    'batch_naming' => 'auto', // auto, manual
                    'batch_naming_format' => 'PR-{FARM}-{COOP}-{DATE}',
                    'require_batch_name' => false,
                ],
                'strain_validation' => [
                    'require_strain_selection' => true,
                    'allow_multiple_strains' => false,
                    'strain_standard_optional' => true,
                    'validate_strain_availability' => true,
                ],
                'cost_tracking' => [
                    'enabled' => true,
                    'include_transport_cost' => true,
                    'include_tax' => true,
                    'track_unit_cost' => true,
                    'track_total_cost' => true,
                ],
                'batch_settings' => [
                    'enabled' => true,
                    'tracking_enabled' => false,
                    'history_enabled' => false,
                    // 'require_batch_number' => true,
                    // 'auto_generate_batch' => [
                    //     'enabled' => false,
                    //     'format' => 'YEAR-SEQ',
                    // ],
                    'allow_multiple_batches' => [
                        'enabled' => false,
                        'max_batches' => 3,
                        // 'batch_number_format' => 'YEAR-SEQ',
                        'depletion_method' => 'fifo', // Default depletion method
                        'depletion_method_fifo' => [ // First In First Out - oldest batch is used first
                            'enabled' => true,
                            'track_age' => true, // Track age of items in batch
                            'min_age_days' => 0, // Minimum age before batch can be used
                            'max_age_days' => null, // Maximum age before batch expires (null = no limit)
                        ],
                        // 'depletion_method_lifo' => [ // Last In First Out - newest batch is used first
                        //     'enabled' => false,
                        //     'track_age' => false, // Don't track age for LIFO
                        //     'min_age_days' => 0,
                        //     'max_age_days' => null,
                        // ],
                        'depletion_method_manual' => [ // Manual depletion - user can manually select batch
                            'enabled' => true,
                            'track_age' => true, // Track age for manual depletion
                            'min_age_days' => 0,
                            'max_age_days' => null,
                        ],
                    ],
                ],
                // 'document_settings' => [
                //     'enabled' => true,
                //     'require_do_number' => true,
                //     'require_invoice' => true,
                //     'require_receipt' => true,
                // ],
                // 'approval_settings' => [
                //     'enabled' => true,
                //     'require_approval' => true,
                //     'approval_levels' => 2,
                //     'notify_on_pending' => true,
                //     'notify_on_approved' => true,
                //     'notify_on_rejected' => true,
                // ],
            ],
            'feed_purchase' => [
                'enabled' => true,
                // 'validation_rules' => [
                //     'enabled' => false,
                //     'require_supplier' => true,
                //     'require_price' => true,
                //     'require_quantity' => true,
                //     'require_unit' => true,
                // ],
                'batch_settings' => [
                    'enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => [
                        'enabled' => true,
                        'format' => 'YEAR-SEQ',
                    ],
                ],
                // 'document_settings' => [
                //     'enabled' => true,
                //     'require_do_number' => true,
                //     'require_invoice' => true,
                //     'require_receipt' => true,
                // ],
            ],
            'supply_purchase' => [
                'enabled' => true,
                // 'validation_rules' => [
                //     'enabled' => false,
                //     'require_supplier' => true,
                //     'require_price' => true,
                //     'require_quantity' => true,
                //     'require_unit' => true,
                // ],
                'batch_settings' => [
                    'enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => [
                        'enabled' => false,
                        'format' => 'YEAR-SEQ',
                    ],
                ],
                // 'document_settings' => [
                //     'enabled' => false,
                //     'require_do_number' => true,
                //     'require_invoice' => true,
                //     'require_receipt' => true,
                // ],
            ],
        ];
    }

    /**
     * Get default livestock configuration
     * Focus on livestock-specific management, not purchase/mutation/usage
     */
    public static function getDefaultLivestockConfig(): array
    {

        // Note : 
        // Jika status development, maka method tidak bisa digunakan ( depletion, mutation, feed usage )
        // Jika status ready, maka method bisa digunakan oleh user
        // Jika status not_applicable, maka method tidak bisa digunakan ( depletion, mutation, feed usage )
        // Jika auto_select true, maka dijadikan default method
        // hanya ada 1 method yang bisa dijadikan default method pada setiap section ( depletion, mutation, feed usage )
        // Note tidak boleh di hapus, digunakan untuk self learning AI

        return [
            'recording_method' => [
                'type' => 'batch',
                'allow_multiple_batches' => true,
                'batch_settings' => [
                    'enabled' => true,
                    'auto_generate_batch' => true,
                    'require_batch_number' => false,
                    'depletion_method_default' => 'fifo',
                    'depletion_methods' => [
                        'fifo' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'track_age' => true,
                            'auto_select' => true,
                            'min_age_days' => 0,
                            'max_age_days' => null,
                            'prefer_older_batches' => true,
                            'age_calculation_method' => 'start_date', // start_date, created_at
                            'batch_selection_criteria' => [
                                'primary' => 'age', // age, quantity, health_status
                                'secondary' => 'quantity',
                                'tertiary' => 'health_status'
                            ],
                            'quantity_distribution' => [
                                'method' => 'proportional', // proportional, sequential, balanced
                                'allow_partial_batch_depletion' => true,
                                'min_batch_remaining' => 0,
                                'preserve_batch_integrity' => false,
                                'max_batches_per_operation' => 5, // Limit to single batch for simplicity
                                'force_single_batch' => true, // Force single batch usage
                                'prefer_complete_batch_depletion' => false // Allow partial depletion
                            ],
                            'validation_rules' => [
                                'check_batch_availability' => true,
                                'validate_quantity_limits' => true,
                                'check_batch_status' => true,
                                'require_active_batches_only' => true
                            ],
                            'performance_optimization' => [
                                'cache_batch_queries' => true,
                                'batch_query_limit' => 100,
                                'use_indexed_queries' => true,
                                'parallel_processing' => false
                            ],
                            'audit_trail' => [
                                'track_depletion_history' => true,
                                'store_batch_snapshots' => true,
                                'log_decision_factors' => true,
                                'include_config_snapshot' => true
                            ]
                        ],
                        'lifo' => [
                            'enabled' => false,
                            'status' => 'development', // development, ready, not_applicable
                            'track_age' => false,
                            'auto_select' => false,
                        ],
                        'manual' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'history_enabled' => false,
                            'track_age' => true,
                            'auto_select' => false,
                            'show_batch_details' => true,
                            'require_selection' => true,
                        ],
                    ],
                    'mutation_method_default' => 'fifo',
                    'mutation_methods' => [
                        'fifo' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'track_age' => true,
                            'auto_select' => true,
                            'min_age_days' => 0,
                            'max_age_days' => null,
                            'prefer_older_batches' => true,
                            'age_calculation_method' => 'start_date', // start_date, created_at
                            'input_restrictions' => [
                                // allow_same_day_repeated_input: Mengizinkan input mutasi berulang pada hari yang sama
                                // - true: User dapat melakukan multiple mutasi pada tanggal yang sama
                                // - false: Hanya satu mutasi per hari yang diizinkan
                                'allow_same_day_repeated_input' => true,

                                // allow_same_livestock_repeated_input: Mengizinkan input mutasi berulang untuk livestock yang sama
                                // - true: User dapat melakukan multiple mutasi untuk livestock yang sama
                                // - false: Setiap livestock hanya dapat dimutasi sekali per operasi
                                'allow_same_livestock_repeated_input' => true,

                                // allow_same_livestock_same_day: Mengizinkan mutasi livestock yang sama pada hari yang sama
                                // - true: User dapat melakukan multiple mutasi untuk livestock yang sama pada hari yang sama
                                // - false: Jika livestock sudah dimutasi pada hari yang sama, harus edit mutasi sebelumnya
                                'allow_same_livestock_same_day' => false,

                                // batch_selection_control: Kontrol pemilihan batch dalam sistem FIFO
                                // - 'automatic': Sistem otomatis memilih batch berdasarkan FIFO (user tidak dapat memilih)
                                // - 'preview_only': User dapat melihat preview batch yang akan dipilih tapi tidak dapat mengubah
                                // - 'manual_override': User dapat override pemilihan batch dengan alasan khusus
                                'batch_selection_control' => 'automatic',

                                // allow_batch_override_reason: Mengizinkan override pemilihan batch dengan alasan
                                // - true: User dapat memberikan alasan untuk override batch selection
                                // - false: Tidak ada override yang diizinkan
                                'allow_batch_override_reason' => false,

                                'allow_same_livestock_repeated_input' => true,
                                'max_mutation_per_day_per_batch' => 10,
                                'max_mutation_per_day_per_livestock' => 10,
                                'require_unique_purpose' => false,
                                'min_interval_minutes' => 0,
                                'max_entries_per_session' => 20,
                                'prevent_duplicate_stocks' => true,
                                'allow_partial_stock_usage' => true,
                                'require_stock_availability_check' => true,
                                'max_stock_age_days' => 365,
                                'warn_on_old_stock' => true,
                                'old_stock_threshold_days' => 90,
                            ],
                            'batch_selection_criteria' => [
                                'primary' => 'age', // age, quantity, health_status
                                'secondary' => 'quantity',
                                'tertiary' => 'health_status'
                            ],
                            'quantity_distribution' => [
                                'method' => 'sequential', // proportional, sequential, balanced
                                'allow_partial_batch_depletion' => true,
                                'min_batch_remaining' => 0,
                                'preserve_batch_integrity' => false,
                                'max_batches_per_operation' => 1, // Limit to single batch for simplicity
                                'force_single_batch' => true, // Force single batch usage
                                'prefer_complete_batch_depletion' => false // Allow partial depletion
                            ],
                            'validation_rules' => [
                                'check_batch_availability' => true,
                                'validate_quantity_limits' => true,
                                'check_batch_status' => true,
                                'require_active_batches_only' => true
                            ],
                            'performance_optimization' => [
                                'cache_batch_queries' => true,
                                'batch_query_limit' => 100,
                                'use_indexed_queries' => true,
                                'parallel_processing' => false
                            ],
                            'audit_trail' => [
                                'track_depletion_history' => true,
                                'store_batch_snapshots' => true,
                                'log_decision_factors' => true,
                                'include_config_snapshot' => true
                            ]
                        ],
                        'lifo' => [
                            'enabled' => false,
                            'status' => 'development', // development, ready, not_applicable
                            'track_age' => false,
                            'auto_select' => false,
                        ],
                        'manual' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'history_enabled' => false,
                            'track_age' => true,
                            'auto_select' => false,
                            'show_batch_details' => true,
                            'require_selection' => true,
                            'input_restrictions' => [
                                'allow_same_day_repeated_input' => true,
                                'allow_same_batch_repeated_input' => true,
                                'max_mutation_per_day_per_batch' => 50,
                                'max_mutation_per_day_per_livestock' => 100,
                                'require_unique_purpose' => false,
                                'min_interval_minutes' => 0,
                                'max_entries_per_session' => 20,
                                'prevent_duplicate_stocks' => true,
                                'allow_partial_stock_usage' => true,
                                'require_stock_availability_check' => true,
                                'max_stock_age_days' => 365,
                                'warn_on_old_stock' => true,
                                'old_stock_threshold_days' => 90,
                            ],
                        ],
                    ],
                    'feed_usage_method_default' => 'fifo',
                    'feed_usage_methods' => [
                        'fifo' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'track_age' => true,
                            'auto_select' => true,
                            'min_age_days' => 0,
                            'max_age_days' => null,
                            'prefer_older_batches' => true,
                            'age_calculation_method' => 'start_date', // start_date, created_at
                            'batch_selection_criteria' => [
                                'primary' => 'age', // age, quantity, health_status
                                'secondary' => 'quantity',
                                'tertiary' => 'health_status'
                            ],
                            'quantity_distribution' => [
                                'method' => 'sequential', // proportional, sequential, balanced
                                'allow_partial_batch_depletion' => true,
                                'min_batch_remaining' => 0,
                                'preserve_batch_integrity' => false,
                                'max_batches_per_operation' => 1, // Limit to single batch for simplicity
                                'force_single_batch' => true, // Force single batch usage
                                'prefer_complete_batch_depletion' => false // Allow partial depletion
                            ],
                            'validation_rules' => [
                                'check_batch_availability' => true,
                                'validate_quantity_limits' => true,
                                'check_batch_status' => true,
                                'require_active_batches_only' => true
                            ],
                            'performance_optimization' => [
                                'cache_batch_queries' => true,
                                'batch_query_limit' => 100,
                                'use_indexed_queries' => true,
                                'parallel_processing' => false
                            ],
                            'audit_trail' => [
                                'track_depletion_history' => true,
                                'store_batch_snapshots' => true,
                                'log_decision_factors' => true,
                                'include_config_snapshot' => true
                            ]
                        ],
                        'lifo' => [
                            'enabled' => false,
                            'status' => 'development', // development, ready, not_applicable
                            'track_age' => false,
                            'auto_select' => false,
                        ],
                        'manual' => [
                            'enabled' => true,
                            'status' => 'ready', // development, ready, not_applicable
                            'track_age' => true,
                            'auto_select' => false,
                            'show_batch_details' => true,
                            'require_selection' => true,
                        ],
                    ],
                    'batch_tracking' => [
                        'enabled' => true,
                        'track_individual_batches' => true,
                        'track_batch_performance' => true,
                        'batch_aging' => true,
                    ],
                    'validation_rules' => [
                        'require_batch_selection' => true,
                        'allow_partial_batch_usage' => true,
                        'max_batches_per_recording' => 5,
                        'min_batch_quantity' => 1,
                    ],
                ],
            ],
            'lifecycle_management' => [
                'enabled' => true,
                'stages' => [
                    'arrival' => [
                        'enabled' => true,
                        'require_health_check' => true,
                        'require_initial_weight' => true,
                        'require_initial_count' => true,
                    ],
                    'growth' => [
                        'enabled' => true,
                        'weight_monitoring' => true,
                        'health_monitoring' => true,
                        'feed_monitoring' => true,
                    ],
                    'harvest' => [
                        'enabled' => true,
                        'require_weight_final' => true,
                        'require_health_final' => true,
                        'require_harvest_date' => true,
                    ],
                ],
                'age_tracking' => [
                    'enabled' => true,
                    'calculate_from' => 'start_date',
                    'age_units' => 'days',
                    'track_age_by_batch' => true,
                ],
            ],
            'health_management' => [
                'enabled' => true,
                'vaccination_tracking' => [
                    'enabled' => true,
                    'require_vaccination_schedule' => true,
                    'track_vaccination_history' => true,
                ],
                'disease_tracking' => [
                    'enabled' => true,
                    'require_disease_reporting' => true,
                    'track_treatment_history' => true,
                ],
                'medication_tracking' => [
                    'enabled' => true,
                    'require_prescription' => true,
                    'track_medication_history' => true,
                ],
            ],
            'depletion_tracking' => [
                'enabled' => true,
                'types' => [
                    'mortality' => [
                        'enabled' => true,
                        'track_by_batch' => true,
                        'batch_attribution' => 'auto',
                    ],
                    'culling' => [
                        'enabled' => true,
                        'require_reason' => true,
                        'track_by_batch' => true,
                        'batch_attribution' => 'manual',
                    ],
                    'sales' => [
                        'enabled' => true,
                        'require_price' => true,
                        'track_by_batch' => true,
                        'batch_attribution' => 'manual',
                    ],
                ],
                'input_restrictions' => [
                    'allow_same_day_repeated_input' => true,
                    'allow_same_batch_repeated_input' => true,
                    'max_depletion_per_day_per_batch' => 10,
                    'require_unique_reason' => false,
                    'allow_zero_quantity' => false,
                    'min_interval_minutes' => 0,
                ],
            ],
            'weight_tracking' => [
                'enabled' => true,
                'unit' => 'gram',
                'precision' => 2,
                'weight_gain_calculation' => true,
                'track_by_batch' => true,
                'batch_weight_method' => 'average',
            ],
            'feed_tracking' => [
                'enabled' => true,
                'require_feed_type' => true,
                'require_quantity' => true,
                'fcr_calculation' => true,
                'track_by_batch' => true,
                'batch_feed_allocation' => 'proportional',
            ],
            'feed_usage' => [
                'enabled' => true,
                'methods' => [
                    'auto' => [
                        'enabled' => true,
                        'method' => 'fifo', // fifo, lifo
                        'require_batch_selection' => false,
                    ],
                    'manual' => [
                        'enabled' => true,
                        'require_batch_selection' => true,
                        'allow_multiple_batches' => true,
                        'validation_rules' => [
                            'require_usage_date' => true,
                            'require_usage_purpose' => true,
                            'require_quantity' => true,
                            'require_notes' => false,
                            'min_quantity' => 0.1,
                            'max_quantity' => 10000,
                            'allow_zero_quantity' => false,
                        ],
                        'input_restrictions' => [
                            'allow_same_day_repeated_input' => true,
                            'allow_same_batch_repeated_input' => true,
                            'max_usage_per_day_per_batch' => 50,
                            'max_usage_per_day_per_livestock' => 100,
                            'require_unique_purpose' => false,
                            'min_interval_minutes' => 0,
                            'max_entries_per_session' => 20,
                            'prevent_duplicate_stocks' => true,
                            'allow_partial_stock_usage' => true,
                            'require_stock_availability_check' => true,
                            'max_stock_age_days' => 365,
                            'warn_on_old_stock' => true,
                            'old_stock_threshold_days' => 90,
                        ],
                        // Pengaturan alur kerja (workflow) untuk antarmuka pengguna (UI) pada fitur penggunaan pakan manual.
                        'workflow_settings' => [
                            // Jika `true`, akan ada langkah pratinjau (review) sebelum data final disimpan. Berguna untuk verifikasi.
                            'enable_preview_step' => true,
                            // Jika `true`, pengguna harus memberikan konfirmasi akhir (misal: via modal/pop-up) sebelum data disubmit.
                            'require_confirmation' => true,
                            // Jika `true`, form yang sedang diisi akan disimpan sebagai draf secara otomatis. `false` untuk menonaktifkan.
                            'auto_save_draft' => false,
                            // Jika `true`, informasi detail mengenai batch (kandang, strain, dll.) akan ditampilkan di UI.
                            'enable_batch_info_display' => true,
                            // Jika `true`, detail stok pakan yang dipilih (misal: tanggal masuk, kuantitas) akan ditampilkan.
                            'show_stock_details' => true,
                            // Jika `true`, informasi biaya yang terkait dengan penggunaan pakan akan ditampilkan kepada pengguna.
                            'show_cost_information' => true,
                            // Jika `true`, pengguna dapat melihat riwayat penggunaan pakan sebelumnya dari dalam komponen.
                            'enable_usage_history' => true,
                        ],
                        'batch_selection' => [
                            'show_batch_details' => true,
                            'show_current_quantity' => true,
                            'show_age_information' => true,
                            'show_coop_information' => true,
                            'show_strain_information' => true,
                            'enable_batch_filtering' => true,
                            'default_sort' => 'age_asc', // age_asc, age_desc, quantity_asc, quantity_desc
                            'hide_inactive_batches' => true,
                            'min_batch_quantity' => 1,
                        ],
                        'stock_selection' => [
                            'show_stock_details' => true,
                            'show_availability' => true,
                            'show_cost_per_unit' => true,
                            'show_batch_info' => true,
                            'show_age_days' => true,
                            'enable_stock_filtering' => true,
                            'default_sort' => 'age_asc',
                            'hide_unavailable_stocks' => true,
                            'warn_on_low_stock' => true,
                            'low_stock_threshold' => 10,
                        ],
                        // Pengaturan mode edit untuk menentukan bagaimana data existing akan diproses
                        'edit_mode_settings' => [
                            // Strategi edit: 'update' = update data existing, 'delete_recreate' = hapus dan buat baru
                            'edit_strategy' => 'update', // 'update' atau 'delete_recreate'

                            // Jika menggunakan 'delete_recreate', tentukan jenis delete
                            'delete_strategy' => 'soft', // 'soft' atau 'hard'

                            // Jika true, akan membuat backup data sebelum edit (untuk audit trail)
                            'create_backup_before_edit' => true,

                            // Jika true, akan menyimpan metadata tentang operasi edit
                            'track_edit_operations' => true,

                            // Pengaturan khusus untuk soft delete
                            'soft_delete_settings' => [
                                // Jika true, soft delete akan menambah usage count di database
                                'increment_usage_count' => true,
                                // Reason untuk soft delete
                                'default_delete_reason' => 'edited',
                                // Apakah menyimpan original data di metadata
                                'preserve_original_data' => true,
                            ],

                            // Pengaturan khusus untuk hard delete
                            'hard_delete_settings' => [
                                // Jika true, akan memvalidasi referensi sebelum hard delete
                                'validate_references' => true,
                                // Jika true, akan restore stock quantities sebelum delete
                                'restore_stock_quantities' => true,
                                // Jika true, akan update livestock totals sebelum delete
                                'update_livestock_totals' => true,
                            ],

                            // Pengaturan untuk update strategy
                            'update_settings' => [
                                // Jika true, akan membandingkan data lama vs baru untuk audit
                                'track_field_changes' => true,
                                // Jika true, akan memvalidasi business rules saat update
                                'validate_business_rules' => true,
                                // Jika true, akan update timestamps
                                'update_timestamps' => true,
                            ],

                            // Notifikasi untuk edit operations
                            'notifications' => [
                                'notify_on_edit' => true,
                                'notify_on_delete_recreate' => true,
                                'include_change_summary' => true,
                            ],
                        ],
                    ],
                ],
                'tracking' => [
                    'enabled' => true,
                    'track_by_batch' => true,
                    'track_usage_history' => true,
                    'track_cost_per_usage' => true,
                    'track_efficiency_metrics' => true,
                    'enable_analytics' => true,
                ],
                'notifications' => [
                    'enabled' => true,
                    'notify_on_completion' => true,
                    'notify_on_low_stock' => true,
                    'notify_on_old_stock_usage' => true,
                    'notify_on_high_usage' => false,
                ],
            ],
            'performance_metrics' => [
                'enabled' => true,
                'metrics' => [
                    'fcr' => true,
                    'ip' => true,
                    'adg' => true,
                    'mortality_rate' => true,
                    'liveability' => true,
                ],
                'calculation_frequency' => 'daily',
                'track_by_batch' => true,
            ],
            'cost_tracking' => [
                'enabled' => true,
                'purchase_cost' => [
                    'enabled' => true,
                    'include_transport' => true,
                    'track_unit_cost' => true,
                ],
                'operational_cost' => [
                    'enabled' => true,
                    'feed_cost' => true,
                    'medical_cost' => true,
                ],
                'profitability_analysis' => [
                    'enabled' => true,
                    'include_all_costs' => true,
                    'calculate_roi' => true,
                ],
            ],
            'validation_rules' => [
                'enabled' => false,
                'require_farm' => true,
                'require_coop' => true,
                'require_start_date' => true,
                'require_initial_quantity' => true,
                'min_quantity' => 1,
                'max_quantity' => 100000,
            ],
            'reporting' => [
                'enabled' => true,
                'reports' => [
                    'inventory_report' => ['enabled' => true, 'frequency' => 'daily'],
                    'performance_report' => ['enabled' => true, 'frequency' => 'weekly'],
                    'cost_report' => ['enabled' => true, 'frequency' => 'monthly'],
                    'health_report' => ['enabled' => true, 'frequency' => 'weekly'],
                ],
                'dashboards' => [
                    'enabled' => true,
                    'real_time_monitoring' => true,
                    'alert_thresholds' => [
                        'mortality_rate' => 5,
                        'weight_gain' => 0.05,
                        'health_incidents' => 10,
                    ],
                ],
            ],
            'documentation' => [
                'enabled' => true,
                'require_notes' => false,
                'require_photos' => false,
                'batch_documentation' => [
                    'enabled' => true,
                    'track_batch_history' => true,
                ],
                'health_documentation' => [
                    'enabled' => true,
                    'track_health_incidents' => true,
                    'track_treatments' => true,
                ],
            ],
        ];
    }

    /**
     * Get default mutation configuration
     */
    public static function getDefaultMutationConfig(): array
    {
        return [
            'livestock_mutation' => [
                'type' => 'batch', // 'batch' or 'fifo'
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                    'allow_multiple_batches' => false,
                ],
                'fifo_settings' => [
                    'enabled' => true,
                    'track_age' => true,
                    'min_age_days' => 0,
                    'max_age_days' => null,
                ],
                'validation_rules' => [
                    'enabled' => false,
                    'require_weight' => true,
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                    'max_mutation_percentage' => 100,
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
            'feed_mutation' => [
                'type' => 'batch',
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                ],
                'validation_rules' => [
                    'enabled' => false,
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                    'max_mutation_percentage' => 100,
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
            'supply_mutation' => [
                'type' => 'batch',
                'batch_settings' => [
                    'tracking_enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                ],
                'validation_rules' => [
                    'enabled' => false,
                    'require_quantity' => true,
                    'allow_partial_mutation' => true,
                    'max_mutation_percentage' => 100,
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
        ];
    }

    /**
     * Get default usage configuration
     */
    public static function getDefaultUsageConfig(): array
    {
        return [
            'livestock_usage' => [
                'enabled' => true,
                'validation_rules' => [
                    'enabled' => false,
                    'require_farm' => true,
                    'require_kandang' => true,
                    'require_breed' => true,
                    'require_quantity' => true,
                    'require_weight' => true,
                    'require_unit' => true,
                ],
                'batch_settings' => [
                    'enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
            'feed_usage' => [
                'enabled' => true,
                'validation_rules' => [
                    'enabled' => false,
                    'require_farm' => true,
                    'require_kandang' => true,
                    'require_feed' => true,
                    'require_quantity' => true,
                    'require_unit' => true,
                ],
                'batch_settings' => [
                    'enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
            'supply_usage' => [
                'enabled' => true,
                'validation_rules' => [
                    'enabled' => false,
                    'require_farm' => true,
                    'require_kandang' => true,
                    'require_supply' => true,
                    'require_quantity' => true,
                    'require_unit' => true,
                ],
                'batch_settings' => [
                    'enabled' => true,
                    'require_batch_number' => true,
                    'auto_generate_batch' => true,
                    'batch_number_format' => 'YEAR-SEQ',
                ],
                'document_settings' => [
                    'require_do_number' => true,
                    'require_invoice' => true,
                    'require_receipt' => true,
                ],
            ],
        ];
    }

    /**
     * Get default notification configuration
     */
    public static function getDefaultNotificationConfig(): array
    {
        return [
            'channels' => [
                'email' => true,
                'database' => true,
                'broadcast' => true,
            ],
            'events' => [
                'purchase' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                    'notify_on' => ['created', 'updated', 'approved', 'rejected'],
                ],
                'mutation' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                    'notify_on' => ['created', 'updated', 'approved', 'rejected'],
                ],
                'usage' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                    'notify_on' => ['created', 'updated', 'approved', 'rejected'],
                ],
                'batch_completion' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
                'low_stock' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
                'age_threshold' => [
                    'enabled' => true,
                    'channels' => ['email', 'database'],
                ],
            ],
        ];
    }

    /**
     * Get default reporting configuration
     */
    public static function getDefaultReportingConfig(): array
    {
        return [
            'default_period' => 'monthly',
            'available_periods' => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
            'export_formats' => ['pdf', 'excel', 'csv'],
            'auto_generate' => [
                'enabled' => false,
                'schedule' => 'monthly',
            ],
            'retention_period' => 365, // days
            'reports' => [
                'purchase' => [
                    'enabled' => true,
                    'types' => ['livestock', 'feed', 'supply'],
                    'metrics' => ['quantity', 'value', 'frequency'],
                ],
                'mutation' => [
                    'enabled' => true,
                    'types' => ['livestock', 'feed', 'supply'],
                    'metrics' => ['quantity', 'value', 'frequency'],
                ],
                'usage' => [
                    'enabled' => true,
                    'types' => ['livestock', 'feed', 'supply'],
                    'metrics' => ['quantity', 'value', 'frequency'],
                ],
            ],
        ];
    }

    /**
     * Get active config section or sub config (safe for user/system)
     * @param string $section
     * @param string|null $subSection
     * @return array
     */
    public static function getActiveConfigSection(string $section, string $subSection): array
    {
        $config = self::getDefaultActiveConfig();
        if (!isset($config[$section])) {
            return [];
        }
        if ($subSection === null) {
            return $config[$section];
        }
        if (isset($config[$section][$subSection])) {
            return $config[$section][$subSection];
        }
        return [];
    }

    /**
     * Get manual feed usage config from active config
     */
    public static function getManualFeedUsageConfig(): array
    {
        return self::getActiveConfigSection('livestock', 'feed_usage_methods')['manual'] ?? [];
    }

    /**
     * Get manual feed usage input restrictions
     * @return array
     */
    public static function getManualFeedUsageInputRestrictions(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['input_restrictions'] ?? [];
    }

    /**
     * Get manual feed usage validation rules
     * @return array
     */
    public static function getManualFeedUsageValidationRules(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['validation_rules'] ?? [];
    }

    /**
     * Get manual feed usage workflow settings
     * @return array
     */
    public static function getManualFeedUsageWorkflowSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['workflow_settings'] ?? [];
    }

    /**
     * Get manual feed usage batch selection settings
     * @return array
     */
    public static function getManualFeedUsageBatchSelectionSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['batch_selection'] ?? [];
    }

    /**
     * Get manual feed usage stock selection settings
     * @return array
     */
    public static function getManualFeedUsageStockSelectionSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['stock_selection'] ?? [];
    }

    /**
     * Get manual feed usage edit mode settings
     *
     * @return array
     */
    public static function getManualFeedUsageEditModeSettings(): array
    {
        $config = self::getManualFeedUsageConfig();
        return $config['edit_mode_settings'] ?? [
            'edit_strategy' => 'update',
            'delete_strategy' => 'soft',
            'create_backup_before_edit' => true,
            'track_edit_operations' => true,
            'soft_delete_settings' => [
                'increment_usage_count' => true,
                'default_delete_reason' => 'edited',
                'preserve_original_data' => true,
            ],
            'hard_delete_settings' => [
                'validate_references' => true,
                'restore_stock_quantities' => true,
                'update_livestock_totals' => true,
            ],
            'update_settings' => [
                'track_field_changes' => true,
                'validate_business_rules' => true,
                'update_timestamps' => true,
            ],
            'notifications' => [
                'notify_on_edit' => true,
                'notify_on_delete_recreate' => true,
                'include_change_summary' => true,
            ],
        ];
    }

    /**
     * Get user-configurable settings (settings that can be modified by users)
     * These are settings that are safe for users to modify without breaking the application
     */
    public static function getUserConfigurableSettings(): array
    {
        return [
            'livestock' => [
                'user_editable_paths' => [
                    // User can modify these specific method selections
                    'recording_method.batch_settings.depletion_method_default',
                    'recording_method.batch_settings.mutation_method_default',
                    'recording_method.batch_settings.feed_usage_method_default',

                    // User can enable/disable certain features (but not modify core config)
                    'lifecycle_management.enabled',
                    'health_management.vaccination_tracking.enabled',
                    'health_management.disease_tracking.enabled',
                    'performance_metrics.enabled',
                    'cost_tracking.enabled',
                    'reporting.enabled',

                    // User can modify validation rules
                    'validation_rules.require_farm',
                    'validation_rules.require_coop',
                    'validation_rules.min_quantity',
                    'validation_rules.max_quantity',

                    // User can modify some feed usage settings
                    'feed_usage.methods.manual.validation_rules.require_usage_date',
                    'feed_usage.methods.manual.validation_rules.require_usage_purpose',
                    'feed_usage.methods.manual.validation_rules.min_quantity',
                    'feed_usage.methods.manual.validation_rules.max_quantity',
                    'feed_usage.methods.manual.input_restrictions.allow_same_day_repeated_input',
                    'feed_usage.methods.manual.input_restrictions.max_usage_per_day_per_batch',
                    'feed_usage.methods.manual.workflow_settings.enable_preview_step',
                    'feed_usage.methods.manual.workflow_settings.require_confirmation',
                ],
                'user_editable_values' => [
                    // Allowed values for user-editable settings
                    'recording_method.batch_settings.depletion_method_default' => ['fifo', 'manual'], // Only ready methods
                    'recording_method.batch_settings.depletion_methods.fifo.quantity_distribution.method' => ['sequential', 'proportional', 'balanced'],
                    'recording_method.batch_settings.depletion_methods.fifo.quantity_distribution.max_batches_per_operation' => [1, 2, 3, 4, 5],
                    'recording_method.batch_settings.mutation_method_default' => ['fifo'], // Only ready methods
                    'recording_method.batch_settings.feed_usage_method_default' => ['fifo', 'manual'], // Only ready methods
                ]
            ],
            'purchasing' => [
                'user_editable_paths' => [
                    // User can modify validation requirements
                    'livestock_purchase.validation_rules.require_strain',
                    'livestock_purchase.validation_rules.require_initial_weight',
                    'livestock_purchase.validation_rules.require_supplier',
                    'livestock_purchase.cost_tracking.enabled',
                    'livestock_purchase.cost_tracking.include_transport_cost',

                    // Batch settings that users can modify
                    'livestock_purchase.batch_settings.batch_creation.auto_create_batch',
                    'livestock_purchase.batch_settings.batch_creation.require_batch_name',
                ]
            ]
        ];
    }

    /**
     * Get developer/application-level settings (settings that should NOT be modified by users)
     * These are core settings that affect application behavior and security
     */
    public static function getDeveloperOnlySettings(): array
    {
        return [
            'livestock' => [
                'protected_paths' => [
                    // Core method configurations - users cannot modify enabled/status/auto_select
                    'recording_method.batch_settings.depletion_methods.*.enabled',
                    'recording_method.batch_settings.depletion_methods.*.status',
                    'recording_method.batch_settings.depletion_methods.*.auto_select',
                    'recording_method.batch_settings.mutation_methods.*.enabled',
                    'recording_method.batch_settings.mutation_methods.*.status',
                    'recording_method.batch_settings.mutation_methods.*.auto_select',
                    'recording_method.batch_settings.feed_usage_methods.*.enabled',
                    'recording_method.batch_settings.feed_usage_methods.*.status',
                    'recording_method.batch_settings.feed_usage_methods.*.auto_select',

                    // Core algorithm settings
                    'recording_method.batch_settings.depletion_methods.*.batch_selection_criteria',
                    'recording_method.batch_settings.depletion_methods.*.quantity_distribution',
                    'recording_method.batch_settings.depletion_methods.*.performance_optimization',
                    'recording_method.batch_settings.depletion_methods.*.audit_trail',

                    // Security and performance settings
                    'feed_usage.methods.manual.input_restrictions.prevent_duplicate_stocks',
                    'feed_usage.methods.manual.input_restrictions.require_stock_availability_check',
                    'feed_usage.methods.manual.edit_mode_settings',

                    // Core validation that affects data integrity
                    'validation_rules.enabled',
                    'recording_method.type',
                    'recording_method.allow_multiple_batches',
                ]
            ]
        ];
    }

    /**
     * Get configuration metadata indicating what can be modified by users
     */
    public static function getConfigMetadata(): array
    {
        return [
            'version' => '1.0',
            'last_updated' => '2025-06-23',
            'config_levels' => [
                'system' => [
                    'description' => 'Core application settings managed by developers',
                    'modifiable_by' => ['developer', 'system_admin'],
                    'requires_deployment' => true,
                ],
                'company' => [
                    'description' => 'Company-wide settings that can be configured by company admins',
                    'modifiable_by' => ['company_admin', 'system_admin'],
                    'requires_deployment' => false,
                ],
                'user' => [
                    'description' => 'User-level preferences and operational settings',
                    'modifiable_by' => ['user', 'company_admin', 'system_admin'],
                    'requires_deployment' => false,
                ]
            ],
            'validation_rules' => [
                'user_config_changes' => [
                    'must_validate_against_allowed_values' => true,
                    'must_check_method_availability' => true,
                    'must_log_changes' => true,
                    'must_backup_before_change' => true,
                ]
            ]
        ];
    }

    /**
     * Get manual depletion configuration
     */
    public static function getManualDepletionConfig(): array
    {
        $config = self::getActiveConfigSection('livestock', 'recording');
        return $config['depletion_methods']['manual'] ?? [
            'enabled' => true,
            'status' => 'ready',
            'history_enabled' => false,
            'track_age' => true,
            'auto_select' => false,
            'show_batch_details' => true,
            'require_selection' => true,
        ];
    }

    /**
     * Get manual depletion history settings
     */
    public static function getManualDepletionHistorySettings(): array
    {
        $config = self::getManualDepletionConfig();
        return [
            'history_enabled' => $config['history_enabled'] ?? false,
            'preserve_original_records' => $config['preserve_original_records'] ?? false,
            'track_edit_history' => $config['track_edit_history'] ?? true,
            'max_history_entries' => $config['max_history_entries'] ?? 10,
        ];
    }

    /**
     * Get manual mutation configuration
     */
    public static function getManualMutationConfig(): array
    {
        // Get company-specific settings
        $companyConfig = auth()->user()->company->config ?? [];
        $livestockConfig = $companyConfig['livestock'] ?? [];

        return $livestockConfig['manual_mutation'] ?? [
            'enabled' => true,
            'default_method' => 'manual',
            'supported_methods' => ['manual', 'fifo', 'lifo'],
            'validation_rules' => [
                'require_destination' => true,
                'require_reason' => false,
                'min_quantity' => 1,
                'max_quantity_percentage' => 100,
                'validate_batch_availability' => true,
                'allow_partial_mutation' => true
            ],
            'batch_settings' => [
                'track_age' => true,
                'show_batch_details' => true,
                'show_utilization_rate' => true,
                'auto_assign_batch' => true,
                'require_batch_selection' => false
            ],
            'workflow_settings' => [
                'require_confirmation' => true,
                'show_preview' => true,
                'auto_close_modal' => true,
                'notification_enabled' => true
            ],
            'edit_mode_settings' => [
                'enabled' => true,
                'auto_load_existing' => true,
                'show_edit_banner' => true,
                'allow_cancel' => true
            ],
            'history_settings' => [
                'history_enabled' => false, // Default to false for backward compatibility
                'strategy' => 'update_existing', // 'update_existing' or 'delete_and_create'
                'audit_trail' => true,
                'backup_before_edit' => true,
                'max_backups' => 10
            ]
        ];
    }

    /**
     * Get manual mutation configuration
     */
    public static function getFifoMutationConfig(): array
    {
        // Get company-specific settings
        $companyConfig = auth()->user()->company->config ?? [];
        $livestockConfig = $companyConfig['livestock'] ?? [];

        return $livestockConfig['fifo_mutation'] ?? [
            'enabled' => true,
            'default_method' => 'fifo',
            'supported_methods' => ['manual', 'fifo', 'lifo'],
            'input_restrictions' => [
                'allow_same_day_repeated_input' => true,
                'allow_same_livestock_repeated_input' => true,
                'allow_same_livestock_same_day' => false,
                'allow_same_batch_same_day' => false,
            ],
            'validation_rules' => [
                'require_destination' => true,
                'require_reason' => false,
                'min_quantity' => 1,
                'max_quantity_percentage' => 100,
                'validate_batch_availability' => true,
                'allow_partial_mutation' => true
            ],
            'batch_settings' => [
                'track_age' => true,
                'show_batch_details' => true,
                'show_utilization_rate' => true,
                'auto_assign_batch' => true,
                'require_batch_selection' => false
            ],
            'workflow_settings' => [
                'require_confirmation' => true,
                'show_preview' => true,
                'auto_close_modal' => true,
                'notification_enabled' => true
            ],
            'edit_mode_settings' => [
                'enabled' => true,
                'auto_load_existing' => true,
                'show_edit_banner' => true,
                'allow_cancel' => true
            ],
            'history_settings' => [
                'history_enabled' => false, // Default to false for backward compatibility
                'strategy' => 'update_existing', // 'update_existing' or 'delete_and_create'
                'audit_trail' => true,
                'backup_before_edit' => true,
                'max_backups' => 10
            ]
        ];
    }

    /**
     * Get manual mutation history settings
     */
    public static function getManualMutationHistorySettings(): array
    {
        // Get company-specific settings
        $companyConfig = auth()->user()->company->config ?? [];
        $livestockConfig = $companyConfig['livestock'] ?? [];
        $mutationConfig = $livestockConfig['manual_mutation'] ?? [];

        return $mutationConfig['history_settings'] ?? [
            'history_enabled' => false, // Default to false for backward compatibility
            'strategy' => 'update_existing', // 'update_existing' or 'delete_and_create'
            'audit_trail' => true,
            'backup_before_edit' => true,
            'max_backups' => 10
        ];
    }

    /**
     * Get manual mutation validation rules
     */
    public static function getManualMutationValidationRules(): array
    {
        $config = self::getManualMutationConfig();
        return $config['validation_rules'] ?? [];
    }

    public static function getFifoMutationValidationRules(): array
    {
        $config = self::getFifoMutationConfig();
        return $config['validation_rules'] ?? [];
    }

    /**
     * Get manual mutation workflow settings
     */
    public static function getManualMutationWorkflowSettings(): array
    {
        $config = self::getManualMutationConfig();
        return $config['workflow_settings'] ?? [];
    }

    public static function getFifoMutationWorkflowSettings(): array
    {
        $config = self::getFifoMutationConfig();
        return $config['workflow_settings'] ?? [];
    }

    /**
     * Get manual mutation batch settings
     */
    public static function getManualMutationBatchSettings(): array
    {
        $config = self::getManualMutationConfig();
        return $config['batch_settings'] ?? [];
    }

    /**
     * Get manual mutation edit mode settings
     */
    public static function getManualMutationEditModeSettings(): array
    {
        $config = self::getManualMutationConfig();
        return $config['edit_mode_settings'] ?? [];
    }
}
