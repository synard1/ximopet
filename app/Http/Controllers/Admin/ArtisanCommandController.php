<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Recording;
use App\Models\Livestock;
use Carbon\Carbon;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class ArtisanCommandController extends Controller
{
    public function index()
    {
        $commands = $this->getAvailableCommands();
        $recentExecutions = $this->getRecentExecutions();

        return view('admin.artisan-commands.index', compact('commands', 'recentExecutions'));
    }

    public function execute(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'options' => 'array',
            'livestock_id' => 'nullable|string',
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'dry_run' => 'boolean',
            'force' => 'boolean'
        ]);

        $command = $request->input('command');
        $options = $request->input('options', []);
        $livestockId = $request->input('livestock_id');
        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dryRun = $request->input('dry_run', false);
        $force = $request->input('force', false);

        // Build command options
        $commandOptions = [];

        if ($livestockId) {
            $commandOptions['--livestock-id'] = $livestockId;
        }

        if ($date) {
            $commandOptions['--date'] = $date;
        }

        if ($startDate) {
            $commandOptions['--start-date'] = $startDate;
        }

        if ($endDate) {
            $commandOptions['--end-date'] = $endDate;
        }

        if ($dryRun) {
            $commandOptions['--dry-run'] = true;
        }

        if ($force) {
            $commandOptions['--force'] = true;
        }

        // Add specific options based on command
        foreach ($options as $option) {
            $commandOptions['--' . $option] = true;
        }

        try {
            // Use different approach based on command
            if ($command === 'recording:fix-data-integrity') {
                return $this->executeRecordingFixDataIntegrity($commandOptions);
            } else {
                // For other commands, use Process
                return $this->executeCommandViaProcess($command, $commandOptions);
            }
        } catch (\Exception $e) {
            Log::error('Artisan command execution failed', [
                'command' => $command,
                'options' => $commandOptions,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Command execution failed'
            ], 500);
        }
    }

    public function preview(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'livestock_id' => 'nullable|string',
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $command = $request->input('command');
        $livestockId = $request->input('livestock_id');
        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build preview data based on command
        $previewData = $this->getPreviewData($command, $livestockId, $date, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'preview' => $previewData
        ]);
    }

    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'ArtisanCommandController is working',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    private function getAvailableCommands()
    {
        return [
            [
                'name' => 'recording:fix-data-integrity',
                'description' => 'Fix recording data integrity issues (negative stock, zero stock_awal, null depletion)',
                'category' => 'Data Integrity',
                'options' => [
                    'fix-negative-stock' => 'Fix negative stock_akhir values',
                    'fix-zero-stock-awal' => 'Fix zero stock_awal values',
                    'fix-null-depletion' => 'Fix null total_deplesi values'
                ],
                'parameters' => [
                    'livestock_id' => 'Specific livestock ID to fix',
                    'date' => 'Specific date to fix (Y-m-d format)',
                    'start_date' => 'Start date for range (Y-m-d format)',
                    'end_date' => 'End date for range (Y-m-d format)'
                ]
            ],
            [
                'name' => 'recording:sales:fix-data',
                'description' => 'Fix inconsistent data in recording_sales and recording_sale_items tables',
                'category' => 'Data Integrity',
                'options' => [],
                'parameters' => []
            ],
            [
                'name' => 'recording:stock:recalculate',
                'description' => 'Recalculate stock values for recordings',
                'category' => 'Data Integrity',
                'options' => [],
                'parameters' => [
                    'livestock_id' => 'Specific livestock ID',
                    'farm_id' => 'Specific farm ID',
                    'coop_id' => 'Specific coop ID',
                    'start_date' => 'Start date',
                    'end_date' => 'End date'
                ]
            ],
            [
                'name' => 'livestock:fix-recalculate-batch-quantity',
                'description' => 'Fix and recalculate livestock batch quantities',
                'category' => 'Data Integrity',
                'options' => [],
                'parameters' => [
                    'livestock_id' => 'Specific livestock ID',
                    'batch_id' => 'Specific batch ID'
                ]
            ],
            [
                'name' => 'supply:fix-stock-available',
                'description' => 'Recalculate quantity_available for all SupplyStock records',
                'category' => 'Data Integrity',
                'options' => [],
                'parameters' => [
                    'farm_id' => 'Filter by specific farm ID',
                    'supply_id' => 'Filter by specific supply ID'
                ]
            ],
            [
                'name' => 'feed:fix-feedstock-purchase-id',
                'description' => 'Fix FeedStock purchase ID and data issues',
                'category' => 'Data Integrity',
                'options' => [],
                'parameters' => []
            ]
        ];
    }

    private function getPreviewData($command, $livestockId, $date, $startDate, $endDate)
    {
        switch ($command) {
            case 'recording:fix-data-integrity':
                return $this->getRecordingIntegrityPreview($livestockId, $date, $startDate, $endDate);

            case 'recording:sales:fix-data':
                return $this->getRecordingSalesPreview();

            case 'recording:stock:recalculate':
                return $this->getStockRecalculatePreview($livestockId, $startDate, $endDate);

            default:
                return ['message' => 'Preview not available for this command'];
        }
    }

    private function getRecordingIntegrityPreview($livestockId, $date, $startDate, $endDate)
    {
        $query = Recording::query();

        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }

        if ($date) {
            $query->whereDate('tanggal', $date);
        } elseif ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // Find problematic records
        $problematicRecords = $query->where(function ($q) {
            $q->where('stock_akhir', '<', 0)
                ->orWhere('stock_awal', 0)
                ->orWhereNull('total_deplesi');
        })->with('livestock')->get();

        $summary = [
            'total_records' => $problematicRecords->count(),
            'negative_stock' => $problematicRecords->where('stock_akhir', '<', 0)->count(),
            'zero_stock_awal' => $problematicRecords->where('stock_awal', 0)->count(),
            'null_depletion' => $problematicRecords->whereNull('total_deplesi')->count(),
            'sample_records' => $problematicRecords->take(5)->map(function ($record) {
                $issues = [];
                if ($record->stock_akhir < 0) $issues[] = 'Negative stock_akhir';
                if ($record->stock_awal == 0) $issues[] = 'Zero stock_awal';
                if (is_null($record->total_deplesi)) $issues[] = 'Null depletion';

                return [
                    'id' => $record->id,
                    'date' => $record->tanggal->format('Y-m-d'),
                    'livestock_name' => $record->livestock->name ?? 'N/A',
                    'stock_awal' => $record->stock_awal,
                    'stock_akhir' => $record->stock_akhir,
                    'total_deplesi' => $record->total_deplesi ?? 'NULL',
                    'issues' => $issues
                ];
            })
        ];

        return $summary;
    }

    private function getRecordingSalesPreview()
    {
        // This would need to be implemented based on the specific logic
        return ['message' => 'Preview for recording sales fix not implemented yet'];
    }

    private function getStockRecalculatePreview($livestockId, $startDate, $endDate)
    {
        $query = Recording::query();

        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $totalRecords = $query->count();

        return [
            'total_records' => $totalRecords,
            'message' => "Will recalculate stock values for {$totalRecords} recording(s)"
        ];
    }

    private function getRecentExecutions()
    {
        return Cache::get('artisan_command_executions', []);
    }

    private function logExecution($command, $options, $exitCode, $output)
    {
        $execution = [
            'command' => $command,
            'options' => $options,
            'exit_code' => $exitCode,
            'output' => $output,
            'executed_at' => now()->toIso8601String(),
            'user_id' => null
        ];

        $executions = Cache::get('artisan_command_executions', []);
        array_unshift($executions, $execution);

        // Keep only last 10 executions
        $executions = array_slice($executions, 0, 10);

        Cache::put('artisan_command_executions', $executions, 3600); // 1 hour
    }

    private function executeRecordingFixDataIntegrity($options)
    {
        try {
            // Create command instance
            $command = new \App\Console\Commands\FixRecordingDataIntegrity();
            $command->setLaravel(app());

            // Set input/output
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $output = new \Symfony\Component\Console\Output\BufferedOutput();

            // Run command
            $exitCode = $command->run($input, $output);
            $outputContent = $output->fetch();

            // Log execution
            $this->logExecution('recording:fix-data-integrity', $options, $exitCode, $outputContent);

            return response()->json([
                'success' => $exitCode === 0,
                'output' => $outputContent,
                'exit_code' => $exitCode,
                'message' => $exitCode === 0 ? 'Command executed successfully' : 'Command failed'
            ]);
        } catch (\Exception $e) {
            Log::error('Recording fix data integrity command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Command execution failed'
            ], 500);
        }
    }

    private function executeCommandViaProcess($command, $options)
    {
        try {
            // Build command string
            $commandString = 'php artisan ' . $command;

            foreach ($options as $key => $value) {
                if (is_bool($value) && $value) {
                    $commandString .= ' ' . $key;
                } else {
                    $commandString .= ' ' . $key . ' ' . escapeshellarg($value);
                }
            }

            // Execute command using Process
            $process = new \Symfony\Component\Process\Process(explode(' ', $commandString));
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(300); // 5 minutes timeout

            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Combine output and error
            $fullOutput = $output;
            if (!empty($errorOutput)) {
                $fullOutput .= "\nError Output:\n" . $errorOutput;
            }

            // Log execution
            $this->logExecution($command, $options, $exitCode, $fullOutput);

            return response()->json([
                'success' => $exitCode === 0,
                'output' => $fullOutput,
                'exit_code' => $exitCode,
                'message' => $exitCode === 0 ? 'Command executed successfully' : 'Command failed'
            ]);
        } catch (\Exception $e) {
            Log::error('Process command execution failed', [
                'command' => $command,
                'options' => $options,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Command execution failed'
            ], 500);
        }
    }
}
