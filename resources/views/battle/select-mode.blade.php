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
<style>
    .battle-header-icon {
        font-size: 48px;
        margin-bottom: 8px;
    }

    .battle-title {
        font-family: 'Orbitron', sans-serif;
        font-weight: 800;
        font-size: 28px;
        color: #fff;
        letter-spacing: 3px;
        text-shadow: 0 0 20px rgba(204, 0, 0, 0.3);
    }

    .battle-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #888;
    }

    /* Mode Cards */
    .mode-card {
        position: relative;
        background: #1e2028;
        border: 2px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 30px 24px;
        text-align: center;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .mode-card:hover {
        transform: translateY(-6px);
        border-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }

    .mode-card-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        opacity: 0;
        transition: opacity 0.4s;
        pointer-events: none;
    }

    .mode-card:hover .mode-card-glow {
        opacity: 1;
    }

    .glow-blue {
        background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
    }

    .glow-red {
        background: radial-gradient(circle, rgba(239, 68, 68, 0.08) 0%, transparent 50%);
    }

    .glow-yellow {
        background: radial-gradient(circle, rgba(251, 191, 36, 0.08) 0%, transparent 50%);
    }

    .mode-icon {
        font-size: 56px;
        margin-bottom: 16px;
        position: relative;
        z-index: 2;
    }

    .mode-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 18px;
        letter-spacing: 2px;
        color: #fff;
        margin-bottom: 10px;
        position: relative;
        z-index: 2;
    }

    .mode-desc {
        color: #888;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 16px;
        position: relative;
        z-index: 2;
    }

    .mode-tags {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 20px;
        position: relative;
        z-index: 2;
    }

    .mode-tag {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .tag-easy {
        background: rgba(34, 197, 94, 0.15);
        color: #22C55E;
    }

    .tag-medium {
        background: rgba(251, 191, 36, 0.15);
        color: #FBBF24;
    }

    .tag-hard {
        background: rgba(239, 68, 68, 0.15);
        color: #EF4444;
    }

    .tag-local {
        background: rgba(59, 130, 246, 0.15);
        color: #3B82F6;
    }

    .tag-hotseat {
        background: rgba(168, 85, 247, 0.15);
        color: #A855F7;
    }

    .tag-random {
        background: rgba(251, 191, 36, 0.15);
        color: #FBBF24;
    }

    .tag-fast {
        background: rgba(34, 197, 94, 0.15);
        color: #22C55E;
    }

    .mode-btn {
        position: relative;
        z-index: 2;
        margin-top: auto;
    }

    /* Team Status */
    .team-status-card {
        background: #1e2028;
        border: 2px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        overflow: hidden;
    }

    .team-status-inner {
        padding: 20px 24px;
    }

    .team-pokeballs-display {
        display: flex;
        gap: 6px;
    }

    .status-pokeball {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(to bottom, #555 50%, #444 50%);
        border: 2px solid #333;
        position: relative;
        opacity: 0.3;
        transition: all 0.3s;
    }

    .status-pokeball::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #444;
        border: 1.5px solid #333;
    }

    .status-pokeball.filled {
        background: linear-gradient(to bottom, #ff0000 50%, #fff 50%);
        border-color: #333;
        opacity: 1;
    }

    .status-pokeball.filled::after {
        background: #fff;
    }

    .team-status-label {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 12px;
        letter-spacing: 1.5px;
        color: #888;
        display: block;
    }

    .team-count {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
    }
</style>
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