@extends('layouts.app')

@section('title', 'Configurar Batalla 2 Jugadores')

@section('content')
<div class="container" style="max-width: 800px; margin-top: 30px;">
    {{-- Header --}}
    <div class="text-center mb-4">
        <a href="{{ route('battle.select-mode') }}" class="back-link">← VOLVER AL CENTRO DE BATALLA</a>
        <h1 class="setup-title mt-3">👥 BATALLA 2 JUGADORES</h1>
        <p class="setup-subtitle">Modo Local — Hotseat</p>
    </div>

    <div class="config-panel">
        <div class="panel-header">
            <span class="panel-icon">⚙️</span>
            <span class="panel-title">CONFIGURACIÓN</span>
        </div>

        <form action="{{ route('battle.start.multiplayer') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-6">
                    {{-- Team Size --}}
                    <div class="config-section">
                        <label class="config-label">TAMAÑO DEL EQUIPO</label>
                        <select class="config-select" name="team_size" id="team_size">
                            @for($i = 1; $i <= min(6, count($team)); $i++) <option value="{{ $i }}" {{ $i==min(3,
                                count($team)) ? 'selected' : '' }}>
                                {{ $i }} Pokémon por jugador
                                </option>
                                @endfor
                        </select>
                    </div>

                    {{-- Level --}}
                    <div class="config-section">
                        <label class="config-label">NIVEL DE POKÉMON</label>
                        <div class="level-slider-row">
                            <input type="range" class="config-range" name="level" id="levelSlider" min="1" max="100"
                                value="50" step="1">
                            <span class="level-badge" id="levelDisplay">Nv. 50</span>
                        </div>
                    </div>

                    {{-- Items info --}}
                    <div class="config-section">
                        <label class="config-label">OBJETOS</label>
                        <div class="info-notice">
                            💊 Modo Sandbox: Cada jugador tendrá acceso a 10 unidades de TODOS los objetos disponibles.
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    {{-- P2 Mode --}}
                    <div class="config-section">
                        <label class="config-label">EQUIPO JUGADOR 2</label>
                        <div class="p2-mode-selector">
                            <label class="p2-mode-option selected" for="p2_random">
                                <input type="radio" name="p2_mode" id="p2_random" value="random" checked>
                                <div class="p2-mode-icon">🎲</div>
                                <span class="p2-mode-text">ALEATORIO</span>
                            </label>
                            <label class="p2-mode-option" for="p2_manual">
                                <input type="radio" name="p2_mode" id="p2_manual" value="manual">
                                <div class="p2-mode-icon">✏️</div>
                                <span class="p2-mode-text">MANUAL</span>
                            </label>
                        </div>
                    </div>

                    {{-- Manual Selection --}}
                    <div id="manualSelectionSection" class="config-section d-none">
                        <label class="config-label">POKÉMON J2</label>
                        <div id="p2_selectors">
                            {{-- Generated via JS --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Player info note --}}
            <div class="info-notice mt-3">
                ℹ️ <strong>J1</strong> usará tu equipo actual. <strong>J2</strong> obtendrá un equipo del mismo tamaño.
            </div>

            {{-- Start Button --}}
            <div class="mt-4">
                <button type="submit" class="btn-pokemon-retro blue w-100 start-battle-btn">
                    ¡COMENZAR BATALLA LOCAL!
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/battle-setup.css') }}">
@endpush

@push('scripts')
<script>
    const levelSlider = document.getElementById('levelSlider');
    const levelDisplay = document.getElementById('levelDisplay');
    if (levelSlider && levelDisplay) {
        levelSlider.addEventListener('input', function () {
            levelDisplay.textContent = 'Nv. ' + this.value;
        });
    }

    // --- MOVE SELECTION LOGIC ---
    let currentPokemonIndex = null;
    let currentPlayerPrefix = 'p1'; // 'p1' or 'p2'
    const movesModal = new bootstrap.Modal(document.getElementById('movesModal'));
    const movesContainer = document.getElementById('movesContainer');
    const modalTitle = document.getElementById('movesModalTitle');

    // Store selected moves: { p1: {index: []}, p2: {index: []} }
    let teamMoves = { p1: {}, p2: {} };

    function openMovesModal(prefix, index, pokemonId, pokemonName) {
        currentPlayerPrefix = prefix;
        currentPokemonIndex = index;
        modalTitle.textContent = `Movimientos de ${pokemonName} (${prefix === 'p1' ? 'J1' : 'J2'})`;
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
        const currentSelection = teamMoves[currentPlayerPrefix][currentPokemonIndex] || [];

        let uniqueMoves = new Map();
        detailedMoves.forEach(m => uniqueMoves.set(m.name, { name: m.name, level: m.level, method: m.method }));
        simpleMoves.forEach(m => {
            if (!uniqueMoves.has(m)) uniqueMoves.set(m, { name: m, level: 0, method: 'other' });
        });

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
        if (!teamMoves[currentPlayerPrefix][currentPokemonIndex]) teamMoves[currentPlayerPrefix][currentPokemonIndex] = [];

        const list = teamMoves[currentPlayerPrefix][currentPokemonIndex];
        const index = list.indexOf(moveName);

        if (index > -1) {
            list.splice(index, 1);
            element.classList.remove('selected');
        } else {
            if (list.length >= 4) {
                alert('¡Máximo 4 movimientos!');
                return;
            }
            list.push(moveName);
            element.classList.add('selected');
        }
        updateMoveCounter();
        updateHiddenInputs();
        updateButtonState(currentPlayerPrefix, currentPokemonIndex);
    }

    function updateMoveCounter() {
        const count = teamMoves[currentPlayerPrefix][currentPokemonIndex] ? teamMoves[currentPlayerPrefix][currentPokemonIndex].length : 0;
        document.getElementById('movesModalCount').textContent = `${count}/4`;
    }

    function updateHiddenInputs() {
        const container = document.getElementById('movesHiddenInputs');
        container.innerHTML = '';

        ['p1', 'p2'].forEach(prefix => {
            Object.keys(teamMoves[prefix]).forEach(index => {
                teamMoves[prefix][index].forEach(move => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${prefix}_moves[${index}][]`;
                    input.value = move;
                    container.appendChild(input);
                });
            });
        });
    }

    function updateButtonState(prefix, index) {
        const btn = document.querySelector(`.moves-btn[data-prefix="${prefix}"][data-index="${index}"]`);
        if (btn) {
            const count = teamMoves[prefix][index] ? teamMoves[prefix][index].length : 0;
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

    // --- P2 SELECTION LOGIC ---

    function toggleP2Mode() {
        const manual = document.getElementById('p2_manual').checked;
        const section = document.getElementById('manualSelectionSection');
        const options = document.querySelectorAll('.p2-mode-option');

        options.forEach(opt => opt.classList.remove('selected'));
        document.querySelector(manual ? 'label[for="p2_manual"]' : 'label[for="p2_random"]').classList.add('selected');

        if (manual) {
            section.classList.remove('d-none');
            updateSelectors();
        } else {
            section.classList.add('d-none');
            document.querySelectorAll('input[name="p2_pokemon[]"]').forEach(s => s.required = false);
        }
    }

    const p2Selectors = document.getElementById('p2_selectors');
    const teamSizeSelect = document.getElementById('team_size');
    const availablePokemon = @json($available_pokemon ?? []);

    function updateSelectors() {
        const size = parseInt(teamSizeSelect.value);
        const currentData = [];

        document.querySelectorAll('.p2-search-input').forEach((input, i) => {
            currentData[i] = {
                id: input.parentElement.querySelector('input[type="hidden"]')?.value || '',
                name: input.value
            };
        });

        p2Selectors.innerHTML = '';

        for (let i = 0; i < size; i++) {
            const div = document.createElement('div');
            div.className = 'mb-3 pokemon-search-container';

            const topRow = document.createElement('div');
            topRow.className = 'd-flex justify-content-between align-items-center mb-1';

            const label = document.createElement('label');
            label.className = 'p2-search-label m-0';
            label.innerText = `Pokémon ${i + 1}`;

            // Move button placeholder (generated if pokemon selected)
            const moveBtn = document.createElement('button');
            moveBtn.type = 'button';
            moveBtn.className = 'btn btn-sm btn-outline-primary moves-btn py-0 px-2';
            moveBtn.style.fontSize = '0.75rem';
            moveBtn.style.display = 'none'; // Hidden by default
            moveBtn.innerText = 'Movimientos';
            moveBtn.dataset.prefix = 'p2';
            moveBtn.dataset.index = i;

            topRow.appendChild(label);
            topRow.appendChild(moveBtn);

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'p2-search-input';
            input.placeholder = 'Buscar por nombre o ID...';
            input.autocomplete = 'off';
            input.value = currentData[i] ? currentData[i].name : '';

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'p2_pokemon[]';
            hiddenInput.value = currentData[i] ? currentData[i].id : '';
            hiddenInput.required = document.getElementById('p2_manual').checked;

            const resultsDiv = document.createElement('div');
            resultsDiv.className = 'pokemon-search-results';

            // Function to handle pokemon selection
            const selectPokemon = (id, name) => {
                input.value = name;
                hiddenInput.value = id;
                resultsDiv.style.display = 'none';

                // Show move button and update click handler
                moveBtn.style.display = 'block';
                moveBtn.onclick = () => openMovesModal('p2', i, id, name);

                // Reset moves for this slot if pokemon changed
                if (teamMoves.p2[i]) delete teamMoves.p2[i];
                updateButtonState('p2', i);
                updateHiddenInputs();
            };

            // Restore state if data exists
            if (currentData[i] && currentData[i].id) {
                moveBtn.style.display = 'block';
                moveBtn.onclick = () => openMovesModal('p2', i, currentData[i].id, currentData[i].name);
                // Need to re-apply button state if moves persist? 
                // For now moves are lost on re-render, effectively. 
                // Ideally we should persist simpleMoves between re-renders of selectors.
                // But since updateSelectors destroys DOM, we rely on teamMoves object.
                updateButtonState('p2', i);
            }

            input.addEventListener('focus', () => {
                if (input.value.length > 0) resultsDiv.style.display = 'block';
            });

            input.addEventListener('input', () => {
                const query = input.value.toLowerCase().trim();
                resultsDiv.innerHTML = '';
                if (query.length < 1) {
                    resultsDiv.style.display = 'none';
                    hiddenInput.value = '';
                    moveBtn.style.display = 'none';
                    return;
                }

                const matches = Object.entries(availablePokemon).filter(([id, name]) =>
                    id.includes(query) || name.toLowerCase().includes(query)
                ).slice(0, 20);

                if (matches.length > 0) {
                    matches.forEach(([id, name]) => {
                        const item = document.createElement('div');
                        item.className = 'pokemon-search-item';
                        item.innerHTML = `
                            <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${id}.png" alt="${name}">
                            <div>
                                <div class="fw-bold" style="font-size:12px;">${name}</div>
                                <div style="font-size:10px;color:#666;">#${id.padStart(3, '0')}</div>
                            </div>
                        `;
                        item.addEventListener('click', () => selectPokemon(id, name));
                        resultsDiv.appendChild(item);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                }
            });

            document.addEventListener('click', (e) => {
                if (!div.contains(e.target)) resultsDiv.style.display = 'none';
            });

            div.appendChild(topRow);
            div.appendChild(input);
            div.appendChild(hiddenInput);
            div.appendChild(resultsDiv);
            p2Selectors.appendChild(div);
        }
    }

    teamSizeSelect.addEventListener('change', () => {
        // Note: Changing team size rebuilds P2 selectors, so P2 moves might be desynced if we reduced size.
        // But logic allows preserving moves in `teamMoves` object even if not visible.
        // However, increasing size adds new slots.
        if (document.getElementById('p2_manual').checked) updateSelectors();
    });
</script>
@endpush