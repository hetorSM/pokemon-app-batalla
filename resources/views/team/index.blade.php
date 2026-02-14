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
<link rel="stylesheet" href="{{ asset('css/team.css') }}">
@endpush