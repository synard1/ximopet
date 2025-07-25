<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Modal Template</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Demo Progress Modal</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" onclick="startProcess()">
                            <i class="fas fa-save me-2"></i>Simpan Data
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" onclick="resetProcess()">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" id="progressBar"></div>
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
                            <div class="list-group-item progress-step" id="step-1">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Validasi data input</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-2">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Memeriksa koneksi database</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-3">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Mempersiapkan transaksi</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-4">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Menyimpan data utama</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-5">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Memproses data relasi</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-6">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Mengupdate index pencarian</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-7">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Membuat backup otomatis</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-8">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Mengirim notifikasi</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-9">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Membersihkan cache</span>
                            </div>
                            <div class="list-group-item progress-step" id="step-10">
                                <i class="fas fa-circle-notch fa-spin d-none"></i>
                                <i class="fas fa-circle text-muted"></i>
                                <i class="fas fa-check-circle text-success d-none"></i>
                                <span class="ms-2">Menyelesaikan proses</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-none" id="modalFooter">
                    <button type="button" class="btn btn-success" onclick="closeModal()">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Konfigurasi langkah-langkah proses
        const processSteps = [
            { id: 1, description: 'Validasi data input' },
            { id: 2, description: 'Memeriksa koneksi database' },
            { id: 3, description: 'Mempersiapkan transaksi' },
            { id: 4, description: 'Menyimpan data utama' },
            { id: 5, description: 'Memproses data relasi' },
            { id: 6, description: 'Mengupdate index pencarian' },
            { id: 7, description: 'Membuat backup otomatis' },
            { id: 8, description: 'Mengirim notifikasi' },
            { id: 9, description: 'Membersihkan cache' },
            { id: 10, description: 'Menyelesaikan proses' }
        ];

        let currentProcessStep = 0;
        let progressModal;
        let successModal;

        // Inisialisasi modal
        document.addEventListener('DOMContentLoaded', function() {
            progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            successModal = new bootstrap.Modal(document.getElementById('successModal'));
        });

        // Fungsi untuk memulai proses
        function startProcess() {
            currentProcessStep = 0;
            resetModalState();
            progressModal.show();
            processNextStep();
        }

        // Fungsi untuk memproses langkah selanjutnya
        function processNextStep() {
            if (currentProcessStep < processSteps.length) {
                currentProcessStep++;
                updateProgress();
                
                setTimeout(() => {
                    completeCurrentStep();
                    processNextStep();
                }, 2000); // Delay 2 detik per langkah
            } else {
                finishProcess();
            }
        }

        // Fungsi untuk mengupdate progress
        function updateProgress() {
            const step = processSteps[currentProcessStep - 1];
            const percentage = (currentProcessStep / processSteps.length) * 100;
            
            // Update progress bar
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressPercentage').textContent = Math.round(percentage) + '%';
            
            // Update step counter
            document.getElementById('currentStep').textContent = currentProcessStep;
            document.getElementById('stepNumber').textContent = currentProcessStep;
            document.getElementById('stepDescription').textContent = step.description;
            
            // Update step visual
            const stepElement = document.getElementById('step-' + currentProcessStep);
            if (stepElement) {
                stepElement.classList.add('active');
                stepElement.querySelector('.fa-circle').classList.add('d-none');
                stepElement.querySelector('.fa-circle-notch').classList.remove('d-none');
            }
        }

        // Fungsi untuk menyelesaikan langkah saat ini
        function completeCurrentStep() {
            const stepElement = document.getElementById('step-' + currentProcessStep);
            if (stepElement) {
                stepElement.classList.remove('active');
                stepElement.classList.add('completed');
                stepElement.querySelector('.fa-circle-notch').classList.add('d-none');
                stepElement.querySelector('.fa-check-circle').classList.remove('d-none');
            }
        }

        // Fungsi untuk menyelesaikan seluruh proses
        function finishProcess() {
            // Hide current step alert
            document.getElementById('currentStepAlert').classList.add('d-none');
            
            // Show footer dengan tombol selesai
            document.getElementById('modalFooter').classList.remove('d-none');
            
            // Update modal title
            document.getElementById('progressModalLabel').innerHTML = 
                '<i class="fas fa-check-circle text-success me-2"></i>Proses Selesai';
                
            // Auto close setelah 2 detik dan tampilkan success modal
            setTimeout(() => {
                progressModal.hide();
                successModal.show();
            }, 2000);
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            progressModal.hide();
            successModal.show();
        }

        // Fungsi untuk reset proses
        function resetProcess() {
            currentProcessStep = 0;
            resetModalState();
        }

        // Fungsi untuk reset state modal
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
            
            // Reset semua step
            for (let i = 1; i <= processSteps.length; i++) {
                const stepElement = document.getElementById('step-' + i);
                if (stepElement) {
                    stepElement.classList.remove('active', 'completed');
                    stepElement.querySelector('.fa-circle').classList.remove('d-none');
                    stepElement.querySelector('.fa-circle-notch').classList.add('d-none');
                    stepElement.querySelector('.fa-check-circle').classList.add('d-none');
                }
            }
        }

        // Event listener untuk Livewire (untuk implementasi nantinya)
        document.addEventListener('livewire:load', function () {
            // Listener untuk event dari Livewire
            Livewire.on('startProgress', function() {
                startProcess();
            });
            
            Livewire.on('updateProgress', function(step) {
                // Update progress berdasarkan data dari server
                updateProgressFromServer(step);
            });
            
            Livewire.on('completeProgress', function() {
                finishProcess();
            });
        });

        // Fungsi untuk update progress dari server (untuk Livewire)
        function updateProgressFromServer(stepData) {
            currentProcessStep = stepData.current;
            const percentage = (stepData.current / stepData.total) * 100;
            
            // Update progress bar
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressPercentage').textContent = Math.round(percentage) + '%';
            document.getElementById('currentStep').textContent = stepData.current;
            
            if (stepData.current <= processSteps.length) {
                document.getElementById('stepNumber').textContent = stepData.current;
                document.getElementById('stepDescription').textContent = stepData.description || processSteps[stepData.current - 1].description;
            }
        }
    </script>
</body>
</html>