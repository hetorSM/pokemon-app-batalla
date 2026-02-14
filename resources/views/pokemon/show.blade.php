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
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;600;800&display=swap');

    :root {
        --silph-red: #cc0000;
        --silph-dark: #0f1115;
        --silph-cyan: #00f2ff;
        --mont-bold: 'Montserrat', sans-serif;
        --inter-ui: 'Inter', sans-serif;
        --pro-easing: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body {
        background-color: var(--silph-dark);
        color: #fff;
        font-family: var(--inter-ui);
        margin: 0;
    }

    .silph-co-container {
        height: 100vh;
        background: var(--silph-dark);
        position: relative;
        z-index: 10;
    }

    /* Hide global elements that interfere with full-screen app view */
    .pokemon-footer {
        display: none !important;
    }

    main.py-4 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .pro-detail-container {
        height: 100vh;
        background: var(--silph-dark);
        background-image:
            radial-gradient(circle at center, rgba(0, 242, 255, 0.03) 0%, transparent 70%),
            linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
        background-size: 100% 100%, 20px 20px, 20px 20px;
    }

    /* Left Section: Visual Hub */
    .silph-visual-hub {
        background: radial-gradient(circle at 30% 30%, #1a1c23 0%, #000 100%);
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    .silph-aura {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, var(--aura-color) 0%, transparent 65%);
        transform: translate(-50%, -50%);
        opacity: 0.15;
        filter: blur(80px);
        z-index: 1;
        animation: aura-sway 12s infinite alternate-reverse var(--pro-easing);
    }

    @keyframes aura-sway {
        0% {
            opacity: 0.1;
            transform: translate(-45%, -50%) scale(0.9);
        }

        100% {
            opacity: 0.3;
            transform: translate(-55%, -50%) scale(1.1);
        }
    }

    /* High-Visibility Pokeball Navigation Controls */
    .silph-nav-controls {
        position: absolute;
        top: 50%;
        width: 100%;
        display: flex;
        justify-content: space-between;
        transform: translateY(-50%);
        z-index: 20;
        padding: 0 30px;
        pointer-events: none;
    }

    .silph-nav-controls a {
        pointer-events: auto;
    }

    /* Silph Elemental Chips */
    .silph-elemental-chip {
        background: var(--chip-color);
        border-radius: 100px;
        padding: 1px;
        position: relative;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.4s var(--pro-easing);
    }

    .chip-inner {
        background: radial-gradient(circle at 15% 15%, rgba(255, 255, 255, 0.2) 0%, transparent 60%);
        padding: 5px 18px 5px 8px;
        border-radius: 100px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chip-icon {
        width: 26px;
        height: 26px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--chip-color);
        filter: drop-shadow(0 0 5px var(--chip-color));
        border: 0.5px solid rgba(255, 255, 255, 0.1);
    }

    .chip-label {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: 1.5pt;
        color: #fff;
    }

    /* Artwork Area */
    .silph-artwork-area {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 5;
    }

    .silph-grid-overlay {
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    .silph-art-img {
        max-width: 85%;
        max-height: 85%;
        filter: drop-shadow(0 30px 60px rgba(0, 0, 0, 0.6));
        z-index: 10;
    }

    .silph-float {
        animation: silph-floating 6s ease-in-out infinite;
    }

    @keyframes silph-floating {

        0%,
        100% {
            transform: translateY(0) scale(1.05);
        }

        50% {
            transform: translateY(-40px) scale(1.1);
        }
    }

    /* Watermark */
    .silph-branding-watermark {
        position: absolute;
        bottom: 5%;
        left: 5%;
        opacity: 0.1;
        pointer-events: none;
    }

    .id-text {
        font-family: var(--mont-bold);
        font-size: 12vw;
        font-weight: 900;
        line-height: 0.8;
    }

    .name-text {
        font-family: var(--mont-bold);
        font-size: 6vw;
        font-weight: 900;
        margin-top: -1vw;
    }

    /* Data Panel */
    .silph-data-panel {
        background: #fff;
        color: #111;
        z-index: 30;
        box-shadow: -20px 0 50px rgba(0, 0, 0, 0.5);
        overflow-y: auto;
    }

    .silph-name-display {
        font-family: var(--mont-bold);
        font-weight: 900;
        font-size: 4rem;
        letter-spacing: -3px;
    }

    .silph-meta-text {
        font-size: 10px;
        font-weight: 800;
        color: #999;
    }

    .silph-section-header {
        font-family: var(--mont-bold);
        font-weight: 900;
        font-size: 10px;
        color: #bbb;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-line {
        width: 40px;
        height: 3px;
        background: #eee;
    }

    .stat-count {
        font-family: var(--mont-bold);
        font-size: 20px;
        font-weight: 900;
    }

    .silph-meter {
        height: 10px;
        background: #f0f0f0;
        border-radius: 100px;
        overflow: hidden;
    }

    .meter-fill {
        height: 100%;
        background: var(--m-color);
        transition: width 2s var(--pro-easing);
    }

    /* Evolution */
    .silph-evo-thumb {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #f9f9f9;
        border: 2px solid #eee;
        padding: 5px;
        display: block;
        position: relative;
        transition: all 0.3s;
    }

    .silph-evo-thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .silph-evo-thumb:hover {
        transform: translateY(-5px);
        border-color: var(--silph-cyan);
    }

    .active-neon {
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        border: 2px solid var(--silph-cyan);
        border-radius: 50%;
        opacity: 0;
        box-shadow: 0 0 15px var(--silph-cyan);
    }

    .silph-evo-thumb.active .active-neon {
        opacity: 1;
    }

    /* Interaction Badge */
    .silph-status-badge {
        background: rgba(0, 242, 255, 0.05);
        padding: 5px 15px;
        border-radius: 100px;
        font-size: 10px;
        color: var(--silph-cyan);
        border: 1px solid rgba(0, 242, 255, 0.1);
    }

    .scanning-beam {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(0, 242, 255, 0.2), transparent);
        animation: linear-scan 4s infinite;
    }

    @keyframes linear-scan {
        0% {
            left: -100%;
        }

        25% {
            left: 200%;
        }

        100% {
            left: 200%;
        }
    }

    @media (max-width: 991px) {
        .silph-co-container {
            height: auto;
            overflow-y: scroll;
        }

        .silph-visual-hub {
            height: auto;
            min-height: 50vh;
            border-right: none;
            padding-bottom: 2rem;
        }

        .silph-nav-controls {
            top: 25%;
            padding: 0 15px;
        }

        .silph-nav-controls .pagination-pokeball {
            width: 50px !important;
            height: 50px !important;
        }

        .silph-nav-controls .page-num {
            width: 26px !important;
            height: 26px !important;
            font-size: 14px !important;
        }

        .silph-data-panel {
            box-shadow: none;
            border-top: 5px solid var(--silph-red);
            height: auto;
        }

        .silph-interaction-footer {
            width: 100%;
            text-align: center;
        }

        .silph-interaction-footer .btn-pokemon-retro {
            width: 100%;
            justify-content: center;
        }

        .silph-branding-watermark {
            display: none;
        }

        .silph-name-display {
            font-size: 3rem;
        }
    }

    /* Moves Grid */
    .silph-move-table-wrapper {
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        max-height: 500px;
        overflow-y: auto;
        padding: 5px;
    }

    .silph-table {
        margin-bottom: 0;
        color: #ddd;
        font-size: 12px;
    }

    .silph-table thead th {
        background: rgba(255, 255, 255, 0.03);
        color: #888;
        font-family: var(--mont-bold);
        font-size: 10px;
        letter-spacing: 1px;
        padding: 10px;
    }

    .silph-table tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        transition: background 0.2s;
    }

    .silph-table tbody tr:hover {
        background: rgba(0, 242, 255, 0.05);
    }

    .silph-table td {
        vertical-align: middle;
        padding: 8px 10px;
    }

    .small-type {
        font-family: var(--mont-bold);
        font-size: 9px;
        opacity: 0.8;
    }

    .silph-move-row.loaded .small-type {
        opacity: 1;
    }

    /* Scrollbar */
    .silph-move-table-wrapper::-webkit-scrollbar {
        width: 4px;
    }

    .silph-move-table-wrapper::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
    }

    .silph-move-table-wrapper::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }
</style>
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