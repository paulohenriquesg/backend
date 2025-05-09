<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Status;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->randomNumber(1),
            'file_id' => File::factory(),
            'status_id' => function () {
                return Status::whereName(Status::IN_PROGRESS)->firstOrFail()->id;
            },
        ];
    }

    public function withFile(File $file): static
    {
        return $this->state(fn (array $attributes) => [
            'file_id' => $file->id,
        ]);
    }

    public function withStatus(Status $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => $status->id,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => Status::firstOrCreate(['name' => Status::IN_PROGRESS])->id,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => Status::firstOrCreate(['name' => Status::COMPLETED])->id,
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => Status::firstOrCreate(['name' => Status::FAILED])->id,
            ];
        });
    }
}
