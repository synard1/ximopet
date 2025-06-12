<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Smart Analytics Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">Analisis cerdas untuk optimasi produksi peternakan</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button wire:click="generateAnalytics"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    Refresh Analytics
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="farm" class="block text-sm font-medium text-gray-700 mb-1">Farm</label>
                <select wire:model="selectedFarm" id="farm"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Semua Farm</option>
                    @foreach($farms as $farm)
                    <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="coop" class="block text-sm font-medium text-gray-700 mb-1">Kandang</label>
                <select wire:model="selectedCoop" id="coop"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Semua Kandang</option>
                    @foreach($coops as $coop)
                    <option value="{{ $coop->id }}">{{ $coop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input wire:model="dateFrom" type="date" id="dateFrom"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input wire:model="dateTo" type="date" id="dateTo"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>
    </div>

    <!-- Overview Metrics -->
    @if(!empty($overviewMetrics))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Mortality Rate</dt>
                            <dd class="text-lg font-medium text-gray-900">{{
                                number_format($overviewMetrics['avg_mortality_rate'], 2) }}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Efficiency Score</dt>
                            <dd class="text-lg font-medium text-gray-900">{{
                                number_format($overviewMetrics['avg_efficiency_score'], 1) }}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg FCR</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($overviewMetrics['avg_fcr'],
                                3) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900">Rp {{
                                number_format($overviewMetrics['total_revenue']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Performance Insights -->
    @if(!empty($insights))
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Insights</h3>
        <div class="space-y-3">
            @foreach($insights as $insight)
            <div class="flex items-start space-x-3 p-3 rounded-lg 
                    @if($insight['type'] === 'warning') bg-yellow-50 border border-yellow-200
                    @elseif($insight['type'] === 'success') bg-green-50 border border-green-200
                    @else bg-blue-50 border border-blue-200 @endif">
                <div class="flex-shrink-0">
                    @if($insight['type'] === 'warning')
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    @elseif($insight['type'] === 'success')
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium 
                            @if($insight['type'] === 'warning') text-yellow-800
                            @elseif($insight['type'] === 'success') text-green-800
                            @else text-blue-800 @endif">
                        {{ $insight['title'] }}
                    </h4>
                    <p class="text-sm 
                            @if($insight['type'] === 'warning') text-yellow-700
                            @elseif($insight['type'] === 'success') text-green-700
                            @else text-blue-700 @endif">
                        {{ $insight['description'] }}
                    </p>
                    <p class="text-xs font-medium mt-1 
                            @if($insight['type'] === 'warning') text-yellow-800
                            @elseif($insight['type'] === 'success') text-green-800
                            @else text-blue-800 @endif">
                        📋 {{ $insight['action'] }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'overview')"
                    class="@if($activeTab === 'overview') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Overview
                </button>
                <button wire:click="$set('activeTab', 'mortality')"
                    class="@if($activeTab === 'mortality') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Analisis Kematian
                </button>
                <button wire:click="$set('activeTab', 'sales')"
                    class="@if($activeTab === 'sales') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Analisis Penjualan
                </button>
                <button wire:click="$set('activeTab', 'production')"
                    class="@if($activeTab === 'production') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Analisis Produksi
                </button>
                <button wire:click="$set('activeTab', 'ranking')"
                    class="@if($activeTab === 'ranking') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Ranking Kandang
                </button>
                <button wire:click="$set('activeTab', 'alerts')"
                    class="@if($activeTab === 'alerts') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Alert & Rekomendasi
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if($activeTab === 'overview')
            <!-- Overview Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Mortality Trend Chart -->
                <div class="bg-white p-4 rounded-lg border">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Trend Tingkat Kematian</h4>
                    <div class="h-64">
                        <canvas id="mortalityChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Efficiency Trend Chart -->
                <div class="bg-white p-4 rounded-lg border">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Trend Skor Efisiensi</h4>
                    <div class="h-64">
                        <canvas id="efficiencyChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- FCR Trend Chart -->
                <div class="bg-white p-4 rounded-lg border lg:col-span-2">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Trend FCR (Feed Conversion Ratio)</h4>
                    <div class="h-64">
                        <canvas id="fcrChart" width="800" height="200"></canvas>
                    </div>
                </div>
            </div>

            @elseif($activeTab === 'mortality')
            <!-- Mortality Analysis -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Analisis Kematian per Kandang</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Farm</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandang</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Kematian</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rata-rata %</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Max Harian</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Hari</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($mortalityAnalysis as $analysis)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->farm->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->coop->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                            @if($analysis->total_mortality > 1000) text-red-600 
                                            @elseif($analysis->total_mortality > 500) text-yellow-600 
                                            @else text-green-600 @endif">
                                    {{ number_format($analysis->total_mortality) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm 
                                            @if($analysis->avg_mortality_rate > 10) text-red-600 
                                            @elseif($analysis->avg_mortality_rate > 5) text-yellow-600 
                                            @else text-green-600 @endif">
                                    {{ number_format($analysis->avg_mortality_rate, 2) }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($analysis->max_daily_mortality) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $analysis->total_days
                                    }} hari</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($activeTab === 'sales')
            <!-- Sales Analysis -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Analisis Penjualan per Kandang</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Farm</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandang</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Terjual</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Berat (kg)</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Revenue</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg Harga/Ekor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($salesAnalysis as $analysis)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->farm->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->coop->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">{{
                                    number_format($analysis->total_sales) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($analysis->total_weight, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">Rp {{
                                    number_format($analysis->total_revenue) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{
                                    number_format($analysis->avg_price_per_bird) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($activeTab === 'production')
            <!-- Production Analysis -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Analisis Produksi per Kandang</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Farm</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandang</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg Berat (g)</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg Daily Gain</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg FCR</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efficiency Score</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Max Berat</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($productionAnalysis as $analysis)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->farm->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $analysis->coop->name
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($analysis->avg_weight) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm 
                                            @if($analysis->avg_daily_gain >= 45) text-green-600 
                                            @elseif($analysis->avg_daily_gain >= 35) text-yellow-600 
                                            @else text-red-600 @endif">
                                    {{ number_format($analysis->avg_daily_gain, 1) }}g
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm 
                                            @if($analysis->avg_fcr <= 1.7) text-green-600 
                                            @elseif($analysis->avg_fcr <= 2.0) text-yellow-600 
                                            @else text-red-600 @endif">
                                    {{ number_format($analysis->avg_fcr, 3) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="h-2 rounded-full 
                                                        @if($analysis->avg_efficiency >= 80) bg-green-500 
                                                        @elseif($analysis->avg_efficiency >= 60) bg-yellow-500 
                                                        @else bg-red-500 @endif"
                                                style="width: {{ min(100, $analysis->avg_efficiency) }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ number_format($analysis->avg_efficiency, 1)
                                            }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($analysis->max_weight) }}g</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($activeTab === 'ranking')
            <!-- Coop Ranking -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ranking Performa Kandang</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rank</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Farm</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandang</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efficiency Score</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mortality %</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    FCR</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Daily Gain</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($coopRanking as $index => $ranking)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if($index === 0)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        🏆 #{{ $index + 1 }}
                                    </span>
                                    @elseif($index < 3) <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        #{{ $index + 1 }}
                                        </span>
                                        @else
                                        <span class="text-gray-500">#{{ $index + 1 }}</span>
                                        @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ranking->farm->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ranking->coop->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">{{
                                    number_format($ranking->avg_efficiency, 1) }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($ranking->avg_mortality, 2) }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($ranking->avg_fcr, 3) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    number_format($ranking->avg_daily_gain, 1) }}g</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @elseif($activeTab === 'alerts')
            <!-- Alerts & Recommendations -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Alert & Rekomendasi</h3>
                    <select wire:model="alertsFilter"
                        class="block px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="all">Semua Alert</option>
                        <option value="unresolved">Belum Resolve</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div class="space-y-4">
                    @foreach($alerts as $alert)
                    <div class="bg-white border rounded-lg p-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($alert->severity === 'critical') bg-red-100 text-red-800
                                                @elseif($alert->severity === 'high') bg-orange-100 text-orange-800
                                                @elseif($alert->severity === 'medium') bg-yellow-100 text-yellow-800
                                                @else bg-blue-100 text-blue-800 @endif">
                                        {{ strtoupper($alert->severity) }}
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $alert->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ $alert->description }}</p>
                                    @if($alert->recommendation)
                                    <div class="mt-2 p-2 bg-blue-50 rounded-md">
                                        <p class="text-sm text-blue-700">💡 <strong>Rekomendasi:</strong> {{
                                            $alert->recommendation }}</p>
                                    </div>
                                    @endif
                                    <div class="mt-2 text-xs text-gray-500">
                                        <span>{{ $alert->farm->name }} - {{ $alert->coop->name }}</span>
                                        <span class="mx-1">•</span>
                                        <span>{{ $alert->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if(!$alert->is_resolved)
                                <button wire:click="resolveAlert('{{ $alert->id }}')"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Resolve
                                </button>
                                @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Resolved
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $alerts->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
    function initCharts() {
        // Mortality Chart
        const mortalityCtx = document.getElementById('mortalityChart');
        if (mortalityCtx && @this.mortalityTrendData) {
            new Chart(mortalityCtx, {
                type: 'line',
                data: @this.mortalityTrendData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Mortality Rate (%)'
                            }
                        }
                    }
                }
            });
        }

        // Efficiency Chart
        const efficiencyCtx = document.getElementById('efficiencyChart');
        if (efficiencyCtx && @this.efficiencyTrendData) {
            new Chart(efficiencyCtx, {
                type: 'line',
                data: @this.efficiencyTrendData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Efficiency Score (%)'
                            }
                        }
                    }
                }
            });
        }

        // FCR Chart
        const fcrCtx = document.getElementById('fcrChart');
        if (fcrCtx && @this.fcrTrendData) {
            new Chart(fcrCtx, {
                type: 'line',
                data: @this.fcrTrendData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'FCR'
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize charts
    setTimeout(initCharts, 100);

    // Reinitialize charts when data changes
    Livewire.hook('message.processed', (message, component) => {
        setTimeout(initCharts, 100);
    });
});
</script>
@endpush