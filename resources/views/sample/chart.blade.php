<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Deaths Chart</title>
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
            max-width: 1200px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
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
            color: #667eea;
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
            <h1>📊 Farm Deaths Monitoring</h1>
            <p>Interactive chart untuk memantau data kematian di berbagai farm</p>
        </div>

        <div class="filter-info">
            <p>💡 Tip: Klik pada legend untuk menyembunyikan/menampilkan data farm tertentu</p>
        </div>

        <div class="chart-container">
            <canvas id="farmChart"></canvas>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Deaths</h3>
                <div class="number" id="totalDeaths">0</div>
                <p>Seluruh periode</p>
            </div>
            <div class="stat-card">
                <h3>Active Farms</h3>
                <div class="number" id="activeFarms">0</div>
                <p>Farm yang ditampilkan</p>
            </div>
            <div class="stat-card">
                <h3>Avg Deaths/Day</h3>
                <div class="number" id="avgDeaths">0</div>
                <p>Rata-rata per hari</p>
            </div>
        </div>
    </div>

    <script>
        // Data mentah
        const rawData = [
            { date: "2025-05-30", farm: "Demo Farm", deaths: 1 },
            { date: "2025-05-31", farm: "Demo Farm", deaths: 2 },
            { date: "2025-06-01", farm: "Demo Farm", deaths: 3 },
            { date: "2025-05-30", farm: "Demo Farm 2", deaths: 3 },
            { date: "2025-05-31", farm: "Demo Farm 2", deaths: 4 },
            { date: "2025-06-01", farm: "Demo Farm 2", deaths: 2 },
            { date: "2025-05-30", farm: "Farm Demo 3", deaths: 5 },
            { date: "2025-05-31", farm: "Farm Demo 3", deaths: 6 },
            { date: "2025-06-01", farm: "Farm Demo 3", deaths: 4 }
        ];

        // Warna untuk setiap farm
        const farmColors = {
            "Demo Farm": {
                background: "rgba(102, 126, 234, 0.2)",
                border: "rgba(102, 126, 234, 1)"
            },
            "Demo Farm 2": {
                background: "rgba(255, 99, 132, 0.2)",
                border: "rgba(255, 99, 132, 1)"
            },
            "Farm Demo 3": {
                background: "rgba(75, 192, 192, 0.2)",
                border: "rgba(75, 192, 192, 1)"
            }
        };

        // Proses data untuk chart
        function processData() {
            const farms = [...new Set(rawData.map(item => item.farm))];
            const dates = [...new Set(rawData.map(item => item.date))].sort();
            
            const datasets = farms.map(farm => {
                const data = dates.map(date => {
                    const item = rawData.find(d => d.farm === farm && d.date === date);
                    return item ? item.deaths : 0;
                });
                
                return {
                    label: farm,
                    data: data,
                    backgroundColor: farmColors[farm].background,
                    borderColor: farmColors[farm].border,
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: farmColors[farm].border,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                };
            });
            
            return {
                labels: dates.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('id-ID', { 
                        day: '2-digit', 
                        month: 'short' 
                    });
                }),
                datasets: datasets
            };
        }

        // Update statistik
        function updateStats(chart) {
            const visibleDatasets = chart.data.datasets.filter((dataset, index) => 
                chart.isDatasetVisible(index)
            );
            
            let totalDeaths = 0;
            let totalDays = 0;
            
            visibleDatasets.forEach(dataset => {
                dataset.data.forEach(value => {
                    totalDeaths += value;
                    if (value > 0) totalDays++;
                });
            });
            
            const avgDeaths = totalDays > 0 ? (totalDeaths / totalDays).toFixed(1) : 0;
            
            document.getElementById('totalDeaths').textContent = totalDeaths;
            document.getElementById('activeFarms').textContent = visibleDatasets.length;
            document.getElementById('avgDeaths').textContent = avgDeaths;
        }

        // Konfigurasi chart
        const ctx = document.getElementById('farmChart').getContext('2d');
        const chartData = processData();
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Trend Kematian Hewan per Farm',
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
                            
                            updateStats(chart);
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
                                return 'Tanggal: ' + tooltipItems[0].label;
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
                            text: 'Tanggal',
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
                            text: 'Jumlah Kematian',
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

        // Update statistik awal
        updateStats(chart);

        // Animasi loading untuk stat cards
        setTimeout(() => {
            document.querySelectorAll('.stat-card').forEach((card, index) => {
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