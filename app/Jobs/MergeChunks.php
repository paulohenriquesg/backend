<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Status;
use Carbon\Carbon;
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

        if ($this->file->uploads()->where('status_id', '!=', $completedStatusId)->exists()) {
            return;
        }

        $carbonFileDate = Carbon::make($this->file->create_datetime);

        $destinationPathOnDisk = sprintf('%04d/%02d/%02d',
            $carbonFileDate->year,
            $carbonFileDate->month,
            $carbonFileDate->day
        );
        $destinationPathNameOnDisk = sprintf('%s/%s',
            $destinationPathOnDisk,
            $this->file->name,
        );

        if (!Storage::disk('storage')->exists($destinationPathOnDisk)) {
            $result = Storage::disk('storage')->makeDirectory($destinationPathOnDisk);

            if (!$result) {
                Log::error('Could not create directory for in storage', [
                    'path' => $destinationPathOnDisk,
                ]);

                throw new \RuntimeException("Could not create directory for in storage");
            }
        }

        $destinationPath = Storage::disk('storage')->path($destinationPathNameOnDisk);

        $destinationStream = fopen($destinationPath, 'wb');

        if (!$destinationStream) {
            Log::error('Could not open destination file for writing', [
                'path' => $destinationPath,
            ]);

            throw new \RuntimeException("Could not open destination file for writing");
        }

        try {
            $uploads = $this->file->uploads()->orderBy('number')->get();

            foreach ($uploads as $upload) {
                $chunkPath = sprintf('%s/%s', $this->file->id, $upload->id . '.chunk');

                $chunkStream = fopen(Storage::disk('chunk_uploads')->path($chunkPath), 'rb');

                if (!$chunkStream) {
                    Log::error('Could not open chunk file', [
                        'path' => Storage::disk('chunk_uploads')->path($chunkPath),
                    ]);

                    throw new \RuntimeException("Could not open chunk file: {$chunkPath}");
                }

                stream_copy_to_stream($chunkStream, $destinationStream);
                fclose($chunkStream);

                // Delete the chunk after it has been merged
                // Storage::delete($chunkPath);
            }

            fclose($destinationStream);

            $this->checkChecksum($destinationPath);

            DB::transaction(function () use ($destinationPathNameOnDisk, $completedStatusId) {
                $this->file->path = $destinationPathNameOnDisk;
                $this->file->status_id = $completedStatusId;
                $this->file->save();

                $this->file->uploads()->delete();
            });

            $this->deleteChunks();

        } catch (\RuntimeException $e) {
            if (is_resource($destinationStream)) {
                fclose($destinationStream);
            }

            $this->file->status_id = Status::where('name', Status::FAILED_CHUNKS_MERGE)->first()->id;
            $this->file->save();

            throw $e;
        }
    }

    /**
     * @param string $destinationPath
     * @return void
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
            ]);

            $this->file->status_id = Status::where('name', Status::FAILED_CHECKSUM)->first()->id;
            $this->file->save();

            throw new \Exception("File checksum does not match");
        }
    }

    private function deleteChunks()
    {
        if (Storage::disk('chunk_uploads')->exists($this->file->id)) {
            Log::debug('Deleting chunks', [
                'path' => Storage::disk('chunk_uploads')->path($this->file->id),
            ]);

            $result = Storage::disk('chunk_uploads')->delete($this->file->id);
            if (!$result) {
                Log::error('Could not delete chunks for successfully uploaded file', [
                    'path' => Storage::disk('chunk_uploads')->path($this->file->id),
                    'file_id' => $this->file->id,
                ]);
            }
        }
    }
}
