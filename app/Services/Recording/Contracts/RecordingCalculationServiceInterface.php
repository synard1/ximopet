<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\RecordingData;
use App\Services\Recording\DTOs\ProcessingResult;
use Carbon\Carbon;

/**
 * RecordingCalculationServiceInterface
 * 
 * Contract for recording calculation service that handles all mathematical operations,
 * performance metrics, analytics, and business calculations.
 */
interface RecordingCalculationServiceInterface
{
    /**
     * Calculate performance metrics for a recording
     */
    public function calculatePerformanceMetrics(RecordingData $recordingData): ProcessingResult;

    /**
     * Calculate Feed Conversion Ratio (FCR)
     */
    public function calculateFeedConversionRatio(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate Index Performance (IP)
     */
    public function calculateIndexPerformance(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate Average Daily Gain (ADG)
     */
    public function calculateAverageDailyGain(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate Liveability percentage
     */
    public function calculateLiveability(int $currentPopulation, int $initialPopulation): int;

    /**
     * Calculate cost analysis
     */
    public function calculateCostAnalysis(RecordingData $recordingData): ProcessingResult;

    /**
     * Calculate feed costs
     */
    public function calculateFeedCosts(string $livestockId, array $feedUsages, Carbon $date): ProcessingResult;

    /**
     * Calculate supply costs
     */
    public function calculateSupplyCosts(string $livestockId, array $supplyUsages, Carbon $date): ProcessingResult;

    /**
     * Calculate total operational costs
     */
    public function calculateTotalOperationalCosts(RecordingData $recordingData): ProcessingResult;

    /**
     * Calculate profitability metrics
     */
    public function calculateProfitabilityMetrics(RecordingData $recordingData): ProcessingResult;

    /**
     * Calculate weight gain progression
     */
    public function calculateWeightGainProgression(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate population dynamics
     */
    public function calculatePopulationDynamics(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate mortality rate
     */
    public function calculateMortalityRate(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate culling rate
     */
    public function calculateCullingRate(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate feed efficiency
     */
    public function calculateFeedEfficiency(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate growth rate
     */
    public function calculateGrowthRate(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate production efficiency
     */
    public function calculateProductionEfficiency(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate cost per kg of meat
     */
    public function calculateCostPerKg(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate return on investment
     */
    public function calculateReturnOnInvestment(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate break-even analysis
     */
    public function calculateBreakEvenAnalysis(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate seasonal performance trends
     */
    public function calculateSeasonalTrends(string $livestockId, int $year): ProcessingResult;

    /**
     * Calculate benchmarking metrics
     */
    public function calculateBenchmarkingMetrics(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate resource utilization
     */
    public function calculateResourceUtilization(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate waste metrics
     */
    public function calculateWasteMetrics(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate sustainability metrics
     */
    public function calculateSustainabilityMetrics(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate predictive analytics
     */
    public function calculatePredictiveAnalytics(string $livestockId, Carbon $date, int $forecastDays = 30): ProcessingResult;

    /**
     * Calculate risk assessment
     */
    public function calculateRiskAssessment(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate optimization recommendations
     */
    public function calculateOptimizationRecommendations(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate comparative analysis
     */
    public function calculateComparativeAnalysis(array $livestockIds, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate trend analysis
     */
    public function calculateTrendAnalysis(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate variance analysis
     */
    public function calculateVarianceAnalysis(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate correlation analysis
     */
    public function calculateCorrelationAnalysis(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate statistical summaries
     */
    public function calculateStatisticalSummaries(string $livestockId, Carbon $startDate, Carbon $endDate): ProcessingResult;

    /**
     * Calculate performance scorecards
     */
    public function calculatePerformanceScorecard(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate alerting thresholds
     */
    public function calculateAlertingThresholds(string $livestockId, Carbon $date): ProcessingResult;

    /**
     * Calculate custom metrics
     */
    public function calculateCustomMetrics(string $livestockId, array $customFormulas, Carbon $date): ProcessingResult;
}
