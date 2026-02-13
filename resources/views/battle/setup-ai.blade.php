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
@endsection

@push('styles')
<style>
    .back-link {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #888;
        text-decoration: none;
        transition: color 0.2s;
    }

    .back-link:hover {
        color: var(--silph-cyan);
    }

    .setup-title {
        font-family: 'Orbitron', sans-serif;
        font-weight: 800;
        font-size: 26px;
        color: #fff;
        letter-spacing: 3px;
    }

    .setup-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #888;
    }

    .config-panel {
        background: #1e2028;
        border: 2px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 24px;
        height: 100%;
    }

    .panel-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.06);
    }

    .panel-icon {
        font-size: 22px;
    }

    .panel-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 14px;
        letter-spacing: 2px;
        color: #fff;
    }

    .config-section {
        margin-bottom: 20px;
    }

    .config-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: 1.5px;
        color: #888;
        margin-bottom: 8px;
    }

    .config-hint {
        font-size: 11px;
        color: #555;
        display: block;
        margin-top: 4px;
    }

    /* Difficulty */
    .difficulty-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 2px solid rgba(255, 255, 255, 0.06);
        border-radius: 10px;
        margin-bottom: 6px;
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(0, 0, 0, 0.2);
    }

    .difficulty-option:hover {
        border-color: rgba(255, 255, 255, 0.15);
    }

    .difficulty-option.selected {
        border-color: var(--silph-cyan);
        background: rgba(0, 242, 255, 0.05);
    }

    .difficulty-option input {
        display: none;
    }

    .diff-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .diff-easy {
        background: #22C55E;
        box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
    }

    .diff-normal {
        background: #FBBF24;
        box-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
    }

    .diff-hard {
        background: #EF4444;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
    }

    .diff-name {
        font-weight: 700;
        font-size: 13px;
        color: #fff;
        display: block;
    }

    .diff-desc {
        font-size: 11px;
        color: #666;
    }

    /* Select */
    .config-select {
        width: 100%;
        padding: 10px 12px;
        background: rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
    }

    /* Level slider */
    .level-slider-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .config-range {
        flex: 1;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        height: 8px;
        outline: none;
    }

    .config-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(to bottom, #ff0000 50%, #fff 50%);
        border: 2px solid #333;
        cursor: pointer;
    }

    .level-badge {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        font-size: 13px;
        background: var(--silph-cyan);
        color: #000;
        padding: 4px 12px;
        border-radius: 6px;
        min-width: 55px;
        text-align: center;
    }

    /* Items */
    .item-counter {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        background: rgba(255, 255, 255, 0.08);
        padding: 2px 8px;
        border-radius: 4px;
        color: #aaa;
    }

    .items-grid-setup {
        max-height: 200px;
        overflow-y: auto;
    }

    .item-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        margin-bottom: 4px;
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(0, 0, 0, 0.15);
    }

    .item-option:hover {
        border-color: rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.25);
    }

    .item-option input {
        display: none;
    }

    .item-option input:checked~.item-check-mark {
        display: flex;
    }

    .item-option input:checked~.item-details .item-name-text {
        color: var(--silph-cyan);
    }

    .item-img {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }

    .item-details {
        flex: 1;
    }

    .item-name-text {
        font-weight: 700;
        font-size: 12px;
        color: #fff;
        display: block;
        transition: color 0.2s;
    }

    .item-desc-text {
        font-size: 10px;
        color: #666;
        display: block;
    }

    .item-check-mark {
        display: none;
        width: 22px;
        height: 22px;
        background: var(--silph-cyan);
        color: #000;
        border-radius: 50%;
        font-size: 12px;
        font-weight: 900;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .start-battle-btn {
        font-size: 16px !important;
        padding: 14px 30px 14px 55px !important;
    }

    /* Team preview cards */
    .team-pkmn-card {
        background: rgba(0, 0, 0, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        transition: all 0.3s;
    }

    .team-pkmn-card.active-member {
        border-color: rgba(34, 197, 94, 0.4);
    }

    .team-pkmn-card:hover {
        transform: translateY(-4px);
    }

    .pkmn-card-img img {
        width: 80px;
        height: 80px;
        object-fit: contain;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
    }

    .pkmn-card-name {
        display: block;
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #fff;
    }

    .pkmn-card-id {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        color: #666;
    }

    .pkmn-card-types {
        display: flex;
        gap: 4px;
        justify-content: center;
        margin: 6px 0;
    }

    .pkmn-type-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 800;
        color: #fff;
        letter-spacing: 0.5px;
    }

    .pkmn-card-stats {
        font-size: 10px;
        color: #888;
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .selection-notice {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 12px;
        color: #8BB8F6;
    }

    .empty-team-msg {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
</style>
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
</script>
@endpush