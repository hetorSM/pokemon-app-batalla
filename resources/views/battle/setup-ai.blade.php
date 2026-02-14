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
</script>
@endpush