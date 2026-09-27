<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePetRequest;
use App\Http\Requests\UpdatePetRequest;
use App\Http\Requests\UpdatePetStatusRequest;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{
    /**
     * Display a listing of the pets with filtering and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Pet::query();

        if ($request->filled('species')) {
            $query->where('species', $request->species);
        }

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = (int) $request->get('per_page', 15);
        $pets = $query->latest()->paginate($perPage);

        return PetResource::collection($pets);
    }

    /**
     * Display the specified pet.
     */
    public function show(int $id): PetResource
    {
        $pet = Pet::findOrFail($id);

        return new PetResource($pet);
    }

    /**
     * Store a newly created pet in storage.
     */
    public function store(StorePetRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('pets', 'public');
        }

        unset($data['photo']);

        $pet = Pet::create($data);

        return (new PetResource($pet))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update general details for the specified pet.
     */
    public function update(UpdatePetRequest $request, int $id): PetResource
    {
        $pet = Pet::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($pet->photo_path && Storage::disk('public')->exists($pet->photo_path)) {
                Storage::disk('public')->delete($pet->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('pets', 'public');
        }

        unset($data['photo']);

        $pet->update($data);

        return new PetResource($pet);
    }

    /**
     * Update adoption status for the specified pet.
     */
    public function updateStatus(UpdatePetStatusRequest $request, int $id): PetResource
    {
        $pet = Pet::findOrFail($id);
        $data = $request->validated();

        if ($data['status'] === 'adopted' && empty($data['adoption_date'])) {
            $data['adoption_date'] = now()->toDateString();
        }

        if ($data['status'] !== 'adopted') {
            $data['adopter_name'] = null;
            $data['adoption_date'] = null;
        }

        $pet->update($data);

        return new PetResource($pet);
    }

    /**
     * Remove the specified pet from storage.
     */
    public function destroy(int $id): Response
    {
        $pet = Pet::findOrFail($id);

        if ($pet->photo_path && Storage::disk('public')->exists($pet->photo_path)) {
            Storage::disk('public')->delete($pet->photo_path);
        }

        $pet->delete();

        return response()->noContent();
    }
}
