<?php

namespace App\Http\Controllers\Api;

use App\Models\File;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class FileController extends Controller
{
    protected $model = File::class;

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return parent::buildIndexFetchQuery($request, $requestedRelations)
            ->where('user_id', $this->resolveUser()->getAuthIdentifier());
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        DB::transaction(function () use ($request, $entity, $attributes) {
            $inProgressStatusId = Status::whereName(Status::IN_PROGRESS)->first()->id;

            /**
             * @var File $entity
             */
            $entity->fill($attributes);
            $entity->user_id = $this->resolveUser()->getAuthIdentifier();
            $entity->status_id = $inProgressStatusId;

            $entity->save();

            for ($i = 0; $i < $request->get('chunks_count', 1); $i++) {
                $entity->uploads()->create([
                    'number' => $i + 1,
                    'file_id' => $entity->id,
                    'status_id' => $inProgressStatusId,
                    'path' => config('app.chunks_upload_folder_path')."/$entity->id",
                ]);
            }
        });
    }

    // todo: add proper upload status to a response when querying with relations
    public function alwaysIncludes(): array
    {
        return [
            'uploads',
        ];
    }

    public function maxLimit(): ?int
    {
        return 100;
    }
}
