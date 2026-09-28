<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $title ?? 'Public Market' }} · Public Market
    </title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'm-0 min-h-screen font-[Arial,Helvetica,sans-serif] text-[#191919]',
    'flex items-center justify-center bg-[#edf3ef] px-4 py-6 sm:px-6 sm:py-8' => ($title ?? '') !== 'Create an account',
    'block bg-white p-0' => ($title ?? '') === 'Create an account',
])>
    {{ $slot }}
</body>
</html>
