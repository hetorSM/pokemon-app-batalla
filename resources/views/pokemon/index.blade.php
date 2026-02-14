@use('App\Helpers\PokemonHelper')
@extends('layouts.app')

@section('title', 'Pokédex - Página ' . $currentPage)

@section('content')
<div class="container mt-4">
    <!-- Pokédex Frame -->
    <div class="pokedex-frame p-4 mb-5">
        <!-- Header con estadísticas -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <div class="digital-screen p-3 rounded-3 shadow-sm border border-dark mb-2">
                    <h1 class="display-6 fw-bold m-0"
                        style="color: #edeff2; font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
                        <i class="fas fa-microchip me-2"></i>
                        @if(isset($searchQuery))
                        SCANNING: "{{ strtoupper($searchQuery) }}"
                        @else
                        NATIONAL DATABASE
                        @endif
                    </h1>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if(isset($searchQuery))
                    <span class="digital-text text-warning">FOUND: {{ count($pokemons) }} UNITS</span>
                    <a href="{{ route('pokemon.index') }}" class="btn-pokemon-retro ms-2"
                        style="font-size: 10px; padding: 5px 15px;">
                        RESET_SCAN
                    </a>
                    @else
                    <span class="digital-text">SECTOR: {{ $currentPage }} / {{ $totalPages }}</span>
                    <span class="badge bg-danger border border-light ms-2" style="font-family: 'Orbitron', sans-serif;">
                        ONLINE: {{ $totalPages * 20 }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="col-md-4 text-end">
                <form action="{{ route('pokemon.search') }}" method="GET" class="position-relative" id="mainSearchForm">
                    <div class="input-group pokedex-input-group shadow">
                        <input type="text" name="q" id="pokedexSearchInput" class="form-control digital-input"
                            placeholder="ID or NAME..." value="{{ $searchQuery ?? old('q') }}" required
                            autocomplete="off">
                        <button class="btn btn-pokedex-action" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="pokedexSearchResults" class="pokemon-search-results w-100" style="top: 100%; left: 0;">
                    </div>
                </form>
            </div>
        </div>

        <!-- Grid de Pokémon -->
        @if (count($pokemons) === 0)
        <div class="digital-screen p-5 text-center rounded-4 border border-danger shadow-inner">
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3 floating"></i>
            <h4 class="digital-text-danger">NO SIGNAL DETECTED</h4>
            <p class="text-white-50">Database returned zero results for this sector.</p>
        </div>
        @else
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4 py-2">
            @foreach ($pokemons as $pokemon)
            <div class="col">
                <div class="pokedex-card h-100 shadow-lg">
                    <!-- Screen Area -->
                    <div class="pokemon-screen-container p-2">
                        <div class="pokemon-screen bg-dark rounded-3 position-relative overflow-hidden">
                            <div class="scanline"></div>
                            <img src="{{ $pokemon['image'] }}" class="img-fluid pokemon-sprite p-2"
                                alt="{{ $pokemon['name'] }}" loading="lazy">
                            <div class="id-tag">#{{ str_pad($pokemon['id'], 3, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>

                    <!-- Data Area -->
                    <div class="p-3 bg-light border-top border-2 border-dark">
                        <h5 class="text-center text-uppercase fw-bold mb-2 pokedex-name">
                            {{ $pokemon['name'] }}
                        </h5>

                        <!-- Type Badges instead of Star -->
                        <div class="d-flex justify-content-center gap-1 mb-3">
                            @foreach($pokemon['types'] ?? ['normal'] as $type)
                            <span class="pokedex-type-badge"
                                style="background-color: {{ PokemonHelper::getTypeColor($type) }}">
                                {{ $type }}
                            </span>
                            @endforeach
                        </div>

                        <!-- Action Button -->
                        <div class="d-grid pt-2">
                            <a href="{{ route('pokemon.show', ['id' => $pokemon['id'], 'from_page' => $currentPage]) }}"
                                class="btn-pokemon-retro blue">
                                VIEW_DETAILS
                            </a>
                        </div>
                    </div>

                    <!-- Mechanic Elements -->
                    <div class="card-footer-pokedex bg-pokedex-red p-1 d-flex justify-content-center gap-2">
                        <div class="pokedex-deco-light bg-warning"></div>
                        <div class="pokedex-deco-light bg-info"></div>
                        <div class="pokedex-deco-light bg-success"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Paginación Modernizada -->
        @if ($totalPages > 1 && !isset($searchQuery))
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center gap-2">
                <li class="page-item {{ !$hasPrevious ? 'disabled opacity-50' : '' }}">
                    <a class="pagination-pokeball" href="{{ $hasPrevious ? '?page=' . ($currentPage - 1) : '#' }}">
                        <div class="page-num"><i class="fas fa-angle-left"></i></div>
                    </a>
                </li>

                @php
                $start = max(1, $currentPage - 1);
                $end = min($totalPages, $currentPage + 1);
                @endphp

                @if($start > 1)
                <li class="page-item">
                    <a class="pagination-pokeball" href="?page=1">
                        <div class="page-num">1</div>
                    </a>
                </li>
                @if($start > 2)<li class="page-item disabled d-flex align-items-center mx-1"><span
                        class="digital-text small">...</span></li>@endif
                @endif

                @for ($i = $start; $i <= $end; $i++) <li class="page-item">
                    <a class="pagination-pokeball {{ $i == $currentPage ? 'active' : '' }}" href="?page={{ $i }}">
                        <div class="page-num">{{ $i }}</div>
                    </a>
                    </li>
                    @endfor

                    @if($end < $totalPages) @if($end < $totalPages - 1)<li
                        class="page-item disabled d-flex align-items-center mx-1"><span
                            class="digital-text small">...</span></li>@endif
                        <li class="page-item">
                            <a class="pagination-pokeball" href="?page={{ $totalPages }}">
                                <div class="page-num">{{ $totalPages }}</div>
                            </a>
                        </li>
                        @endif

                        <li class="page-item {{ !$hasNext ? 'disabled opacity-50' : '' }}">
                            <a class="pagination-pokeball" href="{{ $hasNext ? '?page=' . ($currentPage + 1) : '#' }}">
                                <div class="page-num"><i class="fas fa-angle-right"></i></div>
                            </a>
                        </li>
            </ul>
        </nav>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Press+Start+2P&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/pokedex.css') }}">
@endpush

@push('scripts')
<script>
    const availablePokemon = @json($available_pokemon ?? []);
    const searchInput = document.getElementById('pokedexSearchInput');
    const resultsDiv = document.getElementById('pokedexSearchResults');
    const searchForm = document.getElementById('mainSearchForm');

    if (searchInput && resultsDiv) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            resultsDiv.innerHTML = '';

            if (query.length < 1) {
                resultsDiv.style.display = 'none';
                return;
            }

            const matches = Object.entries(availablePokemon).filter(([id, name]) => {
                return id.includes(query) || name.toLowerCase().includes(query);
            }).slice(0, 10);

            if (matches.length > 0) {
                matches.forEach(([id, name]) => {
                    const item = document.createElement('div');
                    item.className = 'pokemon-search-item p-3 d-flex align-items-center gap-3 cursor-pointer';
                    item.innerHTML = `
                            <div class="bg-dark rounded p-1">
                                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${id}.png" alt="${name}" style="width: 40px; height: 40px; object-fit: contain;">
                            </div>
                            <div>
                                <div class="fw-bold fs-6 text-capitalize" style="color: #fff;">${name}</div>
                                <div class="small" style="color: var(--digital-green); font-size: 0.7rem;">#${id.padStart(3, '0')}</div>
                            </div>
                        `;
                    item.addEventListener('click', () => {
                        searchInput.value = name;
                        resultsDiv.style.display = 'none';
                        searchForm.submit();
                    });
                    resultsDiv.appendChild(item);
                });
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchForm.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length > 0 && resultsDiv.children.length > 0) {
                resultsDiv.style.display = 'block';
            }
        });
    }
</script>
@endpush