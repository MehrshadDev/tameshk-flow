<x-layouts.auth title="ثبت‌نام - {{ config('app.name') }}">
    <x-auth.google-one-tap />
    <x-auth.pending-template-banner />
    <livewire:auth.register />
</x-layouts.auth>
