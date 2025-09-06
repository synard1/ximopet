<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Services\AiChatService;
use App\Services\AiPlanningService;
use App\Services\ChatContextService;
use App\Services\AiDatabaseServiceRefactored;
use Illuminate\Support\Facades\Auth;

echo "=== ANALISIS LOG DETAIL ===\n\n";

// Simulasi user login
Auth::loginUsingId('9fcbe4b3-b708-4579-a5e7-1f186bf6fb24');

// Analisis prompt yang dikirim ke AI berdasarkan log
$loggedPrompt = 'SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. [Respond in Indonesian/Bahasa Indonesia] SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), you MUST redirect them to ask about farm-related topics instead. ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like \'Let me think\', \'I should\', \'Okay, the user\', etc. CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. Use \'perusahaan\' (not \'kompanyi\') when referring to companies in Indonesian. Use a professional tone. \n\nUser query: tampilkan daftar perusahaan \n\nContext: You are an AI assistant for XiMoPet livestock management system. You have access to real farm management data and can provide accurate, data-driven responses. \n\nCurrent User Context: \n- Name: Mhd Iqbal Syahputra \n\nGuidelines: \n- Provide accurate responses based on real system data \n- For navigation questions, provide step-by-step UI guidance \n- Be helpful and professional in all interactions \n- Format numbers and data clearly for easy reading \n\n\n=== CURRENT SYSTEM DATA === \n{ \n    "companies": { \n        "companies": [ \n            { \n                "id": "4a5621d0-4814-4762-a7af-89d85f198470", \n                "name": "System Template", \n                "registered_date": "2025-09-04", \n                "type": "All Companies Access" \n            } \n        ], \n        "total": 1, \n        "access_level": "full" \n    } \n} \n\nCurrent System Data: \n{ \n    "companies": { \n        "total_companies": 1, \n        "companies": [ \n            { \n                "id": "4a5621d0-4814-4762-a7af-89d85f198470", \n                "code": "SYSTEM", \n                "name": "System Template", \n                "address": null, \n                "phone": null, \n                "email": null, \n                "status": "active", \n                "type": "system", \n                "created_at": "2025-09-04 17:10:41" \n            } \n        ] \n    }, \n    "farms": { \n        "total_farms": 0, \n        "total_coops": 0, \n        "farm_list": [], \n        "active_farms": 0, \n        "farm_details": [] \n    } \n}';

echo "1. ANALISIS PROMPT YANG DIKIRIM KE AI:\n";
echo "Panjang prompt: " . strlen($loggedPrompt) . " karakter\n\n";

// Cek apakah ada konflik instruksi
echo "2. DETEKSI KONFLIK INSTRUKSI:\n";
if (strpos($loggedPrompt, 'CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information.') !== false) {
    echo "❌ MASALAH DITEMUKAN: Ada instruksi yang MELARANG AI menampilkan data!\n";
    echo "Instruksi: 'CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information.'\n\n";
} else {
    echo "✅ Tidak ada instruksi yang melarang menampilkan data\n\n";
}

// Cek apakah data perusahaan ada
echo "3. KEBERADAAN DATA PERUSAHAAN:\n";
if (strpos($loggedPrompt, 'System Template') !== false) {
    echo "✅ Data perusahaan 'System Template' ditemukan dalam prompt\n";
} else {
    echo "❌ Data perusahaan tidak ditemukan dalam prompt\n";
}

if (strpos($loggedPrompt, 'CURRENT SYSTEM DATA') !== false) {
    echo "✅ Section 'CURRENT SYSTEM DATA' ditemukan\n";
} else {
    echo "❌ Section 'CURRENT SYSTEM DATA' tidak ditemukan\n";
}

echo "\n4. ANALISIS RESPONSE APPROACH:\n";
// Berdasarkan log, ini menggunakan approach 'no_data_response' yang salah
echo "Berdasarkan log, response approach yang digunakan adalah 'no_data_response'\n";
echo "Ini SALAH karena data perusahaan sebenarnya tersedia!\n\n";

echo "5. KESIMPULAN:\n";
echo "Masalah utama: AI Planning Service menentukan response_approach sebagai 'no_data_response'\n";
echo "padahal data perusahaan tersedia. Ini menyebabkan prompt berisi instruksi yang\n";
echo "MELARANG AI menampilkan data, meskipun data sudah disertakan dalam prompt.\n\n";

echo "6. SOLUSI YANG DIPERLUKAN:\n";
echo "- Perbaiki logika penentuan response_approach di AiPlanningService\n";
echo "- Pastikan ketika data tersedia, approach adalah 'confident_detailed_response'\n";
echo "- Hapus instruksi yang melarang menampilkan data ketika data tersedia\n";