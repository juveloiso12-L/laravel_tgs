<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'POS Kasir') }} - {{ $title ?? 'Kasir' }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pos/pos.js'])
    
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    @yield('content')
    
    @stack('scripts')
</body>
</html>