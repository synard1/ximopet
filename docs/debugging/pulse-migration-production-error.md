# [2024-06-09 15:30] Pulse & Telescope Migration Error in Production

## Issue

Error: Class "Laravel\Pulse\Support\PulseMigration" not found saat menjalankan migrate di production.

## Root Cause

File migrasi Pulse/Telescope tetap ada di production, padahal package Pulse/Telescope tidak diinstall di production.

## Solution

-   Migrasi Pulse/Telescope dipindahkan ke folder khusus dan hanya di-load di local/dev.
-   File migrasi Pulse/Telescope dihapus dari production.
-   Deployment pipeline diupdate untuk skip migrasi Pulse/Telescope di production.

## Impact

Tidak ada error migrasi Pulse/Telescope di production. Deployment lebih aman dan robust.

## Related Files

-   database/migrations/pulse/2023_06_07_000001_create_pulse_tables.php
-   database/migrations/telescope/2018_08_08_100000_create_telescope_entries_table.php
-   app/Providers/AppServiceProvider.php

## Log

[2024-06-09 15:30] Migrasi Pulse/Telescope tidak ditemukan di production. Deployment OK.
