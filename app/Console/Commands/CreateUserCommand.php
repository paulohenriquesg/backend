<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user:create {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user with the given email and password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        try {
            Validator::make(
                [
                    'email' => $email,
                    'password' => $password,
                ],
                [
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:8',
                ]
            )->validate();
        } catch (ValidationException $e) {
            $this->error('Validation failed: '.implode(', ', $e->validator->errors()->all()));

            return;
        }

        User::create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('User created successfully.');
    }
}
