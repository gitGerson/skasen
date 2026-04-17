@if (request()->is('login') || request()->is('two-factor-authentication'))
    <style>
        .fi-simple-header .fi-logo {
            height: auto !important;
            display: block !important;
        }
        .fi-simple-header {
            gap: 0 rem !important;
        }
    </style>
    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
        <img src="{{ asset('logo.png') }}" alt="Suara Skasen" style="height: 5rem; width: auto;" />
        <span style="font-size: 1.25rem; font-weight: 700; color: #ffffff;">SUARA SKASEN</span>
    </div>
@else
    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <img src="{{ asset('logo.png') }}" alt="Suara Skasen" style="height: 2rem; width: auto;" />
        <span style="font-size: 1rem; font-weight: 700; color: #ffffff;">SUARA SKASEN</span>
    </div>
@endif
