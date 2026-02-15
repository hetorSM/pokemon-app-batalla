@extends('layouts.app')

@section('title', 'Configurar Batalla 2 Jugadores')

@section('content')
<div class="container-fluid" style="margin-top: 30px;">
    {{-- Header --}}
    <div class="text-center mb-4">
        <a href="{{ route('battle.select-mode') }}" class="back-link">← VOLVER AL CENTRO DE BATALLA</a>
        <h1 class="setup-title mt-3">👥 BATALLA 2 JUGADORES</h1>
        <p class="setup-subtitle">Modo Local — Hotseat</p>
    </div>

    @if(session('error'))
    <div class="alert alert-danger text-center mx-auto mb-4" style="max-width: 800px;">
        {{ session('error') }}
    </div>
    @endif

    <form action="{{ route('battle.start.multiplayer') }}" method="POST" id="multiplayerForm">
        @csrf

        {{-- Global Config --}}
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="config-panel p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <label class="config-label m-0">TAMAÑO DEL EQUIPO</label>
                                <select class="config-select w-auto" name="team_size" id="team_size">
                                    @for($i = 1; $i <= 6; $i++) <option value="{{ $i }}" {{ $i==3 ? 'selected' : '' }}>
                                        {{ $i }}</option>
                                        @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <label class="config-label m-0">NIVEL</label>
                                <div class="flex-grow-1 d-flex align-items-center gap-2">
                                    <input type="range" class="config-range" name="level" id="levelSlider" min="1"
                                        max="100" value="50" step="1">
                                    <span class="level-badge" id="levelDisplay">Nv. 50</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Columns --}}
        <div class="row g-4">
            {{-- PLAYER 1 COLUMN --}}
            <div class="col-md-6">
                <div class="config-panel">
                    <div class="panel-header text-primary">
                        <span class="panel-icon">👤</span>
                        <span class="panel-title">JUGADOR 1</span>
                    </div>

                    {{-- P1 Mode --}}
                    <div class="config-section">
                        <div class="p2-mode-selector">
                            <label class="p2-mode-option selected" for="p1_session">
                                <input type="radio" name="p1_mode" id="p1_session" value="session" checked
                                    onchange="toggleP1Mode()">
                                <div class="p2-mode-icon">💾</div>
                                <span class="p2-mode-text">MI EQUIPO</span>
                            </label>
                            <label class="p2-mode-option" for="p1_manual">
                                <input type="radio" name="p1_mode" id="p1_manual" value="manual"
                                    onchange="toggleP1Mode()">
                                <div class="p2-mode-icon">✏️</div>
                                <span class="p2-mode-text">MANUAL</span>
                            </label>
                        </div>
                    </div>

                    {{-- P1 Team --}}
                    <div id="p1_session_team">
                        <div class="row g-2">
                            @foreach($team as $index => $pokemon)
                            <div class="col-12 p1-session-item" data-index="{{ $index }}">
                                <div class="team-pkmn-card d-flex align-items-center justify-content-between px-3 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $pokemon['image'] }}" alt="{{ $pokemon['name'] }}"
                                            style="width: 40px; height: 40px;">
                                        <div class="text-start">
                                            <span class="fw-bold text-white d-block" style="font-size: 0.9em;">{{
                                                $pokemon['name'] }}</span>
                                            <span class="text-muted" style="font-size: 0.7em;">Nivel Actual</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary moves-btn"
                                        onclick="openMovesModal('p1', {{ $index }}, {{ $pokemon['id'] }}, '{{ $pokemon['name'] }}')"
                                        data-prefix="p1" data-index="{{ $index }}">
                                        Movimientos
                                    </button>
                                </div>
                            </div>
                            @endforeach
                            @if(count($team) == 0)
                            <div class="text-center text-muted py-4">No tienes Pokémon en tu equipo de sesión.</div>
                            @endif
                        </div>
                    </div>

                    <div id="p1_manual_team" class="d-none">
                        <div id="p1_selectors">
                            <!-- JS Generated -->
                        </div>
                    </div>

                    {{-- P1 Items --}}
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <label class="config-label">MOCHILA (OBJETOS)</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="p1_items_toggle"
                                onchange="toggleItems('p1')">
                            <label class="form-check-label text-light" for="p1_items_toggle">Personalizar
                                Objetos</label>
                        </div>
                        <div id="p1_items_container" class="d-none items-grid-setup">
                            @foreach($available_items ?? [] as $item)
                            <label class="item-option">
                                <input type="checkbox" name="p1_items[]" value="{{ $item['id'] }}" class="p1-item-cb">
                                <img src="{{ $item['sprite'] }}" class="item-img" alt="{{ $item['name'] }}">
                                <div class="item-details">
                                    <span class="item-name-text">{{ $item['name'] }}</span>
                                    <span class="item-desc-text">{{ Str::limit($item['effect'], 40) }}</span>
                                </div>
                                <div class="item-check-mark">✓</div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- PLAYER 2 COLUMN --}}
            <div class="col-md-6">
                <div class="config-panel">
                    <div class="panel-header text-danger">
                        <span class="panel-icon">💻</span>
                        <span class="panel-title">JUGADOR 2</span>
                    </div>

                    {{-- P2 Mode --}}
                    <div class="config-section">
                        <div class="p2-mode-selector">
                            <label class="p2-mode-option selected" for="p2_random">
                                <input type="radio" name="p2_mode" id="p2_random" value="random" checked
                                    onchange="toggleP2Mode()">
                                <div class="p2-mode-icon">🎲</div>
                                <span class="p2-mode-text">ALEATORIO</span>
                            </label>
                            <label class="p2-mode-option" for="p2_manual">
                                <input type="radio" name="p2_mode" id="p2_manual" value="manual"
                                    onchange="toggleP2Mode()">
                                <div class="p2-mode-icon">✏️</div>
                                <span class="p2-mode-text">MANUAL</span>
                            </label>
                        </div>
                    </div>

                    {{-- P2 Team --}}
                    <div id="p2_random_team" class="text-center py-5">
                        <div style="font-size: 4rem; opacity: 0.3;">❓</div>
                        <p class="text-muted">Equipo Aleatorio Generado al Inicio</p>
                    </div>

                    <div id="p2_manual_team" class="d-none">
                        <div id="p2_selectors">
                            <!-- JS Generated -->
                        </div>
                    </div>

                    {{-- P2 Items --}}
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <label class="config-label">MOCHILA (OBJETOS)</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="p2_items_toggle"
                                onchange="toggleItems('p2')">
                            <label class="form-check-label text-light" for="p2_items_toggle">Personalizar
                                Objetos</label>
                        </div>
                        <div id="p2_items_container" class="d-none items-grid-setup">
                            @foreach($available_items ?? [] as $item)
                            <label class="item-option">
                                <input type="checkbox" name="p2_items[]" value="{{ $item['id'] }}" class="p2-item-cb">
                                <img src="{{ $item['sprite'] }}" class="item-img" alt="{{ $item['name'] }}">
                                <div class="item-details">
                                    <span class="item-name-text">{{ $item['name'] }}</span>
                                    <span class="item-desc-text">{{ Str::limit($item['effect'], 40) }}</span>
                                </div>
                                <div class="item-check-mark">✓</div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Start Button --}}
        <div class="container" style="max-width: 600px;">
            <div class="mt-5">
                <button type="submit" class="btn-pokemon-retro blue w-100 start-battle-btn">
                    ¡COMENZAR BATALLA LOCAL!
                </button>
            </div>
        </div>

        <div id="movesHiddenInputs"></div>
    </form>
</div>

<!-- Moves Modal -->
<div class="modal fade" id="movesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content config-panel p-0">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-white" id="movesModalTitle">Seleccionar Movimientos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Selecciona hasta 4 movimientos.</span>
                    <span class="badge bg-primary" id="movesModalCount">0/4</span>
                </div>
                <div id="movesContainer" class="moves-container-scroll" style="min-height: 300px;">
                    <!-- Moves will be loaded here -->
                </div>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/battle-setup.css') }}">
<style>
    /* Additional Styles specific to Revamp if needed */
    .p1-session-item.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
</style>
@endpush

@push('scripts')
<script>
    // --- GLOBAL CONFIG ---
    const levelSlider = document.getElementById('levelSlider');
    const levelDisplay = document.getElementById('levelDisplay');
    if (levelSlider) {
        levelSlider.addEventListener('input', function () {
            levelDisplay.textContent = 'Nv. ' + this.value;
        });
    }

    const teamSizeSelect = document.getElementById('team_size');
    if (teamSizeSelect) {
        teamSizeSelect.addEventListener('change', () => {
            updateTeamSizeUI();
        });
    }

    function updateTeamSizeUI() {
        const size = parseInt(teamSizeSelect.value);

        // P1 Session Team Visibility
        document.querySelectorAll('.p1-session-item').forEach((el, index) => {
            if (index < size) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });

        // Re-render Manual Selectors if active
        if (document.getElementById('p1_manual').checked) renderSelectors('p1');
        if (document.getElementById('p2_manual').checked) renderSelectors('p2');
    }

    // --- MODE TOGGLES ---
    function toggleP1Mode() {
        const isSession = document.getElementById('p1_session').checked;
        document.getElementById('p1_session_team').classList.toggle('d-none', !isSession);
        document.getElementById('p1_manual_team').classList.toggle('d-none', isSession);

        document.querySelector('label[for="p1_session"]').classList.toggle('selected', isSession);
        document.querySelector('label[for="p1_manual"]').classList.toggle('selected', !isSession);

        if (!isSession) renderSelectors('p1');
    }

    function toggleP2Mode() {
        const isRandom = document.getElementById('p2_random').checked;
        document.getElementById('p2_random_team').classList.toggle('d-none', !isRandom);
        document.getElementById('p2_manual_team').classList.toggle('d-none', isRandom);

        document.querySelector('label[for="p2_random"]').classList.toggle('selected', isRandom);
        document.querySelector('label[for="p2_manual"]').classList.toggle('selected', !isRandom);

        if (!isRandom) renderSelectors('p2');
    }

    function toggleItems(prefix) {
        const toggle = document.getElementById(`${prefix}_items_toggle`);
        if (!toggle) return;
        const checked = toggle.checked;
        const container = document.getElementById(`${prefix}_items_container`);
        if (!container) return;

        container.classList.toggle('d-none', !checked);

        if (!checked) {
            container.querySelectorAll('input').forEach(i => i.disabled = true);
        } else {
            container.querySelectorAll('input').forEach(i => i.disabled = false);
        }
    }

    // Initialize items disabled state
    toggleItems('p1');
    toggleItems('p2');

    // --- POKEMON SELECTOR LOGIC (GENERIC) ---
    const availablePokemon = @json($available_pokemon ?? []);
    let selectedPokemon = { p1: {}, p2: {} };
    let teamMoves = { p1: {}, p2: {} };

    function renderSelectors(prefix) {
        const container = document.getElementById(`${prefix}_selectors`);
        const size = parseInt(teamSizeSelect.value);
        container.innerHTML = '';

        for (let i = 0; i < size; i++) {
            const current = selectedPokemon[prefix][i];
            const div = document.createElement('div');
            div.className = 'mb-3 pokemon-search-container position-relative';

            const topRow = document.createElement('div');
            topRow.className = 'd-flex justify-content-between align-items-center mb-1';

            const label = document.createElement('label');
            label.className = 'config-label m-0';
            label.innerText = `Pokémon ${i + 1}`;

            const moveBtn = document.createElement('button');
            moveBtn.type = 'button';
            moveBtn.className = 'btn btn-sm btn-outline-primary moves-btn py-0 px-2';
            moveBtn.style.fontSize = '0.75rem';
            moveBtn.innerText = 'Movimientos';
            moveBtn.dataset.prefix = prefix;
            moveBtn.dataset.index = i;
            moveBtn.style.display = current ? 'block' : 'none';

            if (teamMoves[prefix][i] && teamMoves[prefix][i].length > 0) {
                moveBtn.textContent = `Movimientos: ${teamMoves[prefix][i].length}`;
                moveBtn.classList.add('btn-success');
                moveBtn.classList.remove('btn-outline-primary');
            }

            moveBtn.onclick = () => {
                if (selectedPokemon[prefix][i]) {
                    openMovesModal(prefix, i, selectedPokemon[prefix][i].id, selectedPokemon[prefix][i].name);
                }
            };

            topRow.appendChild(label);
            topRow.appendChild(moveBtn);

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'p2-search-input';
            input.placeholder = 'Buscar...';
            input.autocomplete = 'off';
            input.value = current ? current.name : '';

            const hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = `${prefix}_pokemon[]`;
            hiddenId.value = current ? current.id : '';

            const resultsDiv = document.createElement('div');
            resultsDiv.className = 'pokemon-search-results';
            resultsDiv.style.display = 'none';

            input.addEventListener('input', () => {
                handleSearch(input.value, resultsDiv, (id, name) => {
                    input.value = name;
                    hiddenId.value = id;
                    resultsDiv.style.display = 'none';
                    selectedPokemon[prefix][i] = { id, name };
                    moveBtn.style.display = 'block';

                    // Reset moves if pokemon changed
                    delete teamMoves[prefix][i];
                    updateButtonState(prefix, i);
                    updateHiddenInputs();
                });
            });

            input.addEventListener('focus', () => { if (input.value) resultsDiv.style.display = 'block'; });
            document.addEventListener('click', (e) => { if (!div.contains(e.target)) resultsDiv.style.display = 'none'; });

            div.appendChild(topRow);
            div.appendChild(input);
            div.appendChild(hiddenId);
            div.appendChild(resultsDiv);
            container.appendChild(div);
        }
    }

    function handleSearch(query, resultsDiv, onSelect) {
        query = query.toLowerCase().trim();
        resultsDiv.innerHTML = '';
        if (query.length < 1) {
            resultsDiv.style.display = 'none';
            return;
        }

        let matches = [];
        for (const [id, name] of Object.entries(availablePokemon)) {
            if (id.includes(query) || name.toLowerCase().includes(query)) {
                matches.push([id, name]);
            }
            if (matches.length >= 15) break;
        }

        if (matches.length > 0) {
            matches.forEach(([id, name]) => {
                const item = document.createElement('div');
                item.className = 'pokemon-search-item';
                item.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${id}.png" style="width:30px;height:30px;">
                        <div>
                            <div class="fw-bold" style="font-size:12px; color:white;">${name}</div>
                            <small class="text-muted">#${id.padStart(3, '0')}</small>
                        </div>
                    </div>
                `;
                item.onclick = () => onSelect(id, name);
                resultsDiv.appendChild(item);
            });
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.style.display = 'none';
        }
    }

    // --- MOVE SELECTION ---
    let currentModalState = { prefix: '', index: 0, id: 0 };
    const movesModalEl = document.getElementById('movesModal');
    const movesModal = movesModalEl ? new bootstrap.Modal(movesModalEl) : null;
    const movesContainer = document.getElementById('movesContainer');
    const modalTitle = document.getElementById('movesModalTitle');

    function openMovesModal(prefix, index, id, name) {
        currentModalState = { prefix, index, id };
        if (modalTitle) modalTitle.textContent = `Movimientos: ${name} (${prefix.toUpperCase()})`;
        if (movesContainer) movesContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
        if (movesModal) movesModal.show();

        fetch(`/battle/pokemon-moves/${id}`)
            .then(r => r.json())
            .then(data => renderMovesGrid(data.moves, data.all_moves))
            .catch(err => {
                if (movesContainer) movesContainer.innerHTML = '<div class="alert alert-danger">Error al cargar movimientos</div>';
            });
    }

    function renderMovesGrid(detailed, simple) {
        const { prefix, index } = currentModalState;
        if (!movesContainer) return;
        const currentSelection = teamMoves[prefix][index] || [];
        movesContainer.innerHTML = '';

        let uniqueMoves = new Map();
        detailed.forEach(m => uniqueMoves.set(m.name, { name: m.name, level: m.level, method: m.method }));
        simple.forEach(m => { if (!uniqueMoves.has(m)) uniqueMoves.set(m, { name: m, level: 0, method: 'other' }); });

        const sorted = Array.from(uniqueMoves.values()).sort((a, b) => {
            if (a.method === 'level-up' && b.method !== 'level-up') return -1;
            if (a.method !== 'level-up' && b.method === 'level-up') return 1;
            return b.level - a.level;
        });

        const grid = document.createElement('div');
        grid.className = 'moves-list-grid';

        sorted.forEach(move => {
            const isSelected = currentSelection.includes(move.name);
            const card = document.createElement('div');
            card.className = `move-item ${isSelected ? 'selected' : ''}`;

            let badgeClass = 'badge-levelup';
            let badgeText = `Nivel ${move.level}`;
            if (move.method === 'machine') { badgeClass = 'badge-machine'; badgeText = 'MT/MO'; }
            else if (move.method === 'tutor') { badgeClass = 'badge-tutor'; badgeText = 'Tutor'; }
            else if (move.method === 'egg') { badgeClass = 'badge-egg'; badgeText = 'Huevo'; }
            else if (move.method === 'other') { badgeClass = 'badge-levelup'; badgeText = 'Otro'; }

            card.innerHTML = `
                <div class="d-flex flex-column h-100 justify-content-between">
                    <span class="fw-bold text-capitalize mb-1">${move.name.replace(/-/g, ' ')}</span>
                    <span class="move-badge ${badgeClass} align-self-start">${badgeText}</span>
                </div>
            `;

            card.onclick = () => {
                let list = teamMoves[prefix][index] || [];
                if (list.includes(move.name)) {
                    list = list.filter(m => m !== move.name);
                    card.classList.remove('selected');
                } else {
                    if (list.length >= 4) return alert('Máximo 4 movimientos');
                    list.push(move.name);
                    card.classList.add('selected');
                }
                teamMoves[prefix][index] = list;
                updateMovesCounter(prefix, index);
                updateHiddenInputs();
                updateButtonState(prefix, index);
            };
            grid.appendChild(card);
        });

        movesContainer.appendChild(grid);
        updateMovesCounter(prefix, index);
    }

    function updateMovesCounter(prefix, index) {
        const count = teamMoves[prefix][index] ? teamMoves[prefix][index].length : 0;
        const counter = document.getElementById('movesModalCount');
        if (counter) counter.textContent = `${count}/4`;
    }

    function updateHiddenInputs() {
        const container = document.getElementById('movesHiddenInputs');
        if (!container) return;
        container.innerHTML = '';
        ['p1', 'p2'].forEach(prefix => {
            Object.keys(teamMoves[prefix]).forEach(idx => {
                teamMoves[prefix][idx].forEach(move => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = `${prefix}_moves[${idx}][]`;
                    i.value = move;
                    container.appendChild(i);
                });
            });
        });
    }

    function updateButtonState(prefix, index) {
        let btn;
        if (prefix === 'p1' && document.getElementById('p1_session').checked) {
            btn = document.querySelector(`.p1-session-item[data-index="${index}"] .moves-btn`);
        } else {
            const container = document.getElementById(`${prefix}_selectors`);
            if (container && container.children[index]) {
                btn = container.children[index].querySelector('.moves-btn');
            }
        }

        if (btn) {
            const count = teamMoves[prefix][index] ? teamMoves[prefix][index].length : 0;
            if (count > 0) {
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                btn.textContent = `Movimientos: ${count}`;
            } else {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
                btn.textContent = 'Movimientos';
            }
        }
    }

    // Initialize UI
    window.addEventListener('DOMContentLoaded', () => {
        updateTeamSizeUI();
        toggleP1Mode();
        toggleP2Mode();
    });

</script>
@endpush
```