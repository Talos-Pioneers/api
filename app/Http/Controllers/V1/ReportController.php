<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Store a newly created report in storage.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check if user already reported this item
        $existingReport = Report::where('user_id', $request->user()->id)
            ->where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this item.',
            ], 422);
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'reportable_type' => $validated['reportable_type'],
            'reportable_id' => $validated['reportable_id'],
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
