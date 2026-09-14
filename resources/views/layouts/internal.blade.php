<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Internal - Cetak Foto Jogja')</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-100 text-neutral-800 antialiased min-h-screen">
    @yield('content')
</body>
</html>
