# Dokumentasi Implementasi Progress Modal
## Laravel 11 & Livewire 3

### 📋 Daftar Isi
1. [Persyaratan](#persyaratan)
2. [Instalasi](#instalasi)
3. [Struktur File](#struktur-file)
4. [Implementasi Backend](#implementasi-backend)
5. [Implementasi Frontend](#implementasi-frontend)
6. [Konfigurasi](#konfigurasi)
7. [Cara Penggunaan](#cara-penggunaan)
8. [Troubleshooting](#troubleshooting)

---

## 📋 Persyaratan

### Software Requirements
- PHP 8.1+
- Laravel 11
- Livewire 3
- Bootstrap 5.3+
- Font Awesome 6.4+

### Dependencies
```bash
composer require livewire/livewire
npm install bootstrap@5.3.0
```

---

## 🚀 Instalasi

### 1. Install Livewire 3
```bash
composer require livewire/livewire
php artisan livewire:publish --config
```

### 2. Publish Livewire Assets
```bash
php artisan livewire:publish --assets
```

### 3. Buat Livewire Component
```bash
php artisan make:livewire DataProcessor
```

---

## 📁 Struktur File

```
app/
├── Livewire/
│   ├── DataProcessor.php
│   └── Traits/
│       └── HasProgressTracking.php
resources/
├── views/
│   ├── livewire/
│   │   └── data-processor.blade.php
│   ├── components/
│   │   └── progress-modal.blade.php
│   └── layouts/
│       └── app.blade.php
public/
├── css/
│   └── progress-modal.css
└── js/
    └── progress-modal.js
```

---

## 🔧 Implementasi Backend

### 1. Buat Trait untuk Progress Tracking

**File: `app/Livewire/Traits/HasProgressTracking.php`**

```php
<?php

namespace App\Livewire\Traits;

trait HasProgressTracking
{
    public $progressSteps = [];
    public $currentStep = 0;
    public $totalSteps = 0;
    public $isProcessing = false;

    public function initializeProgressTracking()
    {
        $this->progressSteps = [
            1 => 'Validasi data input',
            2 => 'Memeriksa koneksi database',
            3 => 'Mempersiapkan transaksi',
            4 => 'Menyimpan data utama',
            5 => 'Memproses data relasi',
            6 => 'Mengupdate index pencarian',
            7 => 'Membuat backup otomatis',
            8 => 'Mengirim notifikasi',
            9 => 'Membersihkan cache',
            10 => 'Menyelesaikan proses'
        ];
        
        $this->totalSteps = count($this->progressSteps);
        $this->currentStep = 0;
        $this->isProcessing = false;
    }

    public function startProgress()
    {
        $this->isProcessing = true;
        $this->currentStep = 0;
        $this->dispatch('startProgress');
    }

    public function updateProgress($step, $description = null)
    {
        $this->currentStep = $step;
        
        $this->dispatch('updateProgress', [
            'current' => $this->currentStep,
            'total' => $this->totalSteps,
            'description' => $description ?? $this->progressSteps[$step] ?? 'Memproses...'
        ]);
    }

    public function completeProgress()
    {
        $this->isProcessing = false;
        $this->dispatch('completeProgress');
    }

    public function getProgressPercentage()
    {
        return $this->totalSteps > 0 ? round(($this->currentStep / $this->totalSteps) * 100) : 0;
    }
}
```

### 2. Buat Main Livewire Component

**File: `app/Livewire/DataProcessor.php`**

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Traits\HasProgressTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataProcessor extends Component
{
    use HasProgressTracking;

    public $data = [];
    public $result = null;

    public function mount()
    {
        $this->initializeProgressTracking();
    }

    public function processData()
    {
        try {
            $this->startProgress();
            
            // Step 1: Validasi data input
            $this->updateProgress(1);
            $this->validateInputData();
            sleep(1); // Simulasi delay
            
            // Step 2: Memeriksa koneksi database
            $this->updateProgress(2);
            $this->checkDatabaseConnection();
            sleep(1);
            
            // Step 3: Mempersiapkan transaksi
            $this->updateProgress(3);
            DB::beginTransaction();
            sleep(1);
            
            // Step 4: Menyimpan data utama
            $this->updateProgress(4);
            $this->saveMainData();
            sleep(1);
            
            // Step 5: Memproses data relasi
            $this->updateProgress(5);
            $this->processRelationalData();
            sleep(1);
            
            // Step 6: Mengupdate index pencarian
            $this->updateProgress(6);
            $this->updateSearchIndex();
            sleep(1);
            
            // Step 7: Membuat backup otomatis
            $this->updateProgress(7);
            $this->createBackup();
            sleep(1);
            
            // Step 8: Mengirim notifikasi
            $this->updateProgress(8);
            $this->sendNotifications();
            sleep(1);
            
            // Step 9: Membersihkan cache
            $this->updateProgress(9);
            $this->clearCache();
            sleep(1);
            
            // Step 10: Menyelesaikan proses
            $this->updateProgress(10);
            DB::commit();
            
            $this->result = 'Data berhasil diproses!';
            $this->completeProgress();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Process failed: ' . $e->getMessage());
            $this->dispatch('processError', ['message' => $e->getMessage()]);
        }
    }

    private function validateInputData()
    {
        // Implementasi validasi data
        if (empty($this->data)) {
            throw new \Exception('Data tidak boleh kosong');
        }
    }

    private function checkDatabaseConnection()
    {
        // Implementasi pengecekan koneksi database
        DB::connection()->getPdo();
    }

    private function saveMainData()
    {
        // Implementasi penyimpanan data utama
        // Contoh: User::create($this->data);
    }

    private function processRelationalData()
    {
        // Implementasi pemrosesan data relasi
    }

    private function updateSearchIndex()
    {
        // Implementasi update search index
    }

    private function createBackup()
    {
        // Implementasi backup otomatis
    }

    private function sendNotifications()
    {
        // Implementasi pengiriman notifikasi
    }

    private function clearCache()
    {
        // Implementasi pembersihan cache
        cache()->flush();
    }

    public function render()
    {
        return view('livewire.data-processor');
    }
}
```

---

## 🎨 Implementasi Frontend

### 1. Layout Utama

**File: `resources/views/layouts/app.blade.php`**

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel Progress Modal')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/progress-modal.css') }}" rel="stylesheet">
    
    @livewireStyles
</head>
<body>
    <div class="container mt-5">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    @livewireScripts
    
    <!-- Custom JS -->
    <script src="{{ asset('js/progress-modal.js') }}"></script>
</body>
</html>
```

### 2. Component Progress Modal

**File: `resources/views/components/progress-modal.blade.php`**

```html
<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" 
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-cogs me-2"></i>Memproses Data
                </h5>
            </div>
            <div class="modal-body">
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Progress:</small>
                        <small class="text-muted">
                            <span id="currentStep">0</span> dari <span id="totalSteps">10</span>
                        </small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" 
                             style="width: 0%" id="progressBar"></div>
                    </div>
                    <div class="text-center mt-2">
                        <span id="progressPercentage" class="badge bg-primary">0%</span>
                    </div>
                </div>

                <!-- Current Step Display -->
                <div class="alert alert-info d-flex align-items-center mb-4" id="currentStepAlert">
                    <div class="spinner-border spinner-border-sm text-primary me-3" role="status"></div>
                    <div>
                        <strong>Langkah <span id="stepNumber">1</span>:</strong>
                        <span id="stepDescription">Memulai proses...</span>
                    </div>
                </div>

                <!-- Step List -->
                <div class="step-list">
                    <div class="list-group list-group-flush">
                        @foreach($steps as $index => $step)
                        <div class="list-group-item progress-step" id="step-{{ $index }}">
                            <i class="fas fa-circle-notch fa-spin d-none"></i>
                            <i class="fas fa-circle text-muted"></i>
                            <i class="fas fa-check-circle text-success d-none"></i>
                            <span class="ms-2">{{ $step }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer d-none" id="modalFooter">
                <button type="button" class="btn btn-success" onclick="closeProgressModal()">
                    <i class="fas fa-check me-2"></i>Selesai
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Berhasil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <h4 class="mt-3">Data berhasil disimpan!</h4>
                <p class="text-muted">Semua proses telah selesai dengan sukses.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
```

### 3. Livewire Component View

**File: `resources/views/livewire/data-processor.blade.php`**

```html
<div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Data Processor
                    </h5>
                </div>
                <div class="card-body">
                    @if($result)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ $result }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="sampleData" class="form-label">Sample Data</label>
                        <textarea class="form-control" id="sampleData" rows="3" 
                                  placeholder="Masukkan data yang akan diproses..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" 
                                wire:click="processData" 
                                wire:loading.attr="disabled">
                            <i class="fas fa-save me-2"></i>
                            <span wire:loading.remove>Proses Data</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                        
                        <button type="button" class="btn btn-secondary" 
                                onclick="resetProcess()">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.progress-modal', ['steps' => $progressSteps])
</div>
```

---

## 🎯 Konfigurasi

### 1. CSS Kustom

**File: `public/css/progress-modal.css`**

```css
.progress-step {
    transition: all 0.3s ease;
    opacity: 0.5;
}

.progress-step.active {
    opacity: 1;
    color: #0d6efd;
}

.progress-step.completed {
    opacity: 1;
    color: #198754;
}

.progress-step i {
    width: 20px;
    text-align: center;
}

.modal-backdrop.show {
    opacity: 0.8;
}

.progress-bar {
    transition: width 0.5s ease;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.step-list {
    max-height: 300px;
    overflow-y: auto;
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
```

### 2. JavaScript Kustom

**File: `public/js/progress-modal.js`**

```javascript
let progressModal;
let successModal;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    successModal = new bootstrap.Modal(document.getElementById('successModal'));
    
    // Livewire event listeners
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('startProgress', function() {
            resetModalState();
            progressModal.show();
        });
        
        Livewire.on('updateProgress', function(data) {
            updateProgressDisplay(data[0]);
        });
        
        Livewire.on('completeProgress', function() {
            finishProcess();
        });
        
        Livewire.on('processError', function(data) {
            handleProcessError(data[0]);
        });
    });
});

function updateProgressDisplay(stepData) {
    const { current, total, description } = stepData;
    const percentage = (current / total) * 100;
    
    // Update progress bar
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressPercentage').textContent = Math.round(percentage) + '%';
    document.getElementById('currentStep').textContent = current;
    document.getElementById('stepNumber').textContent = current;
    document.getElementById('stepDescription').textContent = description;
    
    // Update step visual
    updateStepVisual(current);
}

function updateStepVisual(current) {
    // Complete previous step
    if (current > 1) {
        const prevStep = document.getElementById('step-' + (current - 1));
        if (prevStep) {
            prevStep.classList.remove('active');
            prevStep.classList.add('completed');
            prevStep.querySelector('.fa-circle-notch').classList.add('d-none');
            prevStep.querySelector('.fa-check-circle').classList.remove('d-none');
        }
    }
    
    // Activate current step
    const currentStep = document.getElementById('step-' + current);
    if (currentStep) {
        currentStep.classList.add('active');
        currentStep.querySelector('.fa-circle').classList.add('d-none');
        currentStep.querySelector('.fa-circle-notch').classList.remove('d-none');
    }
}

function finishProcess() {
    // Complete final step
    const finalStep = document.getElementById('step-10');
    if (finalStep) {
        finalStep.classList.remove('active');
        finalStep.classList.add('completed');
        finalStep.querySelector('.fa-circle-notch').classList.add('d-none');
        finalStep.querySelector('.fa-check-circle').classList.remove('d-none');
    }
    
    // Hide current step alert
    document.getElementById('currentStepAlert').classList.add('d-none');
    
    // Show footer
    document.getElementById('modalFooter').classList.remove('d-none');
    
    // Update modal title
    document.getElementById('progressModalLabel').innerHTML = 
        '<i class="fas fa-check-circle text-success me-2"></i>Proses Selesai';
    
    // Auto close and show success modal
    setTimeout(() => {
        progressModal.hide();
        successModal.show();
    }, 2000);
}

function handleProcessError(errorData) {
    progressModal.hide();
    
    // Show error alert
    const errorAlert = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> ${errorData.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.querySelector('.card-body').insertAdjacentHTML('afterbegin', errorAlert);
}

function resetModalState() {
    // Reset progress bar
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercentage').textContent = '0%';
    document.getElementById('currentStep').textContent = '0';
    
    // Reset current step display
    document.getElementById('currentStepAlert').classList.remove('d-none');
    document.getElementById('stepNumber').textContent = '1';
    document.getElementById('stepDescription').textContent = 'Memulai proses...';
    
    // Reset modal title
    document.getElementById('progressModalLabel').innerHTML = 
        '<i class="fas fa-cogs me-2"></i>Memproses Data';
    
    // Hide footer
    document.getElementById('modalFooter').classList.add('d-none');
    
    // Reset all steps
    for (let i = 1; i <= 10; i++) {
        const stepElement = document.getElementById('step-' + i);
        if (stepElement) {
            stepElement.classList.remove('active', 'completed');
            stepElement.querySelector('.fa-circle').classList.remove('d-none');
            stepElement.querySelector('.fa-circle-notch').classList.add('d-none');
            stepElement.querySelector('.fa-check-circle').classList.add('d-none');
        }
    }
}

function resetProcess() {
    resetModalState();
    // Reset component state via Livewire
    Livewire.dispatch('resetProgress');
}

function closeProgressModal() {
    progressModal.hide();
    successModal.show();
}
```

---

## 🚀 Cara Penggunaan

### 1. Buat Route

**File: `routes/web.php`**

```php
<?php

use App\Livewire\DataProcessor;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/data-processor', DataProcessor::class)->name('data-processor');
```

### 2. Buat Page

**File: `resources/views/welcome.blade.php`**

```html
@extends('layouts.app')

@section('title', 'Data Processor - Laravel Progress Modal')

@section('content')
    <livewire:data-processor />
@endsection
```

### 3. Jalankan Aplikasi

```bash
php artisan serve
```

Akses `http://localhost:8000/data-processor` untuk melihat implementasi.

---

## 🛡️ Keamanan & Best Practices

### 1. Validasi Input
```php
public function processData()
{
    $this->validate([
        'data' => 'required|array|min:1',
        'data.*' => 'required|string|max:255'
    ]);
    
    // Proses data...
}
```

### 2. Rate Limiting
```php
public function processData()
{
    $this->middleware('throttle:5,1'); // 5 requests per minute
    
    // Proses data...
}
```

### 3. Background Jobs
```php
// Untuk proses yang memakan waktu lama
public function processDataAsync()
{
    ProcessDataJob::dispatch($this->data);
    $this->startProgress();
}
```

---

## 🔧 Troubleshooting

### Issue 1: Modal tidak muncul
**Solusi:**
- Pastikan Bootstrap JS sudah dimuat
- Cek console browser untuk error JavaScript
- Verifikasi ID modal sesuai dengan JavaScript

### Issue 2: Progress tidak update
**Solusi:**
- Pastikan event listener Livewire sudah diinisialisasi
- Cek network tab untuk request Livewire
- Verifikasi method `dispatch()` dipanggil dengan benar

### Issue 3: Memory limit exceeded
**Solusi:**
```php
// Tambahkan di awal method processData()
ini_set('memory_limit', '512M');
set_time_limit(300);
```

### Issue 4: Database timeout
**Solusi:**
```php
// Konfigurasi di config/database.php
'mysql' => [
    // ...
    'options' => [
        PDO::ATTR_TIMEOUT => 300,
    ],
],
```

---

## 📈 Pengembangan Lanjutan

### 1. Real-time Updates dengan Broadcasting
```php
// Install Laravel Echo & Pusher
composer require pusher/pusher-php-server

// Broadcast event
broadcast(new ProgressUpdated($this->currentStep, $this->totalSteps));
```

### 2. Database Logging
```php
// Simpan progress ke database
ProgressLog::create([
    'user_id' => auth()->id(),
    'process_name' => 'data_processing',
    'current_step' => $this->currentStep,
    'total_steps' => $this->totalSteps,
    'status' => 'in_progress'
]);
```

### 3. Customizable Steps
```php
// Buat steps dinamis
public function setProgressSteps(array $steps)
{
    $this->progressSteps = $steps;
    $this->totalSteps = count($steps);
}
```

---

## 📝 Kesimpulan

Implementasi ini menyediakan:
- ✅ Real-time progress tracking
- ✅ Visual feedback yang menarik
- ✅ Error handling yang robust
- ✅ Customizable dan reusable
- ✅ Compatible dengan Laravel 11 & Livewire 3

Sistem ini dapat digunakan untuk berbagai keperluan seperti:
- Import/export data
- Proses batch
- File upload
- Data migration
- Report generation

---

## 🤝 Kontribusi

Untuk pengembangan lebih lanjut, silakan buat pull request atau issue di repository project ini.

**Happy Coding! 🚀**