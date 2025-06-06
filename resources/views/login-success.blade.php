<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="bg-base-200">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login Successful | Photos Nest</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 flex items-center justify-center">
        <div class="hero">
            <div class="hero-content text-center">
                <div class="max-w-md">
                    <h1 class="text-5xl font-bold">Login Successful!</h1>
                    <p class="py-6">You have successfully logged in. You will be redirected shortly.</p>
                    <span class="loading loading-spinner loading-lg"></span>
                </div>
            </div>
        </div>

        <script>
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
            }

            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            document.addEventListener('DOMContentLoaded', (event) => {
                const redirectUrl = getQueryParam('redirect');
                const token = getCookie('token');

                if (redirectUrl && token) {
                    const finalUrl = `${redirectUrl}?token=${encodeURIComponent(token)}`;
                    setTimeout(() => {
                        window.location.href = finalUrl;
                    }, 1500);
                } else {
                    console.error('Redirect URL or token cookie not found.');
                }
            });
        </script>
    </body>
</html>
