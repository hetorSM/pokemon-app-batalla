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

            const label = document.createElement('label');
            label.className = 'p2-search-label';
            label.innerText = `Pokémon ${i + 1}`;

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

            input.addEventListener('focus', () => {
                if (input.value.length > 0) resultsDiv.style.display = 'block';
            });

            input.addEventListener('input', () => {
                const query = input.value.toLowerCase().trim();
                resultsDiv.innerHTML = '';
                if (query.length < 1) { resultsDiv.style.display = 'none'; hiddenInput.value = ''; return; }

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
                        item.addEventListener('click', () => {
                            input.value = name;
                            hiddenInput.value = id;
                            resultsDiv.style.display = 'none';
                        });
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

            div.appendChild(label);
            div.appendChild(input);
            div.appendChild(hiddenInput);
            div.appendChild(resultsDiv);
            p2Selectors.appendChild(div);
        }
    }

    teamSizeSelect.addEventListener('change', () => {
        if (document.getElementById('p2_manual').checked) updateSelectors();
    });
</script>
@endpush