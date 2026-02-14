@extends('layouts.app')

@section('title', 'Seleccionar Modo de Batalla')

@section('content')
<div class="container" style="max-width: 960px; margin-top: 30px;">
    {{-- Header --}}
    <div class="text-center mb-5">
        <div class="battle-header-icon">⚔️</div>
        <h1 class="battle-title">CENTRO DE BATALLA</h1>
        <p class="battle-subtitle">Elige tu modo de combate, entrenador</p>
    </div>

    {{-- Mode Cards --}}
    <div class="row g-4 justify-content-center">
        {{-- VS IA --}}
        <div class="col-md-4">
            <div class="mode-card">
                <div class="mode-card-glow glow-blue"></div>
                <div class="mode-icon">🤖</div>
                <h3 class="mode-title">VS IA</h3>
                <p class="mode-desc">Enfréntate a una IA con 3 niveles de dificultad. Demuestra tu estrategia.</p>
                <div class="mode-tags">
                    <span class="mode-tag tag-easy">Fácil</span>
                    <span class="mode-tag tag-medium">Normal</span>
                    <span class="mode-tag tag-hard">Difícil</span>
                </div>
                <a href="{{ route('battle.setup.ai') }}" class="btn-pokemon-retro mode-btn">JUGAR</a>
            </div>
        </div>

        {{-- 2 PLAYERS --}}
        <div class="col-md-4">
            <div class="mode-card">
                <div class="mode-card-glow glow-red"></div>
                <div class="mode-icon">👥</div>
                <h3 class="mode-title">2 JUGADORES</h3>
                <p class="mode-desc">Comparte pantalla y combate contra un amigo. ¡Batalla local!</p>
                <div class="mode-tags">
                    <span class="mode-tag tag-local">Local</span>
                    <span class="mode-tag tag-hotseat">Hotseat</span>
                </div>
                <a href="{{ route('battle.setup.multiplayer') }}" class="btn-pokemon-retro blue mode-btn">JUGAR</a>
            </div>
        </div>

        {{-- QUICK BATTLE --}}
        <div class="col-md-4">
            <div class="mode-card">
                <div class="mode-card-glow glow-yellow"></div>
                <div class="mode-icon">⚡</div>
                <h3 class="mode-title">BATALLA RÁPIDA</h3>
                <p class="mode-desc">Equipos aleatorios para una batalla instantánea. ¡Sin configuración!</p>
                <div class="mode-tags">
                    <span class="mode-tag tag-random">Aleatorio</span>
                    <span class="mode-tag tag-fast">Rápido</span>
                </div>
                <button class="btn-pokemon-retro yellow mode-btn" id="quickBattleBtn">JUGAR</button>
            </div>
        </div>
    </div>

    {{-- Current Team Info --}}
    @php $teamCount = count(session('team', [])); @endphp
    <div class="team-status-card mt-5">
        <div class="team-status-inner">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="team-pokeballs-display">
                        @for($i = 0; $i < 6; $i++) <div class="status-pokeball {{ $i < $teamCount ? 'filled' : '' }}">
                    </div>
                    @endfor
                </div>
                <div>
                    <span class="team-status-label">TU EQUIPO</span>
                    <span class="team-count">{{ $teamCount }}/6 Pokémon</span>
                </div>
            </div>
            @if($teamCount > 0)
            <a href="{{ route('team.index') }}" class="btn-pokemon-retro green"
                style="font-size:11px; padding: 8px 20px 8px 45px;">GESTIONAR</a>
            @else
            <a href="{{ route('pokemon.index') }}" class="btn-pokemon-retro"
                style="font-size:11px; padding: 8px 20px 8px 45px;">AÑADIR</a>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/battle-setup.css') }}">
@endpush

@push('scripts')
<script>
    document.getElementById('quickBattleBtn')?.addEventListener('click', function () {
        @if ($teamCount > 0)
            window.location.href = "{{ route('battle.setup.ai') }}?quick=true";
        @else
        alert('Necesitas al menos un Pokémon en tu equipo para una batalla rápida.');
        window.location.href = "{{ route('pokemon.index') }}";
        @endif
    });
</script>
@endpush