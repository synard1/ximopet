<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UIResourceService
{
    protected array $navigationStructure;
    protected array $availableRoutes;
    protected array $formStructures;
    
    public function __construct()
    {
        $this->loadUIResources();
    }

    /**
     * Get comprehensive UI navigation and structure information
     */
    public function getUIContext(): string
    {
        $context = "=== XiMoPet Application UI Structure & Navigation Guide ===\n\n";
        
        // Application Overview
        $context .= "APPLICATION: XiMoPet - Livestock Management System\n";
        $context .= "DESCRIPTION: Comprehensive poultry farming management application\n\n";
        
        // Main Navigation Structure
        $context .= "=== MAIN NAVIGATION MENU ===\n";
        $context .= $this->getNavigationStructure();
        
        // Available Routes and URLs
        $context .= "\n=== AVAILABLE ROUTES & URLS ===\n";
        $context .= $this->getRoutesStructure();
        
        // Form Structures
        $context .= "\n=== FORM STRUCTURES & DATA ENTRY ===\n";
        $context .= $this->getFormStructures();
        
        // User Roles and Permissions
        $context .= "\n=== USER ROLES & ACCESS LEVELS ===\n";
        $context .= $this->getUserRolesStructure();
        
        // Common Workflows
        $context .= "\n=== COMMON WORKFLOWS ===\n";
        $context .= $this->getWorkflowGuides();
        
        return $context;
    }

    /**
     * Get specific navigation guidance for user queries
     */
    public function getNavigationGuidance(string $feature): string
    {
        $guides = [
            'farm' => [
                'title' => 'Farm Management',
                'navigation' => 'Dashboard → Master Data → Farm',
                'url' => '/master-data/farm',
                'description' => 'Add, edit, and manage farm locations and basic information',
                'steps' => [
                    '1. Login to XiMoPet with appropriate permissions',
                    '2. From the main dashboard, click on "Master Data" in the sidebar',
                    '3. Select "Farm" from the Master Data submenu',
                    '4. Click the "+ Tambah Farm" button (green button, top right)',
                    '5. Fill in the farm details form with required information',
                    '6. Click "Simpan" to save the new farm data'
                ],
                'required_fields' => ['Nama Farm', 'Alamat', 'Koordinat GPS (optional)', 'Kontak Person'],
                'permissions' => 'Admin or Farm Manager role required'
            ],
            'kandang' => [
                'title' => 'Kandang (Cage) Management',
                'navigation' => 'Dashboard → Master Data → Kandang',
                'url' => '/master-data/kandang',
                'description' => 'Manage cages/pens within farms',
                'steps' => [
                    '1. Navigate to Master Data → Kandang',
                    '2. Click "+ Tambah Kandang" button',
                    '3. Select the Farm where kandang will be located',
                    '4. Enter kandang details (name, capacity, type)',
                    '5. Save the kandang information'
                ],
                'required_fields' => ['Farm', 'Nama Kandang', 'Kapasitas', 'Tipe Kandang'],
                'permissions' => 'Admin or Farm Manager role required'
            ],
            'livestock' => [
                'title' => 'Livestock Management',
                'navigation' => 'Dashboard → Master Data → Livestock',
                'url' => '/master-data/livestock',
                'description' => 'Add and manage livestock/poultry groups',
                'steps' => [
                    '1. Go to Master Data → Livestock',
                    '2. Click "+ Tambah Livestock"',
                    '3. Select Farm and Kandang',
                    '4. Enter livestock details (type, quantity, start date)',
                    '5. Save the livestock record'
                ],
                'required_fields' => ['Farm', 'Kandang', 'Jenis Ternak', 'Jumlah Awal', 'Tanggal Mulai'],
                'permissions' => 'Admin, Farm Manager, or Operator role required'
            ],
            'feed' => [
                'title' => 'Feed Management',
                'navigation' => 'Dashboard → Master Data → Feed / Pembelian → Feed Purchase',
                'url' => '/purchase/feed',
                'description' => 'Manage feed inventory and purchases',
                'steps' => [
                    '1. For feed types: Master Data → Feed',
                    '2. For feed purchases: Pembelian → Feed Purchase',
                    '3. Click "+ Tambah" button',
                    '4. Fill in feed/purchase details',
                    '5. Save the record'
                ],
                'required_fields' => ['Jenis Pakan', 'Supplier', 'Jumlah', 'Harga', 'Tanggal'],
                'permissions' => 'Admin, Farm Manager, or Purchase Manager role required'
            ],
            'recording' => [
                'title' => 'Production Recording',
                'navigation' => 'Dashboard → Recording → Production',
                'url' => '/recording/production',
                'description' => 'Record daily production data',
                'steps' => [
                    '1. Navigate to Recording → Production',
                    '2. Select the date for recording',
                    '3. Choose Farm and Kandang',
                    '4. Enter production data (eggs, mortality, feed consumption)',
                    '5. Save the daily record'
                ],
                'required_fields' => ['Tanggal', 'Farm', 'Kandang', 'Produksi Telur', 'Konsumsi Pakan'],
                'permissions' => 'All authenticated users can record data'
            ]
        ];

        $feature = strtolower($feature);
        if (isset($guides[$feature])) {
            $guide = $guides[$feature];
            $result = "=== {$guide['title']} ===\n";
            $result .= "NAVIGATION: {$guide['navigation']}\n";
            $result .= "URL: {$guide['url']}\n";
            $result .= "DESCRIPTION: {$guide['description']}\n\n";
            $result .= "STEP-BY-STEP INSTRUCTIONS:\n";
            foreach ($guide['steps'] as $step) {
                $result .= "  {$step}\n";
            }
            $result .= "\nREQUIRED FIELDS:\n";
            foreach ($guide['required_fields'] as $field) {
                $result .= "  - {$field}\n";
            }
            $result .= "\nPERMISSIONS: {$guide['permissions']}\n";
            
            return $result;
        }

        return "Navigation guidance for '{$feature}' not found. Available features: " . implode(', ', array_keys($guides));
    }

    /**
     * Get main navigation structure
     */
    protected function getNavigationStructure(): string
    {
        return "MAIN MENU STRUCTURE:
├── Dashboard (/)
│   ├── Overview statistics
│   ├── Recent activities
│   └── Quick access cards
│
├── Master Data (/master-data)
│   ├── Farm (/master-data/farm)
│   ├── Kandang (/master-data/kandang)
│   ├── Livestock (/master-data/livestock)
│   ├── Feed (/master-data/feed)
│   ├── Supply (/master-data/supply)
│   └── Users (/master-data/user)
│
├── Pembelian (Purchases) (/purchase)
│   ├── Feed Purchase (/purchase/feed)
│   ├── Supply Purchase (/purchase/supply)
│   └── Purchase History
│
├── Recording (/recording)
│   ├── Production (/recording/production)
│   ├── Feed Consumption
│   ├── Mortality Records
│   └── Health Records
│
├── Reports (/reports)
│   ├── Production Reports
│   ├── Financial Reports
│   ├── Inventory Reports
│   └── Analytics Dashboard
│
└── Settings (/settings)
    ├── Profile Settings
    ├── Company Settings
    └── System Configuration";
    }

    /**
     * Get available routes structure
     */
    protected function getRoutesStructure(): string
    {
        $routes = "KEY APPLICATION ROUTES:

AUTHENTICATION:
- /login - User login page
- /register - User registration (if enabled)
- /logout - Logout action

DASHBOARD:
- / - Main dashboard with overview
- /dashboard - Alternative dashboard route

MASTER DATA MANAGEMENT:
- /master-data/farm - Farm management (list, create, edit, delete)
- /master-data/kandang - Kandang/cage management
- /master-data/livestock - Livestock group management
- /master-data/feed - Feed type management
- /master-data/supply - Supply/equipment management
- /master-data/user - User management (admin only)

PURCHASE MANAGEMENT:
- /purchase/feed - Feed purchase records
- /purchase/supply - Supply purchase records

RECORDING & TRACKING:
- /recording/production - Daily production recording
- /recording/feed-consumption - Feed usage tracking
- /recording/mortality - Mortality tracking
- /recording/health - Health monitoring

REPORTS & ANALYTICS:
- /reports/production - Production analysis
- /reports/financial - Financial reports
- /reports/inventory - Inventory status
- /analytics - Advanced analytics dashboard

API ENDPOINTS:
- /api/chat/* - AI chat system endpoints
- /api/master-data/* - Master data API endpoints
- /api/reports/* - Reports and analytics APIs";

        return $routes;
    }

    /**
     * Get form structures and field information
     */
    protected function getFormStructures(): string
    {
        return "FORM STRUCTURES & REQUIRED FIELDS:

FARM FORM (/master-data/farm):
Required Fields:
- Nama Farm (Farm Name) - Text, max 255 chars
- Alamat (Address) - Textarea, detailed location
- Koordinat GPS - Text, format: latitude,longitude
- Kontak Person - Text, responsible person name
- Nomor Telepon - Text, contact number
Optional Fields:
- Deskripsi - Additional description
- Status - Active/Inactive

KANDANG FORM (/master-data/kandang):
Required Fields:
- Farm - Dropdown, select existing farm
- Nama Kandang - Text, cage identifier
- Kapasitas - Number, maximum capacity
- Tipe Kandang - Dropdown (Broiler, Layer, Breeder)
- Ukuran - Text, physical dimensions
Optional Fields:
- Keterangan - Additional notes

LIVESTOCK FORM (/master-data/livestock):
Required Fields:
- Farm - Dropdown, select farm
- Kandang - Dropdown, select cage (filtered by farm)
- Jenis Ternak - Dropdown (Ayam Broiler, Ayam Layer, etc.)
- Jumlah Awal - Number, initial quantity
- Tanggal Mulai - Date, start date
- Berat Rata-rata - Number, average weight
Optional Fields:
- Supplier - Text, source of livestock
- Batch Number - Text, identification
- Keterangan - Additional notes

FEED PURCHASE FORM (/purchase/feed):
Required Fields:
- Tanggal Pembelian - Date
- Supplier - Text or dropdown
- Jenis Pakan - Dropdown, feed type
- Jumlah - Number, quantity in kg/tons
- Harga per Unit - Number, price per kg/ton
- Total Harga - Auto-calculated
Optional Fields:
- Nomor Invoice - Text
- Keterangan - Notes";
    }

    /**
     * Get user roles and permissions structure
     */
    protected function getUserRolesStructure(): string
    {
        return "USER ROLES & PERMISSIONS:

SUPER ADMIN:
- Full system access
- Company management
- User role assignments
- System configuration

ADMIN:
- Full access within company
- User management within company
- All master data operations
- All recording and reporting

FARM MANAGER:
- Farm-specific management
- Livestock management
- Recording operations
- Limited master data access

OPERATOR:
- Daily recording operations
- Production data entry
- Feed consumption recording
- Basic reporting access

VIEWER:
- Read-only access
- View reports and analytics
- No data entry permissions

ACCESS LEVELS:
- Company-scoped data access
- Role-based menu visibility
- Feature-level permissions
- Data modification restrictions";
    }

    /**
     * Get common workflow guides
     */
    protected function getWorkflowGuides(): string
    {
        return "COMMON WORKFLOWS:

1. SETTING UP A NEW FARM:
   a) Create Farm record (Master Data → Farm)
   b) Add Kandang/cages (Master Data → Kandang)
   c) Register livestock groups (Master Data → Livestock)
   d) Set up feed types (Master Data → Feed)
   e) Begin daily recording (Recording → Production)

2. DAILY OPERATIONS:
   a) Record production data (eggs, mortality)
   b) Log feed consumption
   c) Update inventory levels
   d) Monitor health status
   e) Generate daily reports

3. PURCHASING WORKFLOW:
   a) Check inventory levels
   b) Create purchase orders
   c) Record purchases (Pembelian menu)
   d) Update inventory
   e) Track expenses

4. MONTHLY REPORTING:
   a) Generate production reports
   b) Calculate feed conversion ratios
   c) Analyze financial performance
   d) Plan next month operations
   e) Stock inventory assessment

TROUBLESHOOTING:
- If buttons are disabled: Check user permissions
- If data is not showing: Verify company scope/filtering
- If forms won't submit: Check required field validation
- For access issues: Contact system administrator";
    }

    /**
     * Load UI resources from configuration and routes
     */
    protected function loadUIResources(): void
    {
        try {
            // Load from cache if available
            $cacheKey = 'ui_resources_structure';
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                $this->navigationStructure = $cached['navigation'] ?? [];
                $this->availableRoutes = $cached['routes'] ?? [];
                $this->formStructures = $cached['forms'] ?? [];
                return;
            }
            
            // Build navigation structure
            $this->buildNavigationStructure();
            $this->buildRoutesStructure();
            $this->buildFormStructures();
            
            // Cache for 1 hour
            Cache::put($cacheKey, [
                'navigation' => $this->navigationStructure,
                'routes' => $this->availableRoutes,
                'forms' => $this->formStructures
            ], 3600);
            
        } catch (\Exception $e) {
            Log::error('UIResourceService: Error loading UI resources', [
                'error' => $e->getMessage()
            ]);
            
            // Set defaults
            $this->navigationStructure = [];
            $this->availableRoutes = [];
            $this->formStructures = [];
        }
    }

    /**
     * Build navigation structure from routes
     */
    protected function buildNavigationStructure(): void
    {
        $this->navigationStructure = [
            'dashboard' => 'Main Dashboard',
            'master-data' => [
                'farm' => 'Farm Management',
                'kandang' => 'Kandang Management',
                'livestock' => 'Livestock Management',
                'feed' => 'Feed Management',
                'supply' => 'Supply Management',
                'user' => 'User Management'
            ],
            'purchase' => [
                'feed' => 'Feed Purchases',
                'supply' => 'Supply Purchases'
            ],
            'recording' => [
                'production' => 'Production Recording',
                'feed-consumption' => 'Feed Consumption',
                'mortality' => 'Mortality Records'
            ],
            'reports' => [
                'production' => 'Production Reports',
                'financial' => 'Financial Reports',
                'inventory' => 'Inventory Reports'
            ]
        ];
    }

    /**
     * Build available routes structure
     */
    protected function buildRoutesStructure(): void
    {
        $this->availableRoutes = [
            'auth' => ['/login', '/logout', '/register'],
            'dashboard' => ['/dashboard', '/'],
            'master-data' => ['/master-data/farm', '/master-data/kandang', '/master-data/livestock'],
            'purchase' => ['/purchase/feed', '/purchase/supply'],
            'recording' => ['/recording/production'],
            'reports' => ['/reports/production', '/reports/financial']
        ];
    }

    /**
     * Build form structures information
     */
    protected function buildFormStructures(): void
    {
        $this->formStructures = [
            'farm' => ['name', 'address', 'gps_coordinates', 'contact_person'],
            'kandang' => ['farm_id', 'name', 'capacity', 'type'],
            'livestock' => ['farm_id', 'kandang_id', 'type', 'initial_quantity', 'start_date'],
            'feed_purchase' => ['date', 'supplier', 'feed_type', 'quantity', 'price']
        ];
    }

    /**
     * Clear UI resources cache
     */
    public function clearCache(): bool
    {
        return Cache::forget('ui_resources_structure');
    }
}