<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * GET /api/health
     * Health check untuk dicek service lain
     */
    public function check(): JsonResponse
    {
        $dbStatus = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        return response()->json([
            'service'   => 'user-service',
            'status'    => 'ok',
            'timestamp' => now()->toISOString(),
            'checks'    => [
                'database' => $dbStatus,
            ],
        ]);
    }
}
