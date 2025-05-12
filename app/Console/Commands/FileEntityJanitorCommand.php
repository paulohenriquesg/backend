<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Status;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileEntityJanitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:janitor:file-entity {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale file entities (including uploads) and their associated storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting janitor cleanup process...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in dry-run mode. No changes will be made.');
        }

        $this->cleanupStaleFileEntities($dryRun);
        $this->cleanupStaleUploadEntities($dryRun);

        $this->info('Janitor cleanup process completed.');
    }

    private function cleanupStaleFileEntities(bool $dryRun): void
    {
        $completedStatusId = Status::where('name', Status::COMPLETED)->first()->id;
        $cutoffDate = Carbon::now()->subDay();

        $staleFiles = File::where('status_id', '!=', $completedStatusId)
            ->where('created_at', '<', $cutoffDate)
            ->cursor();

        foreach ($staleFiles as $file) {
            $this->info("Processing file ID: {$file->id}, status: {$file->status_id}, created: {$file->created_at}");
            Log::debug('Processing file', [
                'file_id' => $file->id,
                'status_id' => $file->status_id,
                'created_at' => $file->created_at,
                'command' => 'JanitorCommand',
            ]);

            try {
                if (Storage::disk('chunk_uploads')->exists($file->id)) {
                    $chunkPath = Storage::disk('chunk_uploads')->path($file->id);
                    $this->info("Deleting chunks folder: {$chunkPath}");

                    Log::debug('Found chunks folder', [
                        'file_id' => $file->id,
                        'path' => $chunkPath,
                        'command' => 'JanitorCommand',
                    ]);

                    if (! $dryRun) {
                        Log::debug('Deleting chunks folder', [
                            'file_id' => $file->id,
                            'path' => $chunkPath,
                            'command' => 'JanitorCommand',
                        ]);

                        $result = Storage::disk('chunk_uploads')->deleteDirectory($file->id);
                        if (! $result) {
                            Log::error('Could not delete chunks folder', [
                                'path' => $chunkPath,
                                'file_id' => $file->id,
                                'command' => 'JanitorCommand',
                            ]);

                            continue;
                        }
                    }
                }

                if (! $dryRun) {
                    $file->uploads()->delete();
                    $file->delete();

                    Log::debug('Deleted file entity', [
                        'file_id' => $file->id,
                        'command' => 'JanitorCommand',
                    ]);
                }

                $this->info("Stale file ID: {$file->id} cleaned up successfully.");
            } catch (\Exception $e) {
                $this->error("Error cleaning up file ID: {$file->id}: {$e->getMessage()}");
                Log::error('Stale file entity cleanup error', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                    'command' => 'JanitorCommand',
                ]);
            }
        }

        $this->info("Completed cleaning up {$staleFiles->count()} stale files.");
    }

    private function cleanupStaleUploadEntities(bool $dryRun): void
    {
        $staleUploads = Upload::doesntHave('file')->cursor();

        foreach ($staleUploads as $upload) {
            $this->info("Processing upload ID: {$upload->id}, file ID: {$upload->file_id}");
            Log::debug('Processing upload', [
                'upload_id' => $upload->id,
                'file_id' => $upload->file_id,
                'command' => 'JanitorCommand',
            ]);

            try {
                if (! $dryRun) {
                    $upload->delete();

                    Log::debug('Deleted upload entity', [
                        'upload_id' => $upload->id,
                        'command' => 'JanitorCommand',
                    ]);
                }

                $this->info("Stale upload ID: {$upload->id} cleaned up successfully.");
            } catch (\Exception $e) {
                $this->error("Error cleaning up upload ID: {$upload->id}: {$e->getMessage()}");
                Log::error('Stale upload entity cleanup error', [
                    'upload_id' => $upload->id,
                    'error' => $e->getMessage(),
                    'command' => 'JanitorCommand',
                ]);
            }
        }
    }
}
