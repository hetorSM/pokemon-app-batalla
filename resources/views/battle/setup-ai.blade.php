@extends('layouts.app')

@section('title', 'Configurar Batalla vs IA')

@section('content')
<div class="container" style="max-width: 1100px; margin-top: 30px;">
    {{-- Header --}}
    <div class="text-center mb-4">
        <a href="{{ route('battle.select-mode') }}" class="back-link">← VOLVER AL CENTRO DE BATALLA</a>
        <h1 class="setup-title mt-3">🤖 BATALLA VS IA</h1>
        <p class="setup-subtitle">Configura los parámetros de tu combate</p>
    </div>

    <div class="row g-4">
        {{-- LEFT: Configuration Panel --}}
        <div class="col-lg-5">
            <div class="config-panel">
                <div class="panel-header">
                    <span class="panel-icon">⚙️</span>
                    <span class="panel-title">CONFIGURACIÓN</span>
                </div>

                <form action="{{ route('battle.start.ai') }}" method="POST">
                    @csrf

                    {{-- Difficulty --}}
                    <div class="config-section">
                        <label class="config-label">DIFICULTAD</label>
                        @foreach($difficulties as $value => $label)
                        <label class="difficulty-option {{ $value == 'hard' ? 'selected' : '' }}"
                            for="difficulty_{{ $value }}">
                            <input type="radio" name="difficulty" id="difficulty_{{ $value }}" value="{{ $value }}" {{
                                $value=='hard' ? 'checked' : '' }}
                                onchange="document.querySelectorAll('.difficulty-option').forEach(o => o.classList.remove('selected')); this.closest('.difficulty-option').classList.add('selected');">
                            <div class="diff-indicator diff-{{ $value }}"></div>
                            <div>
                                <span class="diff-name">{{ $label }}</span>
                                @if($value == 'easy')
                                <span class="diff-desc">IA básica, ideal para aprender</span>
                                @elseif($value == 'normal')
                                <span class="diff-desc">IA equilibrada, buen desafío</span>
                                @else
                                <span class="diff-desc">IA experta, solo para maestros</span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>

                    {{-- Team Size --}}
                    <div class="config-section">
                        <label class="config-label">TAMAÑO DEL EQUIPO</label>
                        <select class="config-select" name="team_size" id="team_size">
                            @for($i = 1; $i <= min(6, count($team)); $i++) <option value="{{ $i }}" {{ $i==min(3,
                                count($team)) ? 'selected' : '' }}>
                                {{ $i }} Pokémon
                                </option>
                                @endfor
                        </select>
                        <span class="config-hint">Se usarán los primeros Pokémon de tu equipo</span>
                    </div>

                    {{-- Level --}}
                    <div class="config-section">
                        <label class="config-label">NIVEL DE POKÉMON</label>
                        <div class="level-slider-row">
                            <input type="range" class="config-range" name="level" id="levelSlider" min="1" max="100"
                                value="50" step="1">
                            <span class="level-badge" id="levelDisplay">Nv. 50</span>
                        </div>
                        <span class="config-hint">Todos los Pokémon serán de este nivel</span>
                    </div>

                    {{-- Items --}}
                    <div class="config-section">
                        <label class="config-label">
                            OBJETOS DE BATALLA
                            <span class="item-counter" id="itemCount">0/6</span>
                        </label>
                        <div class="items-grid-setup">
                            @foreach($items as $item)
                            <label class="item-option" for="item_{{ $item['id'] }}">
                                <input class="item-checkbox" type="checkbox" name="items[]" value="{{ $item['id'] }}"
                                    id="item_{{ $item['id'] }}">
                                <img src="{{ $item['sprite'] }}" alt="{{ $item['name'] }}" class="item-img">
                                <div class="item-details">
                                    <span class="item-name-text">{{ $item['name'] }}</span>
                                    <span class="item-desc-text">{{ $item['description'] }}</span>
                                </div>
                                <div class="item-check-mark">✓</div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Start Button --}}
                    <div class="mt-4">
                        <div id="movesHiddenInputs"></div>
                        <button type="submit" class="btn-pokemon-retro w-100 start-battle-btn">
                            ¡COMENZAR BATALLA!
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT: Team Preview --}}
        <div class="col-lg-7">
            <div class="config-panel">
                <div class="panel-header">
                    <span class="panel-icon">👁️</span>
                    <span class="panel-title">TU EQUIPO</span>
                </div>

                @if(count($team) == 0)
                <div class="empty-team-msg">
                    <div class="empty-icon">❌</div>
                    <h4>No tienes Pokémon</h4>
                    <p>Ve a la Pokédex para añadir Pokémon a tu equipo</p>
                    <a href="{{ route('pokemon.index') }}" class="btn-pokemon-retro" style="font-size:11px;">EXPLORAR
                        POKÉDEX</a>
                </div>
                @else
                <div class="row g-3" id="teamPreview">
                    @foreach($team as $index => $pokemon)
                    <div class="col-md-4 col-6 team-member {{ $index >= min(3, count($team)) ? 'd-none' : '' }}"
                        data-index="{{ $index }}">
                        <div class="team-pkmn-card {{ $index < min(3, count($team)) ? 'active-member' : '' }}">
                            <div class="pkmn-card-img">
                                <img src="{{ $pokemon['image'] }}" alt="{{ $pokemon['name'] }}">
                            </div>
                            <div class="pkmn-card-info">
                                <span class="pkmn-card-name">{{ $pokemon['name'] }}</span>
                                <span class="pkmn-card-id">#{{ str_pad($pokemon['id'], 3, '0', STR_PAD_LEFT) }}</span>
                                <div class="pkmn-card-types">
                                    @foreach($pokemon['types'] as $type)
                                    <span class="pkmn-type-badge"
                                        style="background-color: {{ \App\Helpers\PokemonHelper::getTypeColor($type) }};">
                                        {{ strtoupper($type) }}
                                    </span>
                                    @endforeach
                                </div>
                                <div class="pkmn-card-stats">
                                    <span>PS: {{ $pokemon['stats']['hp'] }}</span>
                                    <span>ATK: {{ $pokemon['stats']['attack'] }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 moves-btn"
                                    data-index="{{ $index }}"
                                    onclick="openMovesModal({{ $index }}, {{ $pokemon['id'] }}, '{{ $pokemon['name'] }}')">
                                    Configurar Movimientos
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="selection-notice mt-3">
                    ℹ️ Se usarán los primeros <strong id="selectedCount">{{ min(3, count($team)) }}</strong> Pokémon
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Moves Modal -->
<div class="modal fade" id="movesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content config-panel">
            <div class="modal-header">
                <h5 class="modal-title" id="movesModalTitle">Seleccionar Movimientos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="modal-title">Selecciona hasta 4 movimientos.</span>
                    <span class="badge bg-primary" id="movesModalCount">0/4</span>
                </div>
                <div id="movesContainer" class="moves-container-scroll">
                    <!-- Moves will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Guardar</button>
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
    // Level slider
    const levelSlider = document.getElementById('levelSlider');
    const levelDisplay = document.getElementById('levelDisplay');
    levelSlider?.addEventListener('input', function () {
        levelDisplay.textContent = 'Nv. ' + this.value;
    });

    // Team size selector
    document.getElementById('team_size')?.addEventListener('change', function () {
        const size = parseInt(this.value);
        document.getElementById('selectedCount').textContent = size;

        document.querySelectorAll('.team-member').forEach((member, index) => {
            if (index < size) {
                member.classList.remove('d-none');
                member.querySelector('.team-pkmn-card')?.classList.add('active-member');
            } else {
                member.classList.add('d-none');
                member.querySelector('.team-pkmn-card')?.classList.remove('active-member');
            }
        });
    });

    // Item limit (max 6)
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const itemCountDisplay = document.getElementById('itemCount');

    function updateItemCount() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        itemCountDisplay.textContent = checked + '/6';

        if (checked >= 6) {
            itemCheckboxes.forEach(cb => { if (!cb.checked) cb.disabled = true; });
        } else {
            itemCheckboxes.forEach(cb => cb.disabled = false);
        }
    }

    itemCheckboxes.forEach(cb => cb.addEventListener('change', updateItemCount));

    // --- MOVE SELECTION LOGIC ---
    let currentPokemonIndex = null;
    const movesModal = new bootstrap.Modal(document.getElementById('movesModal'));
    const movesContainer = document.getElementById('movesContainer');
    const modalTitle = document.getElementById('movesModalTitle');

    // Store selected moves: [index => [move1, move2...]]
    let teamMoves = {};

    function openMovesModal(index, pokemonId, pokemonName) {
        currentPokemonIndex = index;
        modalTitle.textContent = `Movimientos de ${pokemonName}`;
        movesContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p>Cargando movimientos...</p></div>';
        movesModal.show();

        fetch(`/battle/pokemon-moves/${pokemonId}`)
            .then(res => res.json())
            .then(data => {
                renderMoves(data.moves, data.all_moves);
            })
            .catch(err => {
                movesContainer.innerHTML = '<p class="text-danger text-center">Error al cargar movimientos.</p>';
                console.error(err);
            });
    }

    function renderMoves(detailedMoves, simpleMoves) {
        movesContainer.innerHTML = '';
        const currentSelection = teamMoves[currentPokemonIndex] || [];

        // Combine moves to ensure we have a unique list
        // Prefer detailed moves if available
        let uniqueMoves = new Map();

        detailedMoves.forEach(m => uniqueMoves.set(m.name, { name: m.name, level: m.level, method: m.method }));
        simpleMoves.forEach(m => {
            if (!uniqueMoves.has(m)) uniqueMoves.set(m, { name: m, level: 0, method: 'other' });
        });

        // Sort: Level up descending, then others
        const sortedMoves = Array.from(uniqueMoves.values()).sort((a, b) => {
            if (a.method === 'level-up' && b.method !== 'level-up') return -1;
            if (a.method !== 'level-up' && b.method === 'level-up') return 1;
            return b.level - a.level;
        });

        if (sortedMoves.length === 0) {
            movesContainer.innerHTML = '<p class="text-center text-muted">No hay movimientos disponibles.</p>';
            return;
        }

        const list = document.createElement('div');
        list.className = 'moves-list-grid';

        sortedMoves.forEach(move => {
            const isSelected = currentSelection.includes(move.name);
            const item = document.createElement('div');
            item.className = `move-item ${isSelected ? 'selected' : ''}`;
            item.onclick = () => toggleMove(move.name, item);

            let badge = '';
            if (move.method === 'level-up') badge = `<span class="badge bg-secondary" style="font-size:0.7em;">Nivel ${move.level}</span>`;
            else badge = `<span class="badge bg-info text-dark" style="font-size:0.7em;">${move.method}</span>`;

            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="text-transform: capitalize;">${move.name.replace('-', ' ')}</span>
                    ${badge}
                </div>
            `;
            list.appendChild(item);
        });

        movesContainer.appendChild(list);
        updateMoveCounter();
    }

    function toggleMove(moveName, element) {
        if (!teamMoves[currentPokemonIndex]) teamMoves[currentPokemonIndex] = [];

        const index = teamMoves[currentPokemonIndex].indexOf(moveName);
        if (index > -1) {
            teamMoves[currentPokemonIndex].splice(index, 1);
            element.classList.remove('selected');
        } else {
            if (teamMoves[currentPokemonIndex].length >= 4) {
                alert('¡Máximo 4 movimientos!');
                return;
            }
            teamMoves[currentPokemonIndex].push(moveName);
            element.classList.add('selected');
        }
        updateMoveCounter();
        updateHiddenInputs();
        updateButtonState(currentPokemonIndex);
    }

    function updateMoveCounter() {
        const count = teamMoves[currentPokemonIndex] ? teamMoves[currentPokemonIndex].length : 0;
        document.getElementById('movesModalCount').textContent = `${count}/4`;
    }

    function updateHiddenInputs() {
        const container = document.getElementById('movesHiddenInputs');
        container.innerHTML = '';

        Object.keys(teamMoves).forEach(index => {
            teamMoves[index].forEach(move => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `moves[${index}][]`;
                input.value = move;
                container.appendChild(input);
            });
        });
    }

    function updateButtonState(index) {
        const btn = document.querySelector(`.moves-btn[data-index="${index}"]`);
        if (btn) {
            const count = teamMoves[index] ? teamMoves[index].length : 0;
            if (count > 0) {
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                btn.textContent = `Movimientos: ${count}`;
            } else {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
                btn.textContent = 'Configurar Movimientos';
            }
        }
    }
</script>
@endpush