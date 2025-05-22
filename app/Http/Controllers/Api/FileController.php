<?php

namespace App\Http\Controllers\Api;

use App\Jobs\MergeChunks;
use App\Models\File;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
                $fileId = $entity->id;
                $entity->uploads()->create([
                    'number' => $i + 1,
                    'file_id' => $fileId,
                    'status_id' => $inProgressStatusId,
                ]);
            }
        });
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if ($entity->statusName === Status::COMPLETED) {
            throw new BadRequestHttpException('Cannot update completed file');
        }

        $this->performFill($request, $entity, $attributes);
        $entity->save();

        if (isset($attributes['checksum'])) {
            MergeChunks::dispatch($entity);
        }
    }

    public function filterableBy(): array
    {
        return ['name', 'create_datetime', 'created_at', 'updated_at'];
    }

    public function includes(): array
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
