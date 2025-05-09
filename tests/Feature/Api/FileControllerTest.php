<?php

namespace Tests\Feature\Api;

use App\Models\File;
use App\Models\Status;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_list_files_unauthenticated(): void
    {
        $response = $this->getJson('/api/files');
        $response->assertForbidden();
    }

    public function test_list_files_authenticated_empty(): void
    {
        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/files');
        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_list_files_authenticated_with_data(): void
    {
        Sanctum::actingAs($this->user);

        File::factory()->count(3)->withUser($this->user)->create();
        File::factory()->count(2)->create();

        $response = $this->getJson('/api/files');

        $response->assertOk()->assertJsonCount(3, 'data');

        $fileIds = collect($response->json('data'))->pluck('id');
        $this->assertDatabaseHas('files', [
            'user_id' => $this->user->id,
        ]);
        $this->assertTrue(File::whereIn('id', $fileIds)->where('user_id', $this->user->id)->count() === $fileIds->count());
    }

    public function test_show_file_unauthenticated(): void
    {
        $file = File::factory()->withUser($this->user)->create();
        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertForbidden();
    }

    public function test_show_file_authenticated_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/files/9999');

        $response->assertNotFound();
    }

    public function test_show_file_authenticated_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $file = File::factory()->withUser($otherUser)->create();
        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertForbidden();
    }

    public function test_show_file_authenticated_owned(): void
    {
        Sanctum::actingAs($this->user);

        $file = File::factory()->withUser($this->user)->create();
        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $file->id)
            ->assertJsonPath('data.name', $file->name);
    }

    public function test_show_file_with_uploads_include(): void
    {
        Sanctum::actingAs($this->user);

        $file = File::factory()->withUser($this->user)->inProgress()->create();
        Upload::factory()->count(3)->withFile($file)->inProgress()->create();

        $response = $this->getJson("/api/files/{$file->id}?include=uploads");

        $response->assertOk()
            ->assertJsonPath('data.id', $file->id)
            ->assertJsonCount(3, 'data.uploads')
            ->assertJsonStructure(['data' => ['uploads' => [['id', 'number', 'status_name', 'created_at', 'updated_at']]]]);
    }

    public function test_search_files_unauthenticated(): void
    {
        $response = $this->postJson('/api/files/search', []);

        $response->assertForbidden();
    }

    public function test_search_files_by_name(): void
    {
        Sanctum::actingAs($this->user);

        $file1 = File::factory()->withUser($this->user)->create(['name' => 'unique_document.pdf']);
        File::factory()->withUser($this->user)->create(['name' => 'another_file.txt']);

        $response = $this->postJson('/api/files/search', [
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'unique_document.pdf'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file1->id);
    }

    public function test_search_files_by_name_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        File::factory()->withUser($otherUser)->create(['name' => 'secret_document.pdf']);

        $response = $this->postJson('/api/files/search', [
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'secret_document.pdf'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_create_file_unauthenticated(): void
    {
        $response = $this->postJson('/api/files', [
            'name' => 'test_file.txt',
            'create_datetime' => now()->toDateTimeString(),
            'checksum' => 'testchecksum',
            'chunks_count' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_create_file_validation_fails(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/files', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'create_datetime', 'checksum', 'chunks_count']);
    }

    public function test_create_file_success(): void
    {
        Sanctum::actingAs($this->user);

        $data = [
            'name' => 'new_image.jpg',
            'create_datetime' => now()->toDateTimeString(),
            'checksum' => 'testchecksum123',
            'chunks_count' => 3,
        ];

        $response = $this->postJson('/api/files?include=uploads', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.checksum', $data['checksum'])
            ->assertJsonPath('data.status_name', Status::IN_PROGRESS)
            ->assertJsonCount($data['chunks_count'], 'data.uploads');

        $this->assertDatabaseHas('files', [
            'name' => $data['name'],
            'user_id' => $this->user->id,
        ]);
        $file = File::whereName($data['name'])->first();
        $this->assertDatabaseCount('uploads', $data['chunks_count']);
        $this->assertEquals($data['chunks_count'], $file->uploads()->count());
    }

    public function test_create_file_name_already_exists_for_user(): void
    {
        Sanctum::actingAs($this->user);

        $existingFile = File::factory()->withUser($this->user)->create();
        $data = [
            'name' => $existingFile->name,
            'create_datetime' => now()->toDateTimeString(),
            'checksum' => 'testchecksum456',
            'chunks_count' => 1,
        ];

        $response = $this->postJson('/api/files', $data);
        $response->assertStatus(422);
    }

    public function test_delete_file_unauthenticated(): void
    {
        $file = File::factory()->withUser($this->user)->create();
        $response = $this->deleteJson("/api/files/{$file->id}");

        $response->assertForbidden();
    }

    public function test_delete_file_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/files/999');

        $response->assertNotFound();
    }

    public function test_delete_file_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $file = File::factory()->withUser($otherUser)->create();
        $response = $this->deleteJson("/api/files/{$file->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('files', ['id' => $file->id]);

    }

    public function test_delete_file_owned(): void
    {
        Sanctum::actingAs($this->user);

        $file = File::factory()->withUser($this->user)->create();
        $response = $this->deleteJson("/api/files/{$file->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $file->id);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_list_files_pagination(): void
    {
        Sanctum::actingAs($this->user);

        File::factory()->count(16)->withUser($this->user)->create();

        $expectedNextPageUrl = config('app.url').'/api/files?page=2';

        $response = $this->getJson('/api/files');
        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('links.next', $expectedNextPageUrl);

        $nextPageUrl = $response->json('links.next');
        $responsePage2 = $this->getJson($nextPageUrl);
        $responsePage2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_search_files_by_create_datetime_greater_than(): void
    {
        Sanctum::actingAs($this->user);

        $date1 = now()->subDays(5)->startOfDay();
        $date2 = now()->subDays(2)->startOfDay();

        File::factory()->withUser($this->user)->create(['create_datetime' => $date1]);
        $file2 = File::factory()->withUser($this->user)->create(['create_datetime' => $date2]);

        $response = $this->postJson('/api/files/search', [
            'filters' => [
                ['field' => 'create_datetime', 'operator' => '>', 'value' => $date1->toDateTimeString()],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file2->id);
    }

    public function test_search_files_by_name_with_like(): void
    {
        Sanctum::actingAs($this->user);

        $file1 = File::factory()->withUser($this->user)->create(['name' => 'report_2024_final.pdf']);
        File::factory()->withUser($this->user)->create(['name' => 'summary_2023.docx']);

        $response = $this->postJson('/api/files/search', [
            'filters' => [
                ['field' => 'name', 'operator' => 'like', 'value' => '%2024%'],
            ],
        ]);
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file1->id);
    }
}
