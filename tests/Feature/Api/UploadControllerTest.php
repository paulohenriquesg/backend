<?php

namespace Tests\Feature\Api;

use App\Jobs\MergeChunks;
use App\Models\File;
use App\Models\Status;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected File $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->file = File::factory()->withUser($this->user)->inProgress()->create();

        Storage::fake('chunk_uploads');
        $this->storageFake = Storage::fake('storage');

        Bus::fake();
        Queue::fake();
    }

    public function test_list_uploads_unauthenticated(): void
    {
        $response = $this->getJson("/api/files/{$this->file->id}/uploads");
        $response->assertForbidden();
    }

    public function test_list_uploads_file_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/files/999/uploads');

        $response->assertNotFound();
    }

    public function test_list_uploads_file_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherFile = File::factory()->withUser($otherUser)->create();
        $response = $this->getJson("/api/files/{$otherFile->id}/uploads");

        $response->assertForbidden();
    }

    public function test_list_uploads_empty(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/files/{$this->file->id}/uploads");

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_uploads_with_data(): void
    {
        Sanctum::actingAs($this->user);

        Upload::factory()->count(3)->withFile($this->file)->inProgress()->create();
        $response = $this->getJson("/api/files/{$this->file->id}/uploads");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.status_name', Status::IN_PROGRESS);
    }

    public function test_show_upload_unauthenticated(): void
    {
        $upload = Upload::factory()->withFile($this->file)->create();
        $response = $this->getJson("/api/files/{$this->file->id}/uploads/{$upload->id}");

        $response->assertForbidden();
    }

    public function test_show_upload_file_not_found(): void
    {
        $upload = Upload::factory()->withFile($this->file)->create();
        $response = $this->getJson("/api/files/999/uploads/{$upload->id}");
        $response->assertNotFound();
    }

    public function test_show_upload_not_found(): void
    {
        $response = $this->getJson("/api/files/{$this->file->id}/uploads/999");
        $response->assertNotFound();
    }

    public function test_show_upload_file_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherFile = File::factory()->withUser($otherUser)->create();
        $upload = Upload::factory()->withFile($otherFile)->create();
        $response = $this->getJson("/api/files/{$otherFile->id}/uploads/{$upload->id}");

        $response->assertForbidden();
    }

    public function test_show_upload_owned(): void
    {
        Sanctum::actingAs($this->user);

        $upload = Upload::factory()->withFile($this->file)->create();
        $response = $this->getJson("/api/files/{$this->file->id}/uploads/{$upload->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $upload->id)
            ->assertJsonPath('data.number', $upload->number);
    }

    public function test_update_upload_unauthenticated(): void
    {
        $upload = Upload::factory()->withFile($this->file)->inProgress()->create();
        $response = $this->patchJson("/api/files/{$this->file->id}/uploads/{$upload->id}", []);

        $response->assertForbidden();
    }

    public function test_update_upload_file_not_found(): void
    {
        $upload = Upload::factory()->withFile($this->file)->inProgress()->create();
        $response = $this->patchJson("/api/files/999/uploads/{$upload->id}", []);

        $response->assertNotFound();
    }

    public function test_update_upload_not_found(): void
    {
        $response = $this->patchJson("/api/files/{$this->file->id}/uploads/999", []);

        $response->assertNotFound();
    }

    public function test_update_upload_file_not_owned(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherFile = File::factory()->withUser($otherUser)->create();
        $upload = Upload::factory()->withFile($otherFile)->inProgress()->create();

        $response = $this->patchJson("/api/files/{$otherFile->id}/uploads/{$upload->id}", []);

        $response->assertForbidden();
    }

    public function test_update_upload_invalid_content_type(): void
    {
        Sanctum::actingAs($this->user);

        $upload = Upload::factory()->withFile($this->file)->inProgress()->create();
        $response = $this->patchJson("/api/files/{$this->file->id}/uploads/{$upload->id}", [], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_update_upload_success(): void
    {
        Sanctum::actingAs($this->user);

        $upload = Upload::factory()->withFile($this->file)->inProgress()->create();
        $content = 'test file content';

        $response = $this->call(
            'PATCH',
            "/api/files/{$this->file->id}/uploads/{$upload->id}",
            [], [], [],
            ['CONTENT_TYPE' => 'application/octet-stream'],
            $content
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $upload->id)
            ->assertJsonPath('data.status_name', Status::COMPLETED);

        Storage::disk('chunk_uploads')->assertExists("{$this->file->id}/{$upload->id}.chunk");
        $this->assertEquals($content, Storage::disk('chunk_uploads')->get("{$this->file->id}/{$upload->id}.chunk"));

        $upload->refresh();
        $this->assertEquals(Status::COMPLETED, $upload->status->name);
        Bus::assertDispatchedTimes(MergeChunks::class);
    }

    public function test_update_upload_all_chunks_completed_triggers_merge(): void
    {
        Sanctum::actingAs($this->user);

        $chunksCount = 3;
        $this->file->uploads()->delete();
        $uploads = Upload::factory()
            ->count($chunksCount)
            ->withFile($this->file)
            ->inProgress()
            ->create();

        foreach ($uploads as $upload) {
            $content = "chunk content {$upload->number}";
            $response = $this->call(
                'PATCH',
                "/api/files/{$this->file->id}/uploads/{$upload->id}",
                [], [], [],
                ['CONTENT_TYPE' => 'application/octet-stream'],
                $content
            );
            $response->assertOk();
        }

        foreach ($uploads as $upload) {
            $upload->refresh();
            $this->assertEquals(Status::COMPLETED, $upload->status->name);
        }

        Bus::assertDispatchedTimes(MergeChunks::class, $chunksCount);
    }
}
