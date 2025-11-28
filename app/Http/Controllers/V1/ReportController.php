<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class ReportController extends Controller
{
    /**
     * Store a newly created report in storage.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ip = $request->ip();
        $reportableType = $validated['reportable_type'];
        $reportableId = $validated['reportable_id'];

        $rateLimitKey = "report:ip:{$ip}:{$reportableType}:{$reportableId}";

        if (! RateLimiter::attempt(
            $rateLimitKey,
            1,
            function () {
                // No-op: Only for rate limiting
            },
            60 // 1 minute in seconds
        )) {
            return response()->json([
                'message' => 'You can only report this item once per minute. Please try again later.',
            ], 429);
        }

        $existingReport = Report::where('user_id', $request->user()?->id)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $reportableId)
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this item.',
            ], 422);
        }

        $report = Report::create([
            'user_id' => $request->user()?->id ?? null,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully.',
            'data' => [
                'id' => $report->id,
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'reason' => $report->reason,
                'created_at' => $report->created_at,
            ],
        ], 201);
    }
}
