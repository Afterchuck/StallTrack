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
    'm-0 min-h-screen bg-white font-[Arial,Helvetica,sans-serif] text-[#191919]',
    'flex items-center justify-center px-[18px] py-8 max-[520px]:items-start max-[520px]:px-4 max-[520px]:py-6' => ($title ?? '') !== 'Vendor registration',
    'block p-0' => ($title ?? '') === 'Vendor registration',
])>
    {{ $slot }}
</body>
</html>