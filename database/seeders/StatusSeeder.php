<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fileStatuses = [
            Status::IN_PROGRESS,
            Status::COMPLETED,
            Status::FAILED_CHECKSUM,
            Status::FAILED_CHUNKS_MERGE,
        ];

        Status::whereNotIn('name', $fileStatuses)->delete();

        foreach ($fileStatuses as $status) {
            Status::updateOrCreate(['name' => $status], ['name' => $status]);
        }
    }
}
