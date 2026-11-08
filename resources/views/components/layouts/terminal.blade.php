@props([
    'title' => 'Web Terminal | ' . config('app.name'),
])
    <!DOCTYPE html>
<html lang="en" dir="ltr" class="dark">
<head>
    <x-layouts.head :title="$title"/>
</head>
<body class="min-h-dvh overflow-x-hidden bg-[#06080f] font-mono text-slate-100 antialiased selection:bg-primary/30 selection:text-white">

<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-44 -left-44 size-136 rounded-full bg-primary/18 blur-[130px]"></div>
    <div class="absolute top-1/3 -right-44 size-128 rounded-full bg-indigo-500/14 blur-[130px]"></div>
    <div class="absolute -bottom-48 left-1/3 size-120 rounded-full bg-emerald-500/10 blur-[140px]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(#ffffff09_1px,transparent_1px)] [background-size:24px_24px]"></div>
</div>

{{ $slot }}

@livewireScripts
</body>
</html>
