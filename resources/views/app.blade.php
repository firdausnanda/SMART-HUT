<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-name" content="{{ config('app.name', 'SMART-HUT') }}">
    <meta name="description" content="{{ config('app.name', 'SMART-HUT') }} merupakan sistem informasi manajemen terpadu untuk kemudahan pemantauan, pengelolaan data, dan efisiensi operasional.">
    <meta name="keywords" content="smart-hut, kda, sistem informasi, manajemen data, pemantauan, aplikasi web, terintegrasi">
    <meta name="author" content="Firdaus Nanda Christian">

    <title inertia>{{ config('app.name', 'SMART-HUT') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}">

    <!-- Scripts -->
    @routes
    @viteReactRefresh
    @vite('resources/js/app.jsx')
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
