<?php

namespace App\Http\Controllers\Api;

use App\Jobs\MergeChunks;
use App\Models\File;
use App\Models\Status;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Orion\Http\Controllers\RelationController;
use Orion\Http\Requests\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UploadController extends RelationController
{
    protected $model = File::class;

    protected $relation = 'uploads';

    /**
     * @param  Upload  $entity
     */
    protected function performUpdate(Request $request, Model $parentEntity, Model $entity, array $attributes, array $pivot): void
    {
        if ($request->header('content-type') !== 'application/octet-stream') {
            Log::error('Invalid content type', [
                'content_type' => $request->getContentType(),
                'upload_id' => $entity->id,
            ]);

            throw new HttpException(400, 'Invalid content type');
        }

        $fileContent = $request->getContent();

        if (! Storage::disk('chunk_uploads')->exists($entity->file->id)) {
            $createDirectoryResult = Storage::disk('chunk_uploads')->makeDirectory($entity->file->id);

            if (! $createDirectoryResult) {
                Log::error('Failed to create directory', [
                    'path' => Storage::disk('chunk_uploads')->path($entity->file->id),
                    'upload_id' => $entity->id,
                ]);

                throw new HttpException(500, 'Failed to create directory');
            }
        }

        $filePath = sprintf('%s/%s', $entity->file->id, $entity->id.'.chunk');

        $saveResult = Storage::disk('chunk_uploads')->put(
            $filePath,
            $fileContent
        );

        if (! $saveResult) {
            Log::error('Failed to save file chunk', [
                'path' => Storage::disk('chunk_uploads')->path($filePath),
                'upload_id' => $entity->id,
            ]);

            throw new HttpException(500, 'Failed to save file chunk');
        }

        $entity->status_id = Status::whereName(Status::COMPLETED)->first()->id;
        $entity->save();

        MergeChunks::dispatch($parentEntity);
    }
}
