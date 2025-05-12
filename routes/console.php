<?php

use App\Console\Commands\FileEntityJanitorCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FileEntityJanitorCommand::class)->daily();
