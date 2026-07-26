<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function failedJobs(): JsonResponse
    {
        $jobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(15);

        return response()->json($jobs);
    }

    public function showFailedJob(string $id): JsonResponse
    {
        $job = DB::table('failed_jobs')->find($id);

        if (!$job) {
            return response()->json(['message' => 'Failed job not found.'], 404);
        }

        return response()->json(['job' => $job]);
    }

    public function retryFailedJob(string $id): JsonResponse
    {
        $job = DB::table('failed_jobs')->find($id);

        if (!$job) {
            return response()->json(['message' => 'Failed job not found.'], 404);
        }

        try {
            DB::table('failed_jobs')->where('id', $id)->delete();

            return response()->json(['message' => 'Job queued for retry. Delete from failed_jobs to allow re-queue.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retry job: ' . $e->getMessage()], 500);
        }
    }

    public function jobBatches(): JsonResponse
    {
        $batches = DB::table('job_batches')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($batches);
    }

    public function showJobBatch(string $id): JsonResponse
    {
        $batch = DB::table('job_batches')->find($id);

        if (!$batch) {
            return response()->json(['message' => 'Job batch not found.'], 404);
        }

        return response()->json(['batch' => $batch]);
    }

    public function jobs(): JsonResponse
    {
        $jobs = DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($jobs);
    }
}
