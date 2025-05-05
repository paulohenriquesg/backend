<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MergeChunks implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected File $file,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $completedStatusId = Status::where('name', Status::COMPLETED)->first()->id;

        if ($this->file->status_id === $completedStatusId) {
            Log::debug('File already completed', [
                'file_id' => $this->file->id,
                'command' => 'MergeChunks',
            ]);

            return;
        }

        if ($this->file->uploads()->where('status_id', '!=', $completedStatusId)->exists()) {
            return;
        }

        $destinationPathOnDisk = $this->file->getDestinationPathOnDisk();
        $destinationPathNameOnDisk = $this->file->getDestinationPathNameOnDisk();

        if (! Storage::disk('storage')->exists($destinationPathOnDisk)) {
            $result = Storage::disk('storage')->makeDirectory($destinationPathOnDisk);

            if (! $result) {
                Log::error('Could not create directory for in storage', [
                    'path' => $destinationPathOnDisk,
                    'command' => 'MergeChunks',
                ]);

                throw new \RuntimeException('Could not create directory for in storage');
            }
        }

        $destinationPath = Storage::disk('storage')->path($destinationPathNameOnDisk);

        Log::debug('Destination file', [
            'path' => $destinationPath,
            'file_id' => $this->file->id,
            'command' => 'MergeChunks',
        ]);

        $destinationStream = fopen($destinationPath, 'wb');

        if (! $destinationStream) {
            Log::error('Could not open destination file for writing', [
                'path' => $destinationPath,
                'command' => 'MergeChunks',
            ]);

            throw new \RuntimeException('Could not open destination file for writing');
        }

        try {
            $uploads = $this->file->uploads()->orderBy('number')->get();

            if ($uploads->isEmpty()) {
                Log::error('No uploads found for file', [
                    'file_id' => $this->file->id,
                    'command' => 'MergeChunks',
                ]);

                throw new \RuntimeException('No uploads found for file');
            }

            foreach ($uploads as $upload) {
                $chunkPath = sprintf('%s/%s', $this->file->id, $upload->id.'.chunk');

                Log::debug('Merging chunk', [
                    'path' => Storage::disk('chunk_uploads')->path($chunkPath),
                    'file_id' => $this->file->id,
                    'command' => 'MergeChunks',
                ]);

                $chunkStream = fopen(Storage::disk('chunk_uploads')->path($chunkPath), 'rb');

                if (! $chunkStream) {
                    Log::error('Could not open chunk file', [
                        'path' => Storage::disk('chunk_uploads')->path($chunkPath),
                        'file_id' => $this->file->id,
                        'chunk_id' => $upload->id,
                        'command' => 'MergeChunks',
                    ]);

                    throw new \RuntimeException("Could not open chunk file: {$chunkPath}");
                }

                stream_copy_to_stream($chunkStream, $destinationStream);
                fclose($chunkStream);
            }

            fclose($destinationStream);

            $this->checkChecksum($destinationPath);

            DB::transaction(function () use ($destinationPathNameOnDisk, $completedStatusId) {
                $this->file->path = $destinationPathNameOnDisk;
                $this->file->status_id = $completedStatusId;
                $this->file->save();

                $this->file->uploads()->delete();
            });

            if (! touch(Storage::disk('storage')->path($destinationPathNameOnDisk), $this->file->create_datetime->timestamp)) {
                Log::error('Could change file creation timestamp', [
                    'path' => Storage::disk('storage')->path($destinationPathNameOnDisk),
                    'file_id' => $this->file->id,
                    'command' => 'MergeChunks',
                ]);
            }

            $this->deleteChunksFolder();

        } catch (\RuntimeException $e) {
            if (isset($chunkStream) && is_resource($chunkStream)) {
                fclose($chunkStream);
            }

            if (is_resource($destinationStream)) {
                fclose($destinationStream);
            }

            $this->file->status_id = Status::where('name', Status::FAILED_CHUNKS_MERGE)->first()->id;
            $this->file->save();

            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function checkChecksum(string $destinationPath): void
    {
        $fileChecksum = hash_file('sha256', $destinationPath);

        if ($fileChecksum !== $this->file->checksum) {
            Log::error('File checksum does not match', [
                'expected' => $this->file->checksum,
                'actual' => $fileChecksum,
                'path' => $destinationPath,
                'command' => 'MergeChunks',
            ]);

            $this->file->status_id = Status::where('name', Status::FAILED_CHECKSUM)->first()->id;
            $this->file->save();

            throw new \Exception('File checksum does not match');
        }
    }

    private function deleteChunksFolder(): void
    {
        if (Storage::disk('chunk_uploads')->exists($this->file->id)) {
            Log::debug('Deleting chunks', [
                'path' => Storage::disk('chunk_uploads')->path($this->file->id),
                'file_id' => $this->file->id,
                'command' => 'MergeChunks',
            ]);

            $result = Storage::disk('chunk_uploads')->deleteDirectory($this->file->id);
            if (! $result) {
                Log::error('Could not delete chunks for successfully uploaded file', [
                    'path' => Storage::disk('chunk_uploads')->path($this->file->id),
                    'file_id' => $this->file->id,
                    'command' => 'MergeChunks',
                ]);
            }
        }
    }
}
