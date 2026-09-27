<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_pets_catalog_is_public_and_paginated(): void
    {
        Pet::factory()->count(5)->create();

        $response = $this->getJson('/api/pets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'species',
                        'size',
                        'approximate_age',
                        'gender',
                        'photo_url',
                        'description',
                        'vaccines',
                        'status',
                        'rescue_date',
                        'adopter_name',
                        'adoption_date',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_pets_catalog_can_be_filtered_by_species(): void
    {
        Pet::factory()->create(['name' => 'Dog 1', 'species' => 'dog']);
        Pet::factory()->create(['name' => 'Cat 1', 'species' => 'cat']);

        $response = $this->getJson('/api/pets?species=dog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.species', 'dog');
    }

    public function test_pets_catalog_can_be_filtered_by_size(): void
    {
        Pet::factory()->create(['name' => 'Small Dog', 'size' => 'small']);
        Pet::factory()->create(['name' => 'Large Dog', 'size' => 'large']);

        $response = $this->getJson('/api/pets?size=small');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.size', 'small');
    }

    public function test_pets_catalog_can_be_filtered_by_status(): void
    {
        Pet::factory()->create(['name' => 'Available Pet', 'status' => 'available']);
        Pet::factory()->adopted()->create(['name' => 'Adopted Pet']);

        $response = $this->getJson('/api/pets?status=available');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'available');
    }

    public function test_pets_catalog_can_be_searched_by_name(): void
    {
        Pet::factory()->create(['name' => 'Rex Thunder']);
        Pet::factory()->create(['name' => 'Miaou']);

        $response = $this->getJson('/api/pets?search=Thunder');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rex Thunder');
    }

    public function test_pet_details_can_be_viewed_publicly(): void
    {
        $pet = Pet::factory()->create([
            'name' => 'Pipoca',
            'species' => 'dog',
        ]);

        $response = $this->getJson("/api/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pet->id)
            ->assertJsonPath('data.name', 'Pipoca');
    }

    public function test_unauthenticated_user_cannot_create_pet(): void
    {
        $response = $this->postJson('/api/pets', [
            'name' => 'Ghost',
            'species' => 'dog',
            'size' => 'medium',
            'approximate_age' => '2 years',
            'gender' => 'male',
        ]);

        $response->assertStatus(401);
    }

    public function test_agent_cannot_create_new_pet(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($agent)
            ->postJson('/api/pets', [
                'name' => 'Bolinha',
                'species' => 'cat',
                'size' => 'small',
                'approximate_age' => '5 months',
                'gender' => 'female',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_pet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson('/api/pets', [
                'name' => 'Bolinha',
                'species' => 'cat',
                'size' => 'small',
                'approximate_age' => '5 months',
                'gender' => 'female',
                'description' => 'A sweet little kitten',
                'vaccines' => [
                    ['name' => 'FVRCP', 'applied_at' => '2026-03-01'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bolinha')
            ->assertJsonPath('data.species', 'cat')
            ->assertJsonPath('data.vaccines.0.name', 'FVRCP');

        $this->assertDatabaseHas('pets', [
            'name' => 'Bolinha',
            'species' => 'cat',
        ]);
    }

    public function test_store_pet_validates_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson('/api/pets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'species', 'size', 'approximate_age', 'gender']);
    }

    public function test_agent_and_admin_can_update_pet_details(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $pet = Pet::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($agent)
            ->putJson("/api/pets/{$pet->id}", [
                'name' => 'New Name Updated',
                'description' => 'Updated description here.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name Updated')
            ->assertJsonPath('data.description', 'Updated description here.');

        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'name' => 'New Name Updated',
        ]);
    }

    public function test_marking_as_adopted_requires_adopter_name(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $pet = Pet::factory()->create(['status' => 'available']);

        $response = $this->actingAs($agent)
            ->patchJson("/api/pets/{$pet->id}/status", [
                'status' => 'adopted',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['adopter_name']);
    }

    public function test_agent_can_update_status_to_adopted_with_adopter_name(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $pet = Pet::factory()->create(['status' => 'available']);

        $response = $this->actingAs($agent)
            ->patchJson("/api/pets/{$pet->id}/status", [
                'status' => 'adopted',
                'adopter_name' => 'Maria Silva',
                'adoption_date' => '2026-03-25',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'adopted')
            ->assertJsonPath('data.adopter_name', 'Maria Silva')
            ->assertJsonPath('data.adoption_date', '2026-03-25');

        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'status' => 'adopted',
            'adopter_name' => 'Maria Silva',
        ]);
    }

    public function test_agent_cannot_delete_pet(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $pet = Pet::factory()->create();

        $response = $this->actingAs($agent)
            ->deleteJson("/api/pets/{$pet->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('pets', ['id' => $pet->id]);
    }

    public function test_admin_can_delete_pet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pet = Pet::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/pets/{$pet->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('pets', ['id' => $pet->id]);
    }

    public function test_admin_can_upload_photo_and_replace_it(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->create('pet.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($admin)
            ->postJson('/api/pets', [
                'name' => 'Foto Pet',
                'species' => 'dog',
                'size' => 'small',
                'approximate_age' => '1 year',
                'gender' => 'male',
                'photo' => $file,
            ]);

        $response->assertStatus(201);
        $petId = $response->json('data.id');
        $pet = Pet::findOrFail($petId);

        $this->assertNotNull($pet->photo_path);
        Storage::disk('public')->assertExists($pet->photo_path);

        $oldPath = $pet->photo_path;

        // Replace photo
        $newFile = UploadedFile::fake()->create('new_pet.png', 100, 'image/png');
        $updateResponse = $this->actingAs($admin)
            ->putJson("/api/pets/{$petId}", [
                'photo' => $newFile,
            ]);

        $updateResponse->assertStatus(200);
        $pet->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($pet->photo_path);

        // Delete pet and assert photo is deleted
        $deleteResponse = $this->actingAs($admin)->deleteJson("/api/pets/{$petId}");
        $deleteResponse->assertStatus(204);
        Storage::disk('public')->assertMissing($pet->photo_path);
    }
}
