<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XiMoPet - Sistem Monitoring Peternakan | PT. Satu Pintu Digital</title>
    <meta name="description" content="Sistem monitoring peternakan online untuk peternak dan peternak kecil.">
    <meta name="keywords" content="sistem monitoring peternakan, peternak online, peternak kecil">
    <meta name="author" content="PT. Satu Pintu Digital">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .hero-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }
    </style>
    <!-- <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script> -->
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas text-3xl text-purple-600 mr-3">🐔</i>
                        <span class="text-2xl font-bold text-gray-800">XiMoPet</span>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#beranda" class="text-gray-700 hover:text-purple-600 transition-colors">Beranda</a>
                    <a href="#fitur" class="text-gray-700 hover:text-purple-600 transition-colors">Fitur</a>
                    <a href="#tentang" class="text-gray-700 hover:text-purple-600 transition-colors">Tentang</a>
                    <a href="#kontak" class="text-gray-700 hover:text-purple-600 transition-colors">Kontak</a>
                    <button class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        Demo Gratis
                    </button>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-700" title="mobile-menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#beranda" class="block px-3 py-2 text-gray-700">Beranda</a>
                <a href="#fitur" class="block px-3 py-2 text-gray-700">Fitur</a>
                <a href="#tentang" class="block px-3 py-2 text-gray-700">Tentang</a>
                <a href="#kontak" class="block px-3 py-2 text-gray-700">Kontak</a>
                <button class="w-full text-left bg-purple-600 text-white px-3 py-2 rounded-lg mt-2">
                    Demo Gratis
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="beranda" class="gradient-bg pt-20 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <h1 class="text-5xl font-bold mb-6 leading-tight">
                        Revolusi Digital untuk <span class="text-yellow-300">Peternakan Modern</span>
                    </h1>
                    <p class="text-xl mb-8 text-purple-100">
                        XiMoPet adalah sistem monitoring peternakan terdepan yang mengintegrasikan manajemen ternak, pakan, dan analitik dalam satu platform komprehensif.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button class="bg-yellow-400 text-purple-900 px-8 py-4 rounded-lg font-semibold hover:bg-yellow-300 transition-colors">
                            <i class="fas fa-play mr-2"></i>Mulai Demo
                        </button>
                        <!-- <button class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors">
                            <i class="fas fa-download mr-2"></i>Download Brosur
                        </button> -->
                    </div>
                    <div class="mt-8 text-sm text-purple-200">
                        <i class="fas fa-building mr-2"></i>
                        Produk Unggulan PT. Satu Pintu Digital
                    </div>
                </div>
                <div class="hero-animation">
                    <div class="bg-white rounded-2xl shadow-2xl p-8">
                        <div class="text-center mb-6">
                            <i class="fas fa-chart-line text-6xl text-purple-600 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-800">Dashboard Real-time</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="text-gray-700">Ternak Aktif</span>
                                <span class="font-bold text-green-600">2,847</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="text-gray-700">FCR Rata-rata</span>
                                <span class="font-bold text-blue-600">1.65</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                                <span class="text-gray-700">Mortalitas</span>
                                <span class="font-bold text-yellow-600">2.1%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <!-- <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">500+</div>
                    <div class="text-gray-600">Peternakan Terdaftar</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">1M+</div>
                    <div class="text-gray-600">Ternak Dimonitor</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">99.9%</div>
                    <div class="text-gray-600">Uptime System</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">24/7</div>
                    <div class="text-gray-600">Support</div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Features Section -->
    <section id="fitur" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Fitur Lengkap & Terintegrasi</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    XiMoPet menyediakan semua tools yang Anda butuhkan untuk mengelola peternakan modern dengan efisien dan akurat.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Livestock Management -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas text-5xl text-green-500 mb-4">🐔</i>
                        <h3 class="text-2xl font-bold text-gray-800">Manajemen Ternak</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Registrasi & Tracking Ternak</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Manajemen Batch</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Sistem FIFO</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Tracking Berat & Performa</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Manajemen Kesehatan</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Lifecycle Management</li>
                    </ul>
                </div>

                <!-- Feed Management -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas fa-seedling text-5xl text-yellow-500 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Manajemen Pakan</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Pembelian & Stok Pakan</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Recording Penggunaan</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Mutasi Pakan</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Alert Stok</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Analisis Konsumsi</li>
                    </ul>
                </div>

                <!-- Supply Management -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas fa-pills text-5xl text-blue-500 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Manajemen Supply</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>OVK/Medical Supply</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Tracking Penggunaan</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Manajemen Stok</li>
                    </ul>
                </div>

                <!-- Analytics -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas fa-chart-bar text-5xl text-purple-500 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Recording & Analytics</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Daily Recording System</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Metrics (FCR, IP, ADG)</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Real-time Analytics</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Data Integrity Check</li>
                    </ul>
                </div>

                <!-- Master Data -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas fa-database text-5xl text-red-500 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Master Data</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Farm & Coop Management</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Worker Management</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Supplier Management</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Customer Management</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Unit & Category</li>
                    </ul>
                </div>

                <!-- Advanced Features -->
                <div class="feature-card bg-white rounded-xl shadow-lg p-8 hover:shadow-xl">
                    <div class="text-center mb-6">
                        <i class="fas fa-cogs text-5xl text-indigo-500 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">Fitur Advanced</h3>
                    </div>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Alert System</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Role-based Access</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>Export/Import Data</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>PDF Reports</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Mengapa Memilih XiMoPet?</h2>
                <p class="text-xl text-gray-600">Keunggulan yang membuat XiMoPet menjadi pilihan terbaik</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="text-center p-8">
                    <div class="bg-purple-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-rocket text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Efisiensi Maksimal</h3>
                    <p class="text-gray-600">Otomatisasi proses monitoring mengurangi waktu kerja hingga 70%</p>
                </div>

                <div class="text-center p-8">
                    <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Data Akurat</h3>
                    <p class="text-gray-600">Sistem validasi berlapis memastikan akurasi data 99.9%</p>
                </div>

                <div class="text-center p-8">
                    <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-mobile-alt text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Mobile Friendly</h3>
                    <p class="text-gray-600">Akses dari mana saja, kapan saja melalui smartphone atau tablet</p>
                </div>

                <div class="text-center p-8">
                    <div class="bg-yellow-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-3xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Tim Support 24/7</h3>
                    <p class="text-gray-600">Dukungan teknis profesional siap membantu Anda setiap saat</p>
                </div>

                <div class="text-center p-8">
                    <div class="bg-red-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-sync-alt text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Update Berkala</h3>
                    <p class="text-gray-600">Fitur baru dan perbaikan sistem secara berkala tanpa biaya tambahan</p>
                </div>

                <div class="text-center p-8">
                    <div class="bg-indigo-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-lock text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Keamanan Tinggi</h3>
                    <p class="text-gray-600">Enkripsi end-to-end dan backup otomatis melindungi data Anda</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="tentang" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">Tentang PT. Satu Pintu Digital</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        PT. Satu Pintu Digital adalah perusahaan teknologi yang fokus pada pengembangan solusi digital di berbagai sektor.
                    </p>
                    <p class="text-lg text-gray-600 mb-8">
                        Dengan pengalaman lebih dari 10 tahun, kami siap membantu client meningkatkan produktivitas dan efisiensi melalui teknologi inovatif.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-award text-purple-600 mr-4 text-xl"></i>
                            <span class="text-gray-700">Private Datacenter Multiple Upstream</span>

                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <div class="text-center mb-8">
                        <i class="fas fa-building text-6xl text-purple-600 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800">PT. Satu Pintu Digital</h3>
                        <p class="text-gray-600">Transformasi Digital Indonesia</p>
                    </div>
                    <div class="space-y-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Didirikan</span>
                            <span class="font-semibold">2013</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Kantor Pusat</span>
                            <span class="font-semibold">Jakarta</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tim Ahli</span>
                            <span class="font-semibold">50+ Profesional</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Klien Aktif</span>
                            <span class="font-semibold">500+ Peternakan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-bg py-20">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-white mb-6">
                Siap Meningkatkan Produktivitas Peternakan Anda?
            </h2>
            <p class="text-xl text-purple-100 mb-8">
                Bergabunglah dengan 500+ peternakan yang telah merasakan manfaat XiMoPet. Dapatkan demo gratis sekarang!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="bg-yellow-400 text-purple-900 px-8 py-4 rounded-lg font-semibold hover:bg-yellow-300 transition-colors text-lg">
                    <i class="fas fa-calendar-alt mr-2"></i>Jadwalkan Demo
                </button>
                <button class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors text-lg">
                    <i class="fas fa-phone mr-2"></i>Hubungi Sales
                </button>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="kontak" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Hubungi Kami</h2>
                <p class="text-xl text-gray-600">Tim ahli kami siap membantu Anda</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-8 bg-gray-50 rounded-xl">
                    <i class="fas fa-phone text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Telepon</h3>
                    <p class="text-gray-600">+62 822 4354 3715</p>
                </div>

                <div class="text-center p-8 bg-gray-50 rounded-xl">
                    <i class="fas fa-envelope text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Email</h3>
                    <p class="text-gray-600">hello@satupintudigital.id</p>
                </div>

                <div class="text-center p-8 bg-gray-50 rounded-xl">
                    <i class="fas fa-map-marker-alt text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Alamat</h3>
                    <p class="text-gray-600">Komplek Green View Sunggal Block D38</p>
                    <p class="text-gray-600">Deliserdang, Sumatera Utara 20351</p>
                </div>
            </div>

            <div class="mt-16 max-w-2xl mx-auto">
                <!-- Form Status Messages -->
                <div id="form-messages" class="mb-6 hidden">
                    <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 hidden">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>Terima kasih! Pesan Anda telah terkirim. Tim kami akan menghubungi Anda segera.</span>
                    </div>
                    <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 hidden">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="error-text">Terjadi kesalahan. Silakan coba lagi.</span>
                    </div>
                    <div id="rate-limit-message" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-4 hidden">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Mohon tunggu <span id="countdown">60</span> detik sebelum mengirim pesan lagi.</span>
                    </div>
                </div>

                <form id="contactUs" name="contactForm" class="bg-gray-50 p-8 rounded-xl" method="POST" action="#" novalidate>
                    <!-- CSRF Token (simulated) -->
                    <input type="hidden" name="csrf_token" id="csrf_token" value="">
                    
                    <!-- Honeypot field (hidden from users, visible to bots) -->
                    <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <input type="text" name="website" id="website" placeholder="Leave this field empty" tabindex="-1" autocomplete="off">
                        <input type="email" name="email_confirm" id="email_confirm" placeholder="Confirm email" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div class="relative">
                            <input type="text" 
                                   name="full_name" 
                                   id="full_name" 
                                   placeholder="Nama Lengkap *" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors"
                                   required
                                   minlength="2"
                                   maxlength="100"
                                   pattern="^[a-zA-Z\s\u00C0-\u017F\u0100-\u024F\u1E00-\u1EFF]+$"
                                   title="Nama hanya boleh mengandung huruf dan spasi">
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="full_name_error"></div>
                        </div>
                        <div class="relative">
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   placeholder="Email *" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors"
                                   required
                                   maxlength="255"
                                   pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                   title="Masukkan alamat email yang valid">
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="email_error"></div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div class="relative">
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   placeholder="Nomor Telepon *" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors"
                                   required
                                   minlength="10"
                                   maxlength="15"
                                   pattern="^[\+]?[0-9\-\(\)\s]+$"
                                   title="Masukkan nomor telepon yang valid (10-15 digit)">
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="phone_error"></div>
                        </div>
                        <div class="relative">
                            <input type="text" 
                                   name="company" 
                                   id="company" 
                                   placeholder="Nama Perusahaan" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors"
                                   maxlength="100"
                                   pattern="^[a-zA-Z0-9\s\.\,\-\&\(\)]+$"
                                   title="Nama perusahaan hanya boleh mengandung huruf, angka, dan karakter khusus tertentu">
                            <div class="error-message text-red-500 text-sm mt-1 hidden" id="company_error"></div>
                        </div>
                    </div>
                    <div class="relative mb-6">
                        <textarea name="message" 
                                  id="message" 
                                  placeholder="Pesan Anda *" 
                                  rows="4" 
                                  class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors resize-vertical"
                                  required
                                  minlength="10"
                                  maxlength="1000"
                                  title="Pesan minimal 10 karakter dan maksimal 1000 karakter"></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <div class="error-message text-red-500 text-sm hidden" id="message_error"></div>
                            <div class="text-gray-500 text-sm">
                                <span id="message_count">0</span>/1000 karakter
                            </div>
                        </div>
                    </div>

                    <!-- Simple Math Captcha -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Verifikasi: Berapa hasil dari <span id="captcha_question"></span>? *
                        </label>
                        <input type="number" 
                               name="captcha_answer" 
                               id="captcha_answer" 
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:outline-none transition-colors"
                               required
                               min="0"
                               max="100"
                               title="Jawab pertanyaan matematika sederhana">
                        <div class="error-message text-red-500 text-sm mt-1 hidden" id="captcha_error"></div>
                    </div>

                    <!-- Terms and Privacy -->
                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" 
                                   name="terms_accepted" 
                                   id="terms_accepted" 
                                   class="mt-1 mr-3 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                                   required>
                            <span class="text-sm text-gray-600">
                                Saya setuju dengan <a href="#" class="text-purple-600 hover:underline">Syarat & Ketentuan</a> 
                                dan <a href="#" class="text-purple-600 hover:underline">Kebijakan Privasi</a> *
                            </span>
                        </label>
                        <div class="error-message text-red-500 text-sm mt-1 hidden" id="terms_error"></div>
                    </div>

                    <button type="submit" 
                            id="submit_btn"
                            class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <span id="submit_text">
                            <i class="fas fa-paper-plane mr-2"></i>Kirim Pesan
                        </span>
                        <span id="submit_loading" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...
                        </span>
                    </button>

                    <p class="text-xs text-gray-500 mt-4 text-center">
                        * Wajib diisi. Data Anda akan dijaga kerahasiaannya sesuai kebijakan privasi kami.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas text-3xl text-purple-400 mr-3">🐔</i>
                        <span class="text-2xl font-bold">XiMoPet</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Sistem monitoring peternakan terdepan untuk peternakan modern Indonesia.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-purple-400"><i class="fab fa-facebook text-xl" title="Facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-purple-400"><i class="fab fa-twitter text-xl" title="Twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-purple-400"><i class="fab fa-linkedin text-xl" title="LinkedIn"></i></a>
                        <a href="#" class="text-gray-400 hover:text-purple-400"><i class="fab fa-instagram text-xl" title="Instagram"></i></a>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Produk</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Manajemen Ternak</a></li>
                        <li><a href="#" class="hover:text-white">Manajemen Pakan</a></li>
                        <li><a href="#" class="hover:text-white">Analytics</a></li>
                        <!-- <li><a href="#" class="hover:text-white">Mobile App</a></li> -->
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Perusahaan</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-white">Karir</a></li>
                        <li><a href="#" class="hover:text-white">Blog</a></li>
                        <!-- <li><a href="#" class="hover:text-white">Press Release</a></li> -->
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-white">Documentation</a></li>
                        <li><a href="#" class="hover:text-white">Training</a></li>
                        <li><a href="#" class="hover:text-white">Contact Support</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 PT. Satu Pintu Digital. All rights reserved. | 
                    <button onclick="openEmbedModal('privacy')" class="text-purple-400 hover:text-purple-300 underline cursor-pointer">Privacy Policy</button> | 
                    <button onclick="openEmbedModal('terms')" class="text-purple-400 hover:text-purple-300 underline cursor-pointer">Terms of Service</button>
                </p>
            </div>
        </div>
    </footer>

    <!-- Embed Modal -->
    <div id="embedModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 id="modalTitle" class="text-xl font-semibold text-gray-800"></h3>
                    <button onclick="closeEmbedModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div id="modalContent" class="prose max-w-none">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center space-x-4">
                        <button onclick="openFullPage()" id="fullPageBtn" class="text-purple-600 hover:text-purple-800 font-medium">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Buka Halaman Penuh
                        </button>
                    </div>
                    <button onclick="closeEmbedModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Form validation and security features
        class FormValidator {
            constructor() {
                this.form = document.getElementById('contactUs');
                this.submitBtn = document.getElementById('submit_btn');
                this.submitText = document.getElementById('submit_text');
                this.submitLoading = document.getElementById('submit_loading');
                this.lastSubmissionTime = 0;
                this.rateLimitDuration = 60000; // 60 seconds
                this.captchaAnswer = 0;
                this.isSubmitting = false;
                
                this.init();
            }

            init() {
                this.generateCSRFToken();
                this.generateCaptcha();
                this.setupEventListeners();
                this.setupRealTimeValidation();
            }

            generateCSRFToken() {
                const token = Math.random().toString(36).substring(2) + Date.now().toString(36);
                document.getElementById('csrf_token').value = token;
            }

            generateCaptcha() {
                const num1 = Math.floor(Math.random() * 20) + 1;
                const num2 = Math.floor(Math.random() * 20) + 1;
                const operators = ['+', '-'];
                const operator = operators[Math.floor(Math.random() * operators.length)];
                
                let question, answer;
                if (operator === '+') {
                    question = `${num1} + ${num2}`;
                    answer = num1 + num2;
                } else {
                    // Ensure positive result for subtraction
                    const larger = Math.max(num1, num2);
                    const smaller = Math.min(num1, num2);
                    question = `${larger} - ${smaller}`;
                    answer = larger - smaller;
                }
                
                document.getElementById('captcha_question').textContent = question;
                this.captchaAnswer = answer;
            }

            setupEventListeners() {
                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                
                // Message character counter
                const messageField = document.getElementById('message');
                const messageCount = document.getElementById('message_count');
                messageField.addEventListener('input', () => {
                    messageCount.textContent = messageField.value.length;
                });

                // Real-time validation on blur
                const fields = ['full_name', 'email', 'phone', 'company', 'message', 'captcha_answer'];
                fields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('blur', () => this.validateField(fieldId));
                        field.addEventListener('input', () => this.clearFieldError(fieldId));
                    }
                });

                // Terms checkbox validation
                document.getElementById('terms_accepted').addEventListener('change', () => {
                    this.validateField('terms_accepted');
                });
            }

            setupRealTimeValidation() {
                // Add input event listeners for immediate feedback
                const inputs = this.form.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        if (input.classList.contains('border-red-500')) {
                            input.classList.remove('border-red-500');
                            input.classList.add('border-gray-300');
                        }
                    });
                });
            }

            validateField(fieldId) {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(`${fieldId}_error`);
                let isValid = true;
                let errorMessage = '';

                switch (fieldId) {
                    case 'full_name':
                        if (!field.value.trim()) {
                            errorMessage = 'Nama lengkap wajib diisi';
                            isValid = false;
                        } else if (field.value.trim().length < 2) {
                            errorMessage = 'Nama minimal 2 karakter';
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\u00C0-\u017F\u0100-\u024F\u1E00-\u1EFF]+$/.test(field.value.trim())) {
                            errorMessage = 'Nama hanya boleh mengandung huruf dan spasi';
                            isValid = false;
                        }
                        break;

                    case 'email':
                        if (!field.value.trim()) {
                            errorMessage = 'Email wajib diisi';
                            isValid = false;
                        } else if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(field.value.trim())) {
                            errorMessage = 'Format email tidak valid';
                            isValid = false;
                        }
                        break;

                    case 'phone':
                        if (!field.value.trim()) {
                            errorMessage = 'Nomor telepon wajib diisi';
                            isValid = false;
                        } else if (!/^[\+]?[0-9\-\(\)\s]+$/.test(field.value.trim())) {
                            errorMessage = 'Format nomor telepon tidak valid';
                            isValid = false;
                        } else if (field.value.replace(/\D/g, '').length < 10) {
                            errorMessage = 'Nomor telepon minimal 10 digit';
                            isValid = false;
                        }
                        break;

                    case 'company':
                        if (field.value.trim() && !/^[a-zA-Z0-9\s\.\,\-\&\(\)]+$/.test(field.value.trim())) {
                            errorMessage = 'Nama perusahaan mengandung karakter tidak valid';
                            isValid = false;
                        }
                        break;

                    case 'message':
                        if (!field.value.trim()) {
                            errorMessage = 'Pesan wajib diisi';
                            isValid = false;
                        } else if (field.value.trim().length < 10) {
                            errorMessage = 'Pesan minimal 10 karakter';
                            isValid = false;
                        } else if (field.value.length > 1000) {
                            errorMessage = 'Pesan maksimal 1000 karakter';
                            isValid = false;
                        }
                        break;

                    case 'captcha_answer':
                        if (!field.value) {
                            errorMessage = 'Jawaban verifikasi wajib diisi';
                            isValid = false;
                        } else if (parseInt(field.value) !== this.captchaAnswer) {
                            errorMessage = 'Jawaban verifikasi salah';
                            isValid = false;
                        }
                        break;

                    case 'terms_accepted':
                        if (!field.checked) {
                            errorMessage = 'Anda harus menyetujui syarat dan ketentuan';
                            isValid = false;
                        }
                        break;
                }

                this.showFieldError(fieldId, errorMessage, !isValid);
                return isValid;
            }

            showFieldError(fieldId, message, hasError) {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(`${fieldId}_error`);

                if (hasError) {
                    field.classList.add('border-red-500');
                    field.classList.remove('border-gray-300');
                    if (errorElement) {
                        errorElement.textContent = message;
                        errorElement.classList.remove('hidden');
                    }
                } else {
                    field.classList.remove('border-red-500');
                    field.classList.add('border-gray-300');
                    if (errorElement) {
                        errorElement.classList.add('hidden');
                    }
                }
            }

            clearFieldError(fieldId) {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(`${fieldId}_error`);
                
                if (errorElement && !errorElement.classList.contains('hidden')) {
                    field.classList.remove('border-red-500');
                    field.classList.add('border-gray-300');
                    errorElement.classList.add('hidden');
                }
            }

            validateForm() {
                const fields = ['full_name', 'email', 'phone', 'message', 'captcha_answer', 'terms_accepted'];
                let isValid = true;

                fields.forEach(fieldId => {
                    if (!this.validateField(fieldId)) {
                        isValid = false;
                    }
                });

                // Validate company field if filled
                const companyField = document.getElementById('company');
                if (companyField.value.trim()) {
                    if (!this.validateField('company')) {
                        isValid = false;
                    }
                }

                return isValid;
            }

            checkHoneypot() {
                const websiteField = document.getElementById('website');
                const emailConfirmField = document.getElementById('email_confirm');
                
                // If honeypot fields are filled, it's likely a bot
                return websiteField.value === '' && emailConfirmField.value === '';
            }

            checkRateLimit() {
                const now = Date.now();
                const timeSinceLastSubmission = now - this.lastSubmissionTime;
                
                if (timeSinceLastSubmission < this.rateLimitDuration) {
                    const remainingTime = Math.ceil((this.rateLimitDuration - timeSinceLastSubmission) / 1000);
                    this.showRateLimitMessage(remainingTime);
                    return false;
                }
                
                return true;
            }

            showRateLimitMessage(remainingTime) {
                const rateLimitMessage = document.getElementById('rate-limit-message');
                const countdown = document.getElementById('countdown');
                const formMessages = document.getElementById('form-messages');
                
                countdown.textContent = remainingTime;
                this.hideAllMessages();
                rateLimitMessage.classList.remove('hidden');
                formMessages.classList.remove('hidden');
                
                // Update countdown
                const countdownInterval = setInterval(() => {
                    remainingTime--;
                    countdown.textContent = remainingTime;
                    
                    if (remainingTime <= 0) {
                        clearInterval(countdownInterval);
                        rateLimitMessage.classList.add('hidden');
                        formMessages.classList.add('hidden');
                    }
                }, 1000);
            }

            showSuccessMessage() {
                const successMessage = document.getElementById('success-message');
                const formMessages = document.getElementById('form-messages');
                
                this.hideAllMessages();
                successMessage.classList.remove('hidden');
                formMessages.classList.remove('hidden');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                    formMessages.classList.add('hidden');
                }, 5000);
            }

            showErrorMessage(message = 'Terjadi kesalahan. Silakan coba lagi.') {
                const errorMessage = document.getElementById('error-message');
                const errorText = document.getElementById('error-text');
                const formMessages = document.getElementById('form-messages');
                
                this.hideAllMessages();
                errorText.textContent = message;
                errorMessage.classList.remove('hidden');
                formMessages.classList.remove('hidden');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    errorMessage.classList.add('hidden');
                    formMessages.classList.add('hidden');
                }, 5000);
            }

            hideAllMessages() {
                const messages = ['success-message', 'error-message', 'rate-limit-message'];
                messages.forEach(messageId => {
                    document.getElementById(messageId).classList.add('hidden');
                });
            }

            setSubmitButtonState(isLoading) {
                if (isLoading) {
                    this.submitBtn.disabled = true;
                    this.submitText.classList.add('hidden');
                    this.submitLoading.classList.remove('hidden');
                } else {
                    this.submitBtn.disabled = false;
                    this.submitText.classList.remove('hidden');
                    this.submitLoading.classList.add('hidden');
                }
            }

            sanitizeInput(input) {
                return input.trim()
                    .replace(/[<>]/g, '') // Remove potential HTML tags
                    .replace(/javascript:/gi, '') // Remove javascript: protocol
                    .replace(/on\w+=/gi, ''); // Remove event handlers
            }

            async handleSubmit(e) {
                e.preventDefault();
                
                if (this.isSubmitting) return;
                
                // Hide any existing messages
                this.hideAllMessages();
                
                // Check rate limiting
                if (!this.checkRateLimit()) {
                    return;
                }
                
                // Check honeypot
                if (!this.checkHoneypot()) {
                    this.showErrorMessage('Spam terdeteksi. Formulir tidak dapat diproses.');
                    return;
                }
                
                // Validate form
                if (!this.validateForm()) {
                    this.showErrorMessage('Mohon perbaiki kesalahan pada formulir.');
                    return;
                }
                
                this.isSubmitting = true;
                this.setSubmitButtonState(true);
                
                try {
                    // Simulate form submission delay
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    
                    // Get and sanitize form data
                    const formData = new FormData(this.form);
                    const sanitizedData = {};
                    
                    for (let [key, value] of formData.entries()) {
                        if (key !== 'website' && key !== 'email_confirm') { // Exclude honeypot fields
                            sanitizedData[key] = this.sanitizeInput(value);
                        }
                    }
                    
                    // Here you would normally send the data to your server
                    console.log('Form data:', sanitizedData);
                    
                    // Update last submission time
                    this.lastSubmissionTime = Date.now();
                    
                    // Show success message
                    this.showSuccessMessage();
                    
                    // Reset form and regenerate security features
                    this.form.reset();
                    this.generateCSRFToken();
                    this.generateCaptcha();
                    document.getElementById('message_count').textContent = '0';
                    
                } catch (error) {
                    console.error('Form submission error:', error);
                    this.showErrorMessage('Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.');
                } finally {
                    this.isSubmitting = false;
                    this.setSubmitButtonState(false);
                }
            }
        }

        // Initialize form validator when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new FormValidator();
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('nav');
            if (window.scrollY > 100) {
                navbar.classList.add('shadow-xl');
            } else {
                navbar.classList.remove('shadow-xl');
            }
        });

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.text-4xl');
                    counters.forEach(counter => {
                        const target = counter.textContent;
                        const numericValue = parseInt(target.replace(/\D/g, ''));
                        if (numericValue) {
                            animateCounter(counter, numericValue, target);
                        }
                    });
                }
            });
        }, observerOptions);

        // Observe stats section
        const statsSection = document.querySelector('.grid.grid-cols-2.md\\:grid-cols-4');
        if (statsSection) {
            observer.observe(statsSection);
        }

        function animateCounter(element, target, originalText) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = originalText;
                    clearInterval(timer);
                } else {
                    const suffix = originalText.replace(/[\d,]/g, '');
                    element.textContent = Math.floor(current).toLocaleString() + suffix;
                }
            }, 30);
        }

        // Embed Modal Functions
        let currentEmbedType = '';

        function openEmbedModal(type) {
            currentEmbedType = type;
            const modal = document.getElementById('embedModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');
            
            // Set title
            if (type === 'privacy') {
                modalTitle.textContent = 'Kebijakan Privasi';
            } else if (type === 'terms') {
                modalTitle.textContent = 'Syarat & Ketentuan';
            }
            
            // Show loading
            modalContent.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    <span class="ml-3 text-gray-600">Memuat konten...</span>
                </div>
            `;
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Load content
            loadEmbedContent(type);
        }

        function closeEmbedModal() {
            const modal = document.getElementById('embedModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentEmbedType = '';
        }

        function openFullPage() {
            if (currentEmbedType === 'privacy') {
                window.open('privacy.php', '_blank');
            } else if (currentEmbedType === 'terms') {
                window.open('terms.php', '_blank');
            }
        }

        async function loadEmbedContent(type) {
            const modalContent = document.getElementById('modalContent');
            
            try {
                const filename = type === 'privacy' ? 'privacy.php' : 'terms.php';
                const response = await fetch(filename);
                
                if (!response.ok) {
                    throw new Error('Failed to load content');
                }
                
                const html = await response.text();
                
                // Extract content from the loaded HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Get the main content (everything inside the main tag)
                const mainContent = doc.querySelector('main');
                
                if (mainContent) {
                    // Remove the header and footer from the content
                    const contentDiv = mainContent.querySelector('.bg-white.rounded-lg.shadow-lg');
                    if (contentDiv) {
                        modalContent.innerHTML = contentDiv.innerHTML;
                        
                        // Re-enable smooth scrolling for internal links
                        modalContent.querySelectorAll('a[href^="#"]').forEach(anchor => {
                            anchor.addEventListener('click', function (e) {
                                e.preventDefault();
                                const target = modalContent.querySelector(this.getAttribute('href'));
                                if (target) {
                                    target.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                }
                            });
                        });
                    } else {
                        modalContent.innerHTML = mainContent.innerHTML;
                    }
                } else {
                    // Fallback: show the entire body content
                    const bodyContent = doc.querySelector('body');
                    modalContent.innerHTML = bodyContent.innerHTML;
                }
                
            } catch (error) {
                console.error('Error loading content:', error);
                modalContent.innerHTML = `
                    <div class="text-center py-12">
                        <div class="text-red-600 mb-4">
                            <i class="fas fa-exclamation-triangle text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Gagal Memuat Konten</h3>
                        <p class="text-gray-600 mb-4">Terjadi kesalahan saat memuat konten. Silakan coba lagi atau buka halaman penuh.</p>
                        <button onclick="openFullPage()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            Buka Halaman Penuh
                        </button>
                    </div>
                `;
            }
        }

        // Close modal when clicking outside
        document.getElementById('embedModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmbedModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('embedModal').classList.contains('hidden')) {
                closeEmbedModal();
            }
        });
    </script>
    <script>
        (function() {
            function c() {
                var b = a.contentDocument || a.contentWindow.document;
                if (b) {
                    var d = b.createElement('script');
                    d.innerHTML = "window.__CF$cv$params={r:'982ecd1c41eacdee',t:'MTc1ODUxMjYzOS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";
                    b.getElementsByTagName('head')[0].appendChild(d)
                }
            }
            if (document.body) {
                var a = document.createElement('iframe');
                a.height = 1;
                a.width = 1;
                a.style.position = 'absolute';
                a.style.top = 0;
                a.style.left = 0;
                a.style.border = 'none';
                a.style.visibility = 'hidden';
                document.body.appendChild(a);
                if ('loading' !== document.readyState) c();
                else if (window.addEventListener) document.addEventListener('DOMContentLoaded', c);
                else {
                    var e = document.onreadystatechange || function() {};
                    document.onreadystatechange = function(b) {
                        e(b);
                        'loading' !== document.readyState && (document.onreadystatechange = e, c())
                    }
                }
            }
        })();
    </script>
</body>

</html>