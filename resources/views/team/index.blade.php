@use('App\Helpers\PokemonHelper')
@extends('layouts.app')

@section('title', 'Squad Logistics - Silph Co.')

@section('content')
<div class="silph-squad-container min-vh-100 py-4">
    <!-- Grid Overlay -->
    <div class="silph-grid-overlay"></div>

    <div class="container position-relative z-3">
        <!-- Terminal Header -->
        <div class="squad-header-terminal p-4 mb-5 border border-dark rounded-3 shadow-lg">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="silph-logo-small"></div>
                        <h1 class="display-6 fw-bold m-0"
                            style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
                            SILPH_CO // <span class="text-cyan">SQUAD_LOGISTICS</span>
                        </h1>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="occupancy-meter d-flex gap-1">
                            @for($i = 0; $i < 6; $i++) <div class="meter-block {{ $i < $teamCount ? 'active' : '' }}">
                        </div>
                        @endfor
                    </div>
                    <span class="digital-text small {{ $teamCount >= 6 ? 'text-danger' : 'text-warning' }}">
                        UNIT_CAPACITY: {{ $teamCount }}/06
                    </span>
                </div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <a href="{{ route('pokemon.index') }}" class="btn-pokemon-retro blue">
                        <i class="fas fa-plus"></i> ADD_UNIT
                    </a>
                    @if($teamCount > 0)
                    <form action="{{ route('team.clear') }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-pokemon-retro"
                            onclick="return confirm('¿CONFIRMAR PURGA DE ESCUADRÓN?')">
                            <i class="fas fa-trash"></i> PURGE_ALL
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Messages -->
    @if(session('success') || session('error'))
    <div
        class="terminal-alert mb-4 border-start border-4 {{ session('success') ? 'border-success' : 'border-danger' }}">
        <div class="p-3 bg-dark">
            <span class="digital-text">{{ session('success') ?? session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- Biometric Containment Units (Squad Grid) -->
    <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-3">
        @php $teamArray = array_values($team); @endphp
        @for($i = 0; $i < 6; $i++) <div class="col">
            @if(isset($teamArray[$i]))
            @php
            $pokemon = $teamArray[$i];
            $primaryType = $pokemon['types'][0];
            $typeColor = PokemonHelper::getTypeColor($primaryType);
            $typeGlow = $typeColor . '33';
            @endphp
            <div class="containment-unit active glass-box h-100"
                style="--unit-color: {{ $typeColor }}; --unit-glow: {{ $typeGlow }};">
                <div class="unit-status">
                    <span class="pulse-dot"></span> BIO_LINK_STABLE
                </div>

                <div class="unit-visual p-4 text-center">
                    <div class="unit-aura"></div>
                    <img src="{{ $pokemon['image'] }}" alt="{{ $pokemon['name'] }}" class="unit-img floating-unit">
                    <div class="unit-id">SLOT_0{{ $i + 1 }}</div>
                </div>

                <div class="unit-info p-3 border-top border-dark">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0 fw-bold name-display">{{ strtoupper($pokemon['name']) }}</h5>
                        <span class="tech-chip-small">#{{ str_pad($pokemon['id'], 3, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        @foreach($pokemon['types'] as $type)
                        <span class="type-mini-badge" style="--t-color: {{ PokemonHelper::getTypeColor($type) }}">
                            {{ strtoupper($type) }}
                        </span>
                        @endforeach
                    </div>

                    <!-- Stat Meters -->
                    <div class="unit-meters">
                        <div class="mini-stat">
                            <div class="d-flex justify-content-between x-small opacity-75">
                                <span>HP_VAL</span>
                                <span>{{ $pokemon['stats']['hp'] }}</span>
                            </div>
                            <div class="mini-bar">
                                <div class="fill" style="width: {{ min(100, ($pokemon['stats']['hp']/255)*100) }}%">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="unit-actions mt-3 d-flex gap-2">
                        <a href="{{ route('pokemon.show', ['id' => $pokemon['id']]) }}"
                            class="btn-tech-outline flex-grow-1">
                            <i class="fas fa-microscope"></i> ANALYZE
                        </a>
                        <form action="{{ route('team.remove', $pokemon['id']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-pokemon-retro red btn-sm px-3" title="REMOVE_UNIT">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @else
            <div
                class="containment-unit empty glass-box h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center">
                <div class="empty-scanner"></div>
                <div class="empty-icon mb-3 opacity-25">
                    <i class="fas fa-user-slash fa-3x"></i>
                </div>
                <div class="digital-text small opacity-50 mb-3">MISSING_COMMANDER_UNIT</div>
                <a href="{{ route('pokemon.index') }}" class="btn-tech-outline btn-sm">
                    <i class="fas fa-search-plus"></i> LOCATE_UNIT
                </a>
                <div class="unit-id opacity-25">SLOT_0{{ $i + 1 }}</div>
            </div>
            @endif
    </div>
    @endfor
</div>

@if($teamCount > 0)
<!-- Fleet Sync Analysis -->
<div class="row mt-5">
    <div class="col-lg-6">
        <div class="analysis-panel glass-box p-4 h-100">
            <div class="silph-section-header mb-4">
                <span class="header-line"></span> SYNC_AGGREGATE_ANALYSIS
            </div>
            @php
            $totalHP = 0;
            $totalAttack = 0;
            $totalDefense = 0;
            foreach($team as $p) {
            $totalHP += $p['stats']['hp'] ?? 0;
            $totalAttack += $p['stats']['attack'] ?? 0;
            $totalDefense += $p['stats']['defense'] ?? 0;
            }
            $avgHP = $teamCount > 0 ? round($totalHP / $teamCount) : 0;
            $avgAtk = $teamCount > 0 ? round($totalAttack / $teamCount) : 0;
            $avgDef = $teamCount > 0 ? round($totalDefense / $teamCount) : 0;
            @endphp

            <div class="stat-analysis-row mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="digital-text small">HP_AVG_FLEET</span>
                    <span class="fw-bold text-success">{{ $avgHP }}</span>
                </div>
                <div class="liquid-meter hp">
                    <div class="liquid-fill" style="width: {{ min(100, ($avgHP/255)*100) }}%"></div>
                </div>
            </div>

            <div class="stat-analysis-row mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="digital-text small">ATK_AVG_FLEET</span>
                    <span class="fw-bold text-danger">{{ $avgAtk }}</span>
                </div>
                <div class="liquid-meter atk">
                    <div class="liquid-fill" style="width: {{ min(100, ($avgAtk/255)*100) }}%"></div>
                </div>
            </div>

            <div class="stat-analysis-row">
                <div class="d-flex justify-content-between mb-2">
                    <span class="digital-text small">DEF_AVG_FLEET</span>
                    <span class="fw-bold text-warning">{{ $avgDef }}</span>
                </div>
                <div class="liquid-meter def">
                    <div class="liquid-fill" style="width: {{ min(100, ($avgDef/255)*100) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="command-deck-panel glass-box p-4 h-100">
            <div class="silph-section-header mb-4">
                <span class="header-line"></span> COMBAT_COMMAND_DECK
            </div>

            @if($teamCount >= 2)
            <div class="d-grid gap-3">
                <a href="{{ route('battle.setup.ai') }}" class="btn-pokemon-retro yellow py-3 border-4">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <i class="fas fa-robot fa-2x"></i>
                        <div class="text-start">
                            <div class="fw-bold">INITIATE_AI_BATTLE</div>
                            <div class="small opacity-75">PROTOCOL: NEURAL_CHALLENGE</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('battle.setup.multiplayer') }}" class="btn-pokemon-retro blue py-3 border-4">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <i class="fas fa-users-rays fa-2x"></i>
                        <div class="text-start">
                            <div class="fw-bold">INITIATE_LOCAL_PVP</div>
                            <div class="small opacity-75">PROTOCOL: HOTSEAT_SYNC</div>
                        </div>
                    </div>
                </a>
            </div>
            @else
            <div class="combat-lock-alert p-4 text-center border border-danger rounded-2">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h5 class="text-danger fw-bold">COMBAT_PROTOCOLS_LOCKED</h5>
                <p class="small opacity-75 mb-0">Minimum unit requirement not met. Deployment requires
                    <strong>02+</strong> active Bio-Links.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
</div>
</div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@800&family=JetBrains+Mono:wght@500;800&display=swap');

    :root {
        --silph-dark: #090b0d;
        --silph-cyan: #00f2ff;
        --silph-red: #cc0000;
        --tech-bg: rgba(25, 28, 35, 0.95);
    }

    .silph-squad-container {
        background-color: var(--silph-dark);
        color: #fff;
        font-family: 'Inter', sans-serif;
        position: relative;
    }

    .silph-grid-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(rgba(0, 242, 255, 0.05) 1px, transparent 1px);
        background-size: 30px 30px;
        pointer-events: none;
    }

    /* Terminal Header */
    .squad-header-terminal {
        background: #1a1c23;
        border-color: rgba(0, 242, 255, 0.2) !important;
        position: relative;
        overflow: hidden;
    }

    .meter-block {
        width: 15px;
        height: 15px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .meter-block.active {
        background: var(--silph-cyan);
        box-shadow: 0 0 10px var(--silph-cyan);
    }

    /* Containment Units */
    .containment-unit {
        background: var(--tech-bg);
        border: 2px solid #000;
        border-top: 5px solid var(--unit-color, #333);
        border-radius: 4px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }

    .containment-unit.active:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 20px var(--unit-glow);
        background: #252832;
    }

    .unit-status {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 8px;
        font-family: 'JetBrains Mono', monospace;
        color: #00ff88;
        background: rgba(0, 255, 136, 0.1);
        padding: 2px 8px;
        border-radius: 100px;
        z-index: 5;
    }

    .unit-visual {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: radial-gradient(circle at center, var(--unit-glow) 0%, transparent 70%);
    }

    .unit-aura {
        position: absolute;
        width: 120px;
        height: 120px;
        border: 2px dashed var(--unit-color);
        border-radius: 50%;
        opacity: 0.2;
        animation: rotate 10s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .unit-img {
        max-height: 120px;
        z-index: 2;
        filter: drop-shadow(0 0 15px var(--unit-glow));
    }

    .unit-id {
        position: absolute;
        bottom: 5px;
        left: 10px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        opacity: 0.3;
    }

    .name-display {
        font-family: 'Orbitron', sans-serif;
        font-size: 16px;
        letter-spacing: 1px;
    }

    .tech-chip-small {
        font-family: 'JetBrains Mono', monospace;
        font-size: 9px;
        background: #000;
        color: var(--silph-cyan);
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid var(--silph-cyan);
    }

    .type-mini-badge {
        font-size: 8px;
        font-weight: 900;
        padding: 2px 10px;
        background: rgba(0, 0, 0, 0.5);
        color: #fff;
        border-left: 3px solid var(--t-color);
        letter-spacing: 1px;
    }

    .mini-bar {
        height: 4px;
        background: #000;
        border-radius: 2px;
        overflow: hidden;
        margin-top: 4px;
    }

    .mini-bar .fill {
        height: 100%;
        background: var(--unit-color);
        box-shadow: 0 0 5px var(--unit-color);
    }

    .btn-tech-outline {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        padding: 6px 15px;
        font-size: 11px;
        font-family: 'JetBrains Mono', monospace;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-tech-outline:hover {
        background: #fff;
        color: #000;
        border-color: #fff;
    }

    /* Empty Unit Styles */
    .containment-unit.empty {
        border-style: dashed;
        border-color: rgba(255, 255, 255, 0.1) !important;
        opacity: 0.8;
    }

    .empty-scanner {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: var(--silph-cyan);
        opacity: 0.2;
        animation: scan 3s infinite;
    }

    @keyframes scan {
        0% {
            top: 0;
        }

        100% {
            top: 100%;
        }
    }

    /* Analysis Panel */
    .analysis-panel,
    .command-deck-panel {
        background: var(--tech-bg);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .silph-section-header {
        font-family: 'Orbitron', sans-serif;
        font-size: 11px;
        color: var(--silph-cyan);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-line {
        width: 30px;
        height: 2px;
        background: var(--silph-cyan);
    }

    .liquid-meter {
        height: 12px;
        background: #000;
        border-radius: 100px;
        overflow: hidden;
        position: relative;
    }

    .liquid-fill {
        height: 100%;
        transition: width 1s ease-out;
    }

    .hp .liquid-fill {
        background: linear-gradient(90deg, #1d4d35, #00ff88);
        box-shadow: 0 0 10px #00ff88;
    }

    .atk .liquid-fill {
        background: linear-gradient(90deg, #531c1c, #ff4500);
        box-shadow: 0 0 10px #ff4500;
    }

    .def .liquid-fill {
        background: linear-gradient(90deg, #4d3f1d, #ffcc00);
        box-shadow: 0 0 10px #ffcc00;
    }

    .floating-unit {
        animation: float 4s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .digital-text {
        font-family: 'JetBrains Mono', monospace;
    }
</style>
@endpush