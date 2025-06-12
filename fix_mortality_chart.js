// Fixed Mortality Chart JavaScript Implementation

// Initialize advanced mortality chart with comprehensive logging
function initializeAdvancedMortalityChart() {
    console.log(
        "[Mortality Chart Debug] ========== INITIALIZATION START =========="
    );
    console.log(
        "[Mortality Chart Debug] Starting advanced mortality chart initialization"
    );

    // Check if we're on the mortality tab
    const mortalityTab = document.querySelector(
        '.nav-link[wire\\:click\\.prevent*="mortality"]'
    );
    const isActive = mortalityTab?.classList.contains("active");
    console.log("[Mortality Chart Debug] Mortality tab active:", isActive);

    if (!isActive) {
        console.log(
            "[Mortality Chart Debug] Not on mortality tab, skipping initialization"
        );
        return;
    }

    // Check Chart.js availability
    if (typeof Chart === "undefined") {
        console.error("[Mortality Chart Debug] ❌ Chart.js not loaded!");
        setTimeout(() => {
            console.log(
                "[Mortality Chart Debug] Retrying chart initialization in 1 second..."
            );
            initializeAdvancedMortalityChart();
        }, 1000);
        return;
    }
    console.log(
        "[Mortality Chart Debug] ✅ Chart.js available, version:",
        Chart.version
    );

    // Check all required elements
    const elements = checkMortalityChartElements();

    if (!elements.canvas) {
        console.error(
            "[Mortality Chart Debug] ❌ Canvas element not found! Retrying in 500ms..."
        );
        setTimeout(() => {
            initializeAdvancedMortalityChart();
        }, 500);
        return;
    }

    // Check if canvas is visible and has dimensions
    const canvasRect = elements.canvas.getBoundingClientRect();
    console.log("[Mortality Chart Debug] Canvas dimensions:", {
        width: canvasRect.width,
        height: canvasRect.height,
        visible: canvasRect.width > 0 && canvasRect.height > 0,
    });

    if (canvasRect.width === 0 || canvasRect.height === 0) {
        console.warn(
            "[Mortality Chart Debug] ⚠️ Canvas has zero dimensions, forcing container visibility"
        );
        if (elements.container) {
            elements.container.style.display = "block";
            elements.container.style.height = "400px";
        }
        // Retry after making container visible
        setTimeout(() => {
            initializeAdvancedMortalityChart();
        }, 100);
        return;
    }

    try {
        // Get fresh chart data from component using Livewire call
        console.log(
            "[Mortality Chart Debug] Fetching chart data via Livewire..."
        );

        // Make Livewire call to get data
        Livewire.find(
            document.querySelector("[wire\\:id]").getAttribute("wire:id")
        )
            .call("getMortalityChartData")
            .then(function (chartData) {
                console.log(
                    "[Mortality Chart Debug] Chart data received from Livewire:",
                    chartData
                );

                // Detailed data validation
                const dataValidation = {
                    hasData: !!chartData,
                    hasLabels:
                        chartData?.labels && Array.isArray(chartData.labels),
                    labelsCount: chartData?.labels?.length || 0,
                    hasDatasets:
                        chartData?.datasets &&
                        Array.isArray(chartData.datasets),
                    datasetsCount: chartData?.datasets?.length || 0,
                    chartType: chartData?.type,
                    hasOptions: !!chartData?.options,
                };
                console.log(
                    "[Mortality Chart Debug] Data validation:",
                    dataValidation
                );

                // Check if we have data
                if (
                    !chartData ||
                    !chartData.labels ||
                    chartData.labels.length === 0
                ) {
                    console.warn(
                        "[Mortality Chart Debug] ⚠️ No data available for chart"
                    );
                    if (elements.container) {
                        elements.container.style.display = "none";
                        console.log(
                            "[Mortality Chart Debug] Chart container hidden"
                        );
                    }
                    if (elements.noData) {
                        elements.noData.classList.remove("d-none");
                        console.log(
                            "[Mortality Chart Debug] No data message shown"
                        );
                    }
                    return;
                }

                // Show chart container and hide no data message
                if (elements.container) {
                    elements.container.style.display = "block";
                    console.log(
                        "[Mortality Chart Debug] Chart container shown"
                    );
                }
                if (elements.noData) {
                    elements.noData.classList.add("d-none");
                    console.log(
                        "[Mortality Chart Debug] No data message hidden"
                    );
                }

                // Destroy existing chart safely
                if (
                    window.advancedMortalityChart &&
                    typeof window.advancedMortalityChart.destroy === "function"
                ) {
                    console.log(
                        "[Mortality Chart Debug] Destroying existing chart"
                    );
                    window.advancedMortalityChart.destroy();
                    window.advancedMortalityChart = null;
                    console.log(
                        "[Mortality Chart Debug] ✅ Existing chart destroyed"
                    );
                }

                // Update chart title
                if (elements.title && chartData.title) {
                    elements.title.textContent = chartData.title;
                    console.log(
                        "[Mortality Chart Debug] Chart title updated:",
                        chartData.title
                    );
                }

                // Prepare chart configuration with safe defaults
                const chartConfig = {
                    type: chartData.type || "line",
                    data: {
                        labels: chartData.labels || [],
                        datasets: chartData.datasets || [],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                        },
                        plugins: {
                            title: {
                                display: false,
                            },
                            legend: {
                                display: true,
                                position: "top",
                            },
                            tooltip: {
                                mode: "index",
                                intersect: false,
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || "";
                                        if (label) {
                                            label += ": ";
                                        }

                                        if (
                                            label.includes("Rate") ||
                                            label.includes("%")
                                        ) {
                                            label += context.parsed.y + "%";
                                        } else {
                                            label +=
                                                context.parsed.y.toLocaleString();
                                        }
                                        return label;
                                    },
                                },
                            },
                        },
                        interaction: {
                            mode: "index",
                            intersect: false,
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: "Values",
                                },
                            },
                        },
                        // Merge any additional options from chartData
                        ...(chartData.options || {}),
                    },
                };

                console.log(
                    "[Mortality Chart Debug] Chart configuration prepared:",
                    {
                        type: chartConfig.type,
                        labelsCount: chartConfig.data.labels.length,
                        datasetsCount: chartConfig.data.datasets.length,
                        hasOptions: !!chartConfig.options,
                    }
                );

                // Create chart context
                console.log(
                    "[Mortality Chart Debug] Creating chart context..."
                );
                const ctx = elements.canvas.getContext("2d");

                if (!ctx) {
                    console.error(
                        "[Mortality Chart Debug] ❌ Failed to get 2D context from canvas"
                    );
                    return;
                }
                console.log("[Mortality Chart Debug] ✅ Chart context created");

                // Create the chart
                console.log(
                    "[Mortality Chart Debug] Creating Chart.js instance..."
                );
                window.advancedMortalityChart = new Chart(ctx, chartConfig);

                console.log(
                    "[Mortality Chart Debug] ✅ Chart created successfully!"
                );
                console.log("[Mortality Chart Debug] Chart instance:", {
                    id: window.advancedMortalityChart.id,
                    type: window.advancedMortalityChart.config.type,
                    canvas: window.advancedMortalityChart.canvas.id,
                    isInitialized: !!window.advancedMortalityChart.data,
                });

                // Force chart update and render
                setTimeout(() => {
                    if (window.advancedMortalityChart) {
                        try {
                            window.advancedMortalityChart.update("none");
                            window.advancedMortalityChart.render();
                            console.log(
                                "[Mortality Chart Debug] ✅ Chart forced update and render completed"
                            );
                        } catch (renderError) {
                            console.error(
                                "[Mortality Chart Debug] ❌ Error during chart render:",
                                renderError
                            );
                        }
                    }
                }, 100);
            })
            .catch(function (error) {
                console.error(
                    "[Mortality Chart Debug] ❌ Error fetching chart data:",
                    error
                );

                // Show no data state on error
                if (elements.container) {
                    elements.container.style.display = "none";
                    console.log(
                        "[Mortality Chart Debug] Chart container hidden due to error"
                    );
                }
                if (elements.noData) {
                    elements.noData.classList.remove("d-none");
                    console.log(
                        "[Mortality Chart Debug] No data message shown due to error"
                    );
                }
            });
    } catch (error) {
        console.error(
            "[Mortality Chart Debug] ❌ ERROR during chart creation:",
            error
        );
        console.error("[Mortality Chart Debug] Error stack:", error.stack);

        // Show no data state on error
        if (elements.container) {
            elements.container.style.display = "none";
            console.log(
                "[Mortality Chart Debug] Chart container hidden due to error"
            );
        }
        if (elements.noData) {
            elements.noData.classList.remove("d-none");
            console.log(
                "[Mortality Chart Debug] No data message shown due to error"
            );
        }
    }

    console.log(
        "[Mortality Chart Debug] ========== INITIALIZATION END =========="
    );
}

// Check required DOM elements for mortality chart
function checkMortalityChartElements() {
    const elements = {
        canvas: document.getElementById("advancedMortalityChart"),
        container: document.getElementById("mortalityChartContainer"),
        noData: document.getElementById("mortalityChartNoData"),
        title: document.getElementById("mortalityChartTitle"),
        selector: document.getElementById("mortalityChartType"),
    };

    console.log("[Mortality Chart Debug] Required elements check:", {
        canvas: !!elements.canvas && elements.canvas.tagName === "CANVAS",
        container: !!elements.container,
        noData: !!elements.noData,
        title: !!elements.title,
        selector: !!elements.selector,
    });

    return elements;
}

// Global debug functions
window.debugMortalityChart = function () {
    console.log(
        "[Mortality Chart Debug] ========== CHART STATE DEBUG =========="
    );

    const debugInfo = {
        chartJs: {
            available: typeof Chart !== "undefined",
            version: typeof Chart !== "undefined" ? Chart.version : "N/A",
        },
        elements: {
            canvas: !!document.getElementById("advancedMortalityChart"),
            container: !!document.getElementById("mortalityChartContainer"),
            noData: !!document.getElementById("mortalityChartNoData"),
            title: !!document.getElementById("mortalityChartTitle"),
            selector: !!document.getElementById("mortalityChartType"),
        },
        chartInstance: {
            exists: !!window.advancedMortalityChart,
            id: window.advancedMortalityChart?.id,
            type: window.advancedMortalityChart?.config?.type,
        },
        tab: {
            mortalityTabActive: document
                .querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]')
                ?.classList.contains("active"),
        },
    };

    console.log("[Mortality Chart Debug] Debug info:", debugInfo);

    return debugInfo;
};

window.forceInitializeMortalityChart = function () {
    console.log("[Mortality Chart Debug] FORCE INITIALIZATION REQUESTED");

    // Wait for a moment to ensure DOM is ready
    setTimeout(() => {
        console.log("[Mortality Chart Debug] Starting forced initialization");

        // Ensure we're on mortality tab
        const mortalityTab = document.querySelector(
            '.nav-link[wire\\:click\\.prevent*="mortality"]'
        );
        if (mortalityTab && !mortalityTab.classList.contains("active")) {
            console.log(
                "[Mortality Chart Debug] Switching to mortality tab first"
            );
            mortalityTab.click();

            // Wait for tab switch then initialize
            setTimeout(() => {
                initializeAdvancedMortalityChart();
            }, 500);
        } else {
            initializeAdvancedMortalityChart();
        }
    }, 100);
};
