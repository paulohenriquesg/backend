<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word.'.'.$this->faker->fileExtension(),
            'create_datetime' => $this->faker->dateTimeThisDecade(),
            'checksum' => $this->faker->sha256(),
            'user_id' => User::factory(),
            'status_id' => function () {
                return Status::whereName(Status::IN_PROGRESS)->firstOrFail()->id;
            },
            'path' => null,
        ];
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
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
