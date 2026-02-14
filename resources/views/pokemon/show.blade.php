@use('App\Helpers\PokemonHelper')
@extends('layouts.app')

@section('title', $pokemon['name'] . ' - Silph Co. Database')

@section('content')
<div class="silph-co-container min-vh-100">
    <!-- Split Screen Layout -->
    <div class="row g-0 h-100">

        <!-- Left Section (60%): Visual Hub -->
        <div class="col-lg-7 silph-visual-hub position-relative">
            <!-- Dynamic Aura -->
            @php $primaryColor = PokemonHelper::getTypeColor($pokemon['types'][0]); @endphp
            <div class="silph-aura" style="--aura-color: {{ $primaryColor }}"></div>

            <!-- Pokeball Navigation Controls -->
            <div class="silph-nav-controls">
                <a href="{{ route('pokemon.show', ['id' => $pokemon['id'] - 1, 'from_page' => $fromPage]) }}"
                    class="pagination-pokeball {{ $pokemon['id'] <= 1 ? 'disabled invisible' : '' }}"
                    title="PREVIOUS_INDEX" style="width: 65px; height: 65px;">
                    <div class="page-num" style="width: 32px; height: 32px; font-size: 16px;">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                </a>

                <a href="{{ route('pokemon.show', ['id' => $pokemon['id'] + 1, 'from_page' => $fromPage]) }}"
                    class="pagination-pokeball" title="NEXT_INDEX" style="width: 65px; height: 65px;">
                    <div class="page-num" style="width: 32px; height: 32px; font-size: 16px;">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>

            <!-- Silph Top Bar -->
            <div class="silph-header p-4 d-flex justify-content-between align-items-center position-relative z-3">
                <a href="{{ route('pokemon.index', ['page' => $fromPage]) }}" class="btn-pokemon-retro">
                    RETURN_DATABASE
                </a>
                <div class="silph-status-badge">
                    <div class="scanning-beam"></div>
                    <span class="pulse-dot"></span> UNIT_IDENTIFIED
                </div>
            </div>

            <!-- Artwork Display -->
            <div class="silph-artwork-area">
                <div class="silph-grid-overlay"></div>
                <img id="silphPokemonArt" src="{{ $pokemon['image'] }}" class="silph-art-img silph-float"
                    alt="{{ $pokemon['name'] }}">
            </div>

            <!-- Watermark Branding -->
            <div class="silph-branding-watermark">
                <div class="id-text">#{{ str_pad($pokemon['id'], 3, '0', STR_PAD_LEFT) }}</div>
                <div class="name-text">{{ strtoupper($pokemon['name']) }}</div>
            </div>

            <!-- Interaction Module -->
            <!-- Interaction Module -->
            <div
                class="silph-interaction-footer px-4 pb-5 mb-3 d-flex flex-column flex-md-row align-items-center gap-3 gap-md-4 position-relative z-3">
                @if(isset($pokemon['cries']['latest']))
                <button onclick="triggerSilphResonance('{{ $pokemon['cries']['latest'] }}')"
                    class="btn-pokemon-retro yellow" title="RESONANCE_SCAN">
                    <span class="scan-beam"></span>
                    <i class="fas fa-volume-up"></i> RESONANCE_SCAN
                </button>
                @endif

                <div class="silph-team-controls">
                    @if(in_array($pokemon['id'], session('team', [])))
                    <form action="{{ route('team.remove', $pokemon['id']) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-pokemon-retro">
                            DE-SYNC_UNIT
                        </button>
                    </form>
                    @elseif(count(session('team', [])) < 6) <form action="{{ route('team.add', $pokemon['id']) }}"
                        method="POST">
                        @csrf
                        <button type="submit" class="btn-pokemon-retro blue">
                            SYNC_TEAM_6GB
                        </button>
                        </form>
                        @endif
                </div>
            </div>
        </div>

        <!-- Right Section (40%): Data Panel -->
        <div class="col-lg-5 silph-data-panel">
            <div class="p-4 p-xl-5">

                <!-- Entity ID Header -->
                <div class="silph-identity mb-5">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach($pokemon['types'] as $type)
                        <div class="silph-elemental-chip"
                            style="--chip-color: {{ PokemonHelper::getTypeColor($type) }}">
                            <div class="chip-inner">
                                <div class="chip-icon">
                                    <i class="fas fa-bolt-lightning"></i> <!-- Prototype mapping -->
                                </div>
                                <span class="chip-label">{{ strtoupper($type) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <h1 class="silph-name-display">{{ strtoupper($pokemon['name']) }}</h1>
                    <div class="silph-meta-text">SILPH_CO // OS_VERSION_8.0 // ENCRYPTION_HIGH</div>
                </div>

                <!-- Technical Analytics (Stats) -->
                <div class="silph-analytics">
                    <div class="silph-section-header mb-4">
                        <span class="header-line"></span> DATA_METRICS
                    </div>

                    @foreach($pokemon['stats'] as $statName => $statValue)
                    @php
                    $percentage = min(100, ($statValue / 255) * 100);
                    $statColor = match($statName) {
                    'hp' => '#00ff88',
                    'attack' => '#ff4500',
                    'defense' => '#ffcc00',
                    'special-attack' => '#00ddeb',
                    'special-defense' => '#bf00ff',
                    'speed' => '#ff9900',
                    default => '#ffffff'
                    };
                    @endphp
                    <div class="silph-stat-row mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <span class="stat-id">{{ str_replace('-', '_', strtoupper($statName)) }}</span>
                            <span class="stat-count" style="color: {{ $statColor }}">{{ str_pad($statValue, 3, '0',
                                STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="silph-meter">
                            <div class="meter-fill" style="width: 0%; --m-color: {{ $statColor }}"
                                data-width="{{ $percentage }}%">
                                <div class="meter-glint"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Evolution Hierarchy -->
                @if(!empty($pokemon['evolutions']) && count($pokemon['evolutions']) > 1)
                <div class="silph-evolution-path mt-5">
                    <div class="silph-section-header mb-4">
                        <span class="header-line"></span> EVOLUTION_CHAIN_SEQ
                    </div>
                    <div class="d-flex align-items-center gap-4 overflow-auto py-4">
                        @foreach($pokemon['evolutions'] as $evo)
                        <div class="silph-evo-node">
                            <a href="{{ route('pokemon.show', ['id' => $evo['id'], 'from_page' => $fromPage]) }}"
                                class="silph-evo-thumb {{ $evo['id'] == $pokemon['id'] ? 'active' : '' }}">
                                <img src="{{ $evo['image'] }}" alt="{{ $evo['name'] }}">
                                <div class="active-neon"></div>
                            </a>
                            <div class="evo-label">{{ strtoupper($evo['name']) }}</div>
                        </div>
                        @if(!$loop->last)
                        <div class="evo-separator"><i class="fas fa-bracket-square-right"></i></div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Moves Analytics -->
                <div class="silph-moves-module mt-5">
                    <div class="silph-section-header mb-4 justify-content-between">
                        <div><span class="header-line"></span> COMBAT_CAPABILITIES</div>
                        <div id="moves-loading-indicator" class="text-warning small d-none">
                            <i class="fas fa-sync fa-spin"></i> SYNCING_MOVE_DATABASE...
                        </div>
                    </div>

                    @php
                    $levelMoves = [];
                    $techMoves = [];

                    if (!empty($pokemon['moves_detailed'])) {
                    foreach($pokemon['moves_detailed'] as $move) {
                    if ($move['method'] === 'level-up') {
                    $levelMoves[] = $move;
                    } else {
                    $techMoves[] = $move;
                    }
                    }
                    } else {
                    // Fallback for old data: Treat all as tech/unknown
                    foreach($pokemon['move_names'] ?? [] as $name) {
                    $techMoves[] = ['name' => $name, 'level' => 0, 'method' => 'unknown'];
                    }
                    }

                    // Sort tech moves alphabetically
                    usort($techMoves, fn($a, $b) => strcmp($a['name'], $b['name']));
                    @endphp

                    <div class="row">
                        <!-- Level Up Moves -->
                        <div class="col-lg-6 mb-4">
                            <h6 class="text-muted mb-3 font-weight-bold" style="font-family: var(--mont-bold);">
                                NATURAL_LEARNING_SET</h6>
                            <div class="silph-move-table-wrapper">
                                <table class="table table-borderless silph-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 50px;">LVL</th>
                                            <th>MOVE_NAME</th>
                                            <th>TYPE</th>
                                            <th class="text-end">PWR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($levelMoves as $move)
                                        <tr class="silph-move-row" data-move-name="{{ $move['name'] }}">
                                            <td class="text-center text-cyan fw-bold">{{ $move['level'] }}</td>
                                            <td class="fw-bold">{{ ucfirst(str_replace('-', ' ', $move['name'])) }}</td>
                                            <td id="type-{{ $move['name'] }}" class="small-type">...</td>
                                            <td id="power-{{ $move['name'] }}" class="text-end small-pwr">--</td>
                                        </tr>
                                        @endforeach
                                        @if(empty($levelMoves))
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No natural moves data.</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Technical Moves -->
                        <div class="col-lg-6 mb-4">
                            <h6 class="text-muted mb-3 font-weight-bold" style="font-family: var(--mont-bold);">
                                TECHNICAL_&_ASTRAL_SET</h6>
                            <div class="silph-move-table-wrapper">
                                <table class="table table-borderless silph-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 50px;">SRC</th>
                                            <th>MOVE_NAME</th>
                                            <th>TYPE</th>
                                            <th class="text-end">PWR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($techMoves as $move)
                                        <tr class="silph-move-row" data-move-name="{{ $move['name'] }}">
                                            <td class="text-center text-muted small"><i class="fas fa-compact-disc"></i>
                                            </td>
                                            <td class="fw-bold">{{ ucfirst(str_replace('-', ' ', $move['name'])) }}</td>
                                            <td id="type-{{ $move['name'] }}" class="small-type">...</td>
                                            <td id="power-{{ $move['name'] }}" class="text-end small-pwr">--</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pokedex.css') }}">
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Staggered Stat Entry
        const fills = document.querySelectorAll('.meter-fill');
        setTimeout(() => {
            fills.forEach((fill, idx) => {
                setTimeout(() => {
                    fill.style.width = fill.getAttribute('data-width');
                }, idx * 100);
            });
        }, 500);
    });

    function triggerSilphResonance(url) {
        const audio = new Audio(url);
        const art = document.getElementById('silphPokemonArt');

        if (art) {
            art.style.transition = 'all 0.4s var(--pro-easing)';
            art.style.transform = 'scale(1.2) translateY(-20px)';
            art.style.filter = 'drop-shadow(0 0 80px var(--silph-cyan)) brightness(1.3)';

            setTimeout(() => {
                art.style.transform = 'scale(1.1)';
                art.style.filter = 'drop-shadow(0 0 50px rgba(255,255,255,0.2))';
            }, 500)
        }

        audio.play();
    }

    // Async Move Fetching
    const TYPE_COLORS = {
        'normal': '#A8A878', 'fire': '#F08030', 'water': '#6890F0', 'grass': '#78C850',
        'electric': '#F8D030', 'ice': '#98D8D8', 'fighting': '#C03028', 'poison': '#A040A0',
        'ground': '#E0C068', 'flying': '#A890F0', 'psychic': '#F85888', 'bug': '#A8B820',
        'rock': '#B8A038', 'ghost': '#705898', 'dragon': '#7038F8', 'dark': '#705848',
        'steel': '#B8B8D0', 'fairy': '#EE99AC'
    };

    document.addEventListener('DOMContentLoaded', () => {

        const moveRows = document.querySelectorAll('.silph-move-row');
        const loader = document.getElementById('moves-loading-indicator');

        // We will fetch moves in batches to be efficient
        const movesToFetch = [];
        moveRows.forEach(row => {
            movesToFetch.push(row.getAttribute('data-move-name'));
        });

        // Deduplicate
        const uniqueMoves = [...new Set(movesToFetch)];

        if (uniqueMoves.length > 0) {
            loader.classList.remove('d-none');
            fetchMovesBatch(uniqueMoves);
        }

        async function fetchMovesBatch(moves) {
            // Chunking into batches of 20 - bit larger for tables
            const chunkSize = 20;
            for (let i = 0; i < moves.length; i += chunkSize) {
                const chunk = moves.slice(i, i + chunkSize);
                try {
                    const response = await fetch('/api/moves/batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ moves: chunk })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        updateMoveRows(data);
                    }
                } catch (error) {
                    console.error("Failed to fetch moves chunk", error);
                }

                // Small delay to be nice to the server/UI
                await new Promise(r => setTimeout(r, 200));
            }
            loader.classList.add('d-none');
        }

        function updateMoveRows(data) {
            // data is keyed by move name
            for (const [name, moveData] of Object.entries(data)) {
                if (!moveData) continue;

                // Find ALL rows with this move name (duplicates allowed in UI if needed, but here likely unique per table)
                const rows = document.querySelectorAll(`.silph-move-row[data-move-name="${name}"]`);

                rows.forEach(row => {
                    row.classList.add('loaded');
                    // Find cells using querySelector relative to row would be safer if IDs weren't used.
                    // But we used IDs "type-{name}" which is problematic if move appears twice (e.g. in both tables? No, split by method).
                    // Actually, IDs must be unique. If a move is both level up and TM, we might have duplicate IDs.
                    // Let's rely on scoping within the row for cleaner DOM.
                    // But the blade template generated IDs. 
                    // Let's select properly.

                    const typeSpan = row.querySelector('[id^="type-"]');
                    const powerSpan = row.querySelector('[id^="power-"]');

                    if (typeSpan) {
                        typeSpan.textContent = moveData.type.toUpperCase();
                        typeSpan.style.color = TYPE_COLORS[moveData.type] || '#888';
                        typeSpan.style.fontWeight = 'bold';
                    }
                    if (powerSpan) {
                        powerSpan.textContent = moveData.power > 0 ? moveData.power : '-';
                    }
                });
            }
        }
    });
</script>
@endpush