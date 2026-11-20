<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('etms.brand_name', 'Ecohydrotech Solution') }}</title>
    @vite(['resources/css/app.css', 'resources/js/inertia.ts'])
    @inertiaHead
</head>
<body class="min-h-screen bg-white font-classic text-[#272d38]">
    @inertia
</body>
</html>
