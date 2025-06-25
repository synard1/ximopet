<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TransactionClearService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionClearController extends Controller
{
    protected TransactionClearService $clearService;

    public function __construct(TransactionClearService $clearService)
    {
        $this->clearService = $clearService;

        // Restrict access to super admin only
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole('SuperAdmin')) {
                abort(403, 'Only Super Admin can access this feature.');
            }
            return $next($request);
        });
    }

    /**
     * Show the transaction clear page
     */
    public function index()
    {
        $preview = $this->clearService->getPreviewSummary();

        return view('pages.admin.transaction-clear.index', compact('preview'));
    }

    /**
     * Get preview of what will be cleared
     */
    public function preview(): JsonResponse
    {
        try {
            $preview = $this->clearService->getPreviewSummary();

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get transaction clear preview', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute transaction data clearing
     */
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => 'required|accepted',
            'password' => 'required|string'
        ]);

        // Verify user password for additional security
        if (!Auth::guard('web')->attempt([
            'email' => Auth::user()->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Password verification failed. Please enter your correct password.'
            ], 401);
        }

        try {
            Log::info('Transaction clear initiated by user', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $result = $this->clearService->clearAllTransactionData();

            // Log the operation result
            Log::info('Transaction clear completed', [
                'success' => $result['success'],
                'user_id' => Auth::id(),
                'cleared_records' => $result['cleared_data'] ?? [],
                'restored_livestock_count' => count($result['restored_livestock'] ?? [])
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Transaction clear failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear transaction data: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Show clearing history/log
     */
    public function history()
    {
        // Get clearing history from logs or audit trail
        $clearingHistory = $this->getClearingHistory();

        return view('pages.admin.transaction-clear.history', compact('clearingHistory'));
    }

    /**
     * Get clearing history from audit trail
     */
    private function getClearingHistory(): array
    {
        // This could be implemented with a dedicated table for clearing history
        // For now, we'll return a simple structure
        return [
            'recent_clears' => [],
            'total_clears' => 0
        ];
    }
}
