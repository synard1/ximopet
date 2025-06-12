<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refactored Mortality Chart - JSON/Array Data with Line Chart</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .chart-container {
            position: relative;
            height: 500px;
            margin-bottom: 30px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(145deg, #f0f0f0, #ffffff);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .filter-info {
            background: linear-gradient(145deg, #e3f2fd, #ffffff);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }

        .filter-info p {
            color: #1976d2;
            font-weight: 500;
            margin: 0;
        }

        .code-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid #e9ecef;
        }

        .code-section h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .code-section pre {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .header h1 {
                font-size: 2em;
            }

            .chart-container {
                height: 400px;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📊 Refactored Mortality Chart</h1>
            <p>Menggunakan JSON/Array data dan Line Chart styling yang modern</p>
        </div>

        <div class="filter-info">
            <p>💡 Tip: Klik pada legend untuk menyembunyikan/menampilkan data. Statistik akan ter-update secara dinamis.
            </p>
        </div>

        <div class="stats-container">
            <div class="stat-card mortality-stat-card">
                <h3>Total Deaths</h3>
                <div class="number mortality-stat-number" style="color: #dc3545;">0</div>
                <p>From visible datasets</p>
            </div>
            <div class="stat-card mortality-stat-card">
                <h3>Active Sources</h3>
                <div class="number mortality-stat-number" style="color: #2196f3;">0</div>
                <p>Sources displayed</p>
            </div>
            <div class="stat-card mortality-stat-card">
                <h3>Avg Deaths/Day</h3>
                <div class="number mortality-stat-number" style="color: #ff9800;">0</div>
                <p>Daily average</p>
            </div>
            <div class="stat-card mortality-stat-card">
                <h3>Max Daily Deaths</h3>
                <div class="number mortality-stat-number" style="color: #4caf50;">0</div>
                <p>Peak mortality</p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="mortalityChart"></canvas>
        </div>

        <div class="code-section">
            <h3>🔧 Refactor Highlights</h3>
            <pre><code>// 1. JSON/Array Data Format (like TestMortalityData.php)
const mortalityData = [
    { date: "2025-05-30", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 1 },
    { date: "2025-05-31", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 2 },
    { date: "2025-06-01", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 3 },
    // ... more data
];

// 2. Modern Chart Configuration
const chartConfig = {
    type: 'line',
    data: {
        labels: dateLabels,
        datasets: processedDatasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: { display: true, text: "Chart Title", font: { size: 18, weight: 'bold' } },
            legend: { display: true, position: 'top', labels: { usePointStyle: true } },
            tooltip: { backgroundColor: 'rgba(0, 0, 0, 0.8)', cornerRadius: 10 }
        },
        scales: {
            x: { title: { display: true, text: 'Date' } },
            y: { beginAtZero: true, title: { display: true, text: 'Deaths Count' } }
        },
        interaction: { intersect: false, mode: 'index' },
        animation: { duration: 1000, easing: 'easeInOutQuart' }
    }
};

// 3. Interactive Statistics Update
function updateStats(chart) {
    const visibleDatasets = chart.data.datasets.filter((dataset, index) => 
        chart.isDatasetVisible(index)
    );
    // Calculate and update stats...
}</code></pre>
        </div>
    </div>

    <script>
        // Simulated mortality data in JSON/Array format (like TestMortalityData.php)
        const rawMortalityData = [
            { date: "2025-05-30", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 1 },
            { date: "2025-05-31", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 2 },
            { date: "2025-06-01", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 3 },
            { date: "2025-05-30", farm: "Demo Farm 2", coop: "Kandang A", livestock: "Batch-2", deaths: 3 },
            { date: "2025-05-31", farm: "Demo Farm 2", coop: "Kandang A", livestock: "Batch-2", deaths: 4 },
            { date: "2025-06-01", farm: "Demo Farm 2", coop: "Kandang A", livestock: "Batch-2", deaths: 2 },
            { date: "2025-05-30", farm: "Farm Demo 3", coop: "Kandang X", livestock: "Batch-3", deaths: 5 },
            { date: "2025-05-31", farm: "Farm Demo 3", coop: "Kandang X", livestock: "Batch-3", deaths: 6 },
            { date: "2025-06-01", farm: "Farm Demo 3", coop: "Kandang X", livestock: "Batch-3", deaths: 4 },
            { date: "2025-06-02", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 2 },
            { date: "2025-06-03", farm: "Demo Farm", coop: "Kandang 1", livestock: "Batch-1", deaths: 1 },
            { date: "2025-06-02", farm: "Demo Farm 2", coop: "Kandang A", livestock: "Batch-2", deaths: 3 },
            { date: "2025-06-03", farm: "Demo Farm 2", coop: "Kandang A", livestock: "Batch-2", deaths: 5 },
            { date: "2025-06-02", farm: "Farm Demo 3", coop: "Kandang X", livestock: "Batch-3", deaths: 3 },
            { date: "2025-06-03", farm: "Farm Demo 3", coop: "Kandang X", livestock: "Batch-3", deaths: 2 },
        ];

        // Process data for farm comparison chart (like refactored AnalyticsService)
        function buildFarmComparisonChart(mortalityData) {
            // Group by farm and date
            const farmGroups = {};
            mortalityData.forEach(item => {
                const farm = item.farm;
                if (!farmGroups[farm]) {
                    farmGroups[farm] = {};
                }
                const date = item.date;
                farmGroups[farm][date] = (farmGroups[farm][date] || 0) + item.deaths;
            });

            // Get unique dates and sort
            const dates = [...new Set(mortalityData.map(item => item.date))].sort();
            const labels = dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
            });

            // Create datasets with modern styling
            const datasets = [];
            const colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4'];
            let colorIndex = 0;

            Object.keys(farmGroups).forEach(farm => {
                const data = dates.map(date => farmGroups[farm][date] || 0);
                const color = colors[colorIndex % colors.length];

                datasets.push({
                    label: farm,
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '20',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: color,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                });
                colorIndex++;
            });

            return {
                type: 'line',
                title: 'Farm Mortality Comparison',
                labels: labels,
                datasets: datasets
            };
        }

        // Update statistics based on visible datasets
        function updateMortalityStats(chart) {
            if (!chart) return;
            
            const visibleDatasets = chart.data.datasets.filter((dataset, index) => 
                chart.isDatasetVisible(index)
            );
            
            let totalDeaths = 0;
            let totalDays = 0;
            let maxDailyDeaths = 0;
            let activeSources = visibleDatasets.length;
            
            visibleDatasets.forEach(dataset => {
                dataset.data.forEach(value => {
                    totalDeaths += value;
                    if (value > 0) totalDays++;
                    if (value > maxDailyDeaths) maxDailyDeaths = value;
                });
            });
            
            const avgDeaths = totalDays > 0 ? (totalDeaths / totalDays).toFixed(1) : 0;
            
            // Update statistics cards
            const statsCards = document.querySelectorAll('.mortality-stat-card');
            if (statsCards.length >= 4) {
                // Total Deaths
                statsCards[0].querySelector('.mortality-stat-number').textContent = totalDeaths.toLocaleString();
                
                // Active Sources
                statsCards[1].querySelector('.mortality-stat-number').textContent = activeSources;
                
                // Avg Deaths/Day
                statsCards[2].querySelector('.mortality-stat-number').textContent = avgDeaths;
                
                // Max Daily Deaths
                statsCards[3].querySelector('.mortality-stat-number').textContent = maxDailyDeaths;
            }
            
            console.log('Updated statistics:', {
                totalDeaths,
                activeSources,
                avgDeaths,
                maxDailyDeaths
            });
        }

        // Process chart data
        const chartData = buildFarmComparisonChart(rawMortalityData);

        // Create chart with refactored configuration
        const ctx = document.getElementById('mortalityChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: chartData.type,
            data: {
                labels: chartData.labels,
                datasets: chartData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: chartData.title,
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        color: '#333',
                        padding: 20
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 14
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.datasetIndex;
                            const chart = legend.chart;
                            
                            if (chart.isDatasetVisible(index)) {
                                chart.hide(index);
                                legendItem.hidden = true;
                            } else {
                                chart.show(index);
                                legendItem.hidden = false;
                            }
                            
                            // Update statistics
                            updateMortalityStats(chart);
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#fff',
                        borderWidth: 1,
                        cornerRadius: 10,
                        displayColors: true,
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Date: ' + tooltipItems[0].label;
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' deaths';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Deaths Count',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Update initial statistics
        updateMortalityStats(chart);

        // Add hover animations to stat cards
        setTimeout(() => {
            document.querySelectorAll('.mortality-stat-card').forEach((card, index) => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
                
                // Animate loading for stat cards
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });
        }, 500);
    </script>
</body>

</html>