<?php

namespace App\Http\Controllers;

use App\Credentials\AiModelCatalogService;
use App\Credentials\Exceptions\InvalidCredentialException;
use App\Credentials\Registry\CredentialRegistry;
use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Http\Resources\CredentialResource;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CredentialController extends Controller
{
    public function __construct(
        private readonly CredentialRegistry $registry,
        private readonly AiModelCatalogService $modelCatalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Credential::class);
        $q = $request->user()->credentials()->latest();
        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        $credentials = $q->get();
        return response()->json([
            'data' => CredentialResource::collection($credentials),
            'meta' => [
                'types' => $this->registry->definitionsAsArray(),
            ],
        ]);
    }

    public function store(StoreCredentialRequest $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $data = $request->array('data');

        try {
            $this->registry->make($type)->validate($data);
        } catch (InvalidCredentialException $e) {
            throw ValidationException::withMessages([
                'data' => [$e->getMessage()],
            ]);
        }

        $credential = $request->user()->credentials()->create([
            'type' => $type,
            'name' => $request->string('name')->toString(),
            'data' => $data,
        ]);

        return response()->json([
            'data' => new CredentialResource($credential),
        ], 201);
    }

    public function show(Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        return response()->json([
            'data' => new CredentialResource($credential),
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): JsonResponse
    {
        $payload = [];

        if ($request->has('name')) {
            $payload['name'] = $request->string('name')->toString();
        }

        if ($request->has('data')) {
            $data = $request->array('data');

            try {
                $this->registry->make($credential->type)->validate($data);
            } catch (InvalidCredentialException $e) {
                throw ValidationException::withMessages([
                    'data' => [$e->getMessage()],
                ]);
            }

            $payload['data'] = $data;
        }

        $credential->update($payload);

        return response()->json([
            'data' => new CredentialResource($credential->fresh()),
        ]);
    }

    public function destroy(Credential $credential): JsonResponse
    {
        Gate::authorize('delete', $credential);

        $credential->delete();

        return response()->json(null, 204);
    }

    public function models(Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        $apiKey = trim((string) $credential->get('api_key'));

        if ($apiKey === '') {
            return response()->json(['data' => []], 400);
        }

        $forceRefresh = request()->boolean('refresh');

        return response()->json([
            'data' => $this->modelCatalog->listForCredential($credential, $forceRefresh),
        ]);
    }
}
