<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Workflow\Support\MySqlClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class MySqlOptionsController extends Controller
{
    public function tables(Request $request, Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        try {
            return response()->json([
                'data' => (new MySqlClient($credential))->listTables(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'data'    => [],
                'message' => strip_tags($e->getMessage()),
            ], 422);
        }
    }
}
