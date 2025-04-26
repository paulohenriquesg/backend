<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="bg-base-200">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login | Photos Nest</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Scripts and Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 flex items-center justify-center">
        <div class="hero">
            <div class="hero-content flex-col lg:flex-row-reverse">
                <div class="text-center lg:text-left lg:ml-8">
                    <h1 class="text-5xl font-bold">Login</h1>
                    <p class="py-6">Access your Photos Nest account to manage your photos and memories safely in the cloud.</p>
                </div>
                <div class="card shrink-0 w-full max-w-sm shadow-2xl bg-base-100">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-error mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.submit') }}">
                            @csrf
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Email</span>
                                </label>
                                <input type="email" name="email" placeholder="email@example.com" class="input input-bordered @error('email') input-error @enderror" value="{{ old('email') }}" required autofocus />
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Password</span>
                                </label>
                                <input type="password" name="password" placeholder="password" class="input input-bordered @error('password') input-error @enderror" required />
                            </div>
                            <input type="hidden" name="device_name" value="{{ $deviceName ?? 'web_browser' }}">
                            <input type="hidden" name="redirect" value="{{ $redirect }}">
                            <div class="form-control mt-6">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        @if (Route::has('register'))
                            <div class="text-center mt-4">
                                <span>Don't have an account? </span>
                                <a href="{{ route('register') }}" class="link link-primary">Register now</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
