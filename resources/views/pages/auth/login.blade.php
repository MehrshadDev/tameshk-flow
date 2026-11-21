<x-layouts.auth title="ورود - {{ config('app.name') }}">
    <x-auth.google-one-tap />
    <x-auth.pending-template-banner />
    <livewire:auth.login />
</x-layouts.auth>
