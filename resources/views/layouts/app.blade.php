<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pokémon Battle')</title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@800&family=Montserrat:wght@800;900&family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos Pokémon -->
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">

    <!-- Global Loader Styles -->

    @stack('styles')
</head>

<body>
    <!-- Input Blocker (Invisible but prevents double-clicks) -->
    <div id="input-blocker"></div>

    <!-- Global Loader Overlay (Visual only, bottom right) -->
    <div id="global-loader">
        <div class="loader-content">
            <div class="loader-pokeball"></div>
            <div class="loader-text">Procesando...</div>
        </div>
    </div>

    <!-- Navbar Pokémon -->
    <nav class="navbar navbar-expand-lg navbar-dark pokemon-navbar">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <i class="fas fa-dragon"></i> Pokémon Battle
                <span class="pokeball"></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('inicio') }}">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('pokemon.index') }}">
                            <i class="fas fa-book"></i> Pokédex
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('pokedex.items') }}">
                            <i class="fas fa-briefcase"></i> Objetos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('team.index') }}">
                            <i class="fas fa-users"></i> Mi Equipo
                            @php
                            $teamCount = count(session('team', []));
                            @endphp
                            @if ($teamCount > 0)
                            <span class="badge bg-warning ms-1">
                                {{ $teamCount }}/6
                            </span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('battle.select-mode') }}">
                            <i class="fas fa-fist-raised"></i> Batalla
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="py-4">
        @yield('content')
    </main>

    <!-- Footer Pokémon -->
    <footer class="pokemon-footer">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-6">
                    <h5>Pokémon Battle Simulator</h5>
                    <p>Proyecto educativo desarrollado con Laravel y PokéAPI</p>
                </div>
                <div class="col-md-6">
                    <h5>Información</h5>
                    <p class="mb-1">Datos obtenidos de: <a href="https://pokeapi.co/" class="text-warning">PokéAPI</a>
                    </p>
                    <p class="mb-0">No afiliado a Nintendo/The Pokémon Company</p>
                </div>
            </div>
            <hr class="my-3 bg-light">
            <p class="mb-0">
                <i class="fas fa-code"></i> con <i class="fas fa-heart text-danger"></i>
                para entrenadores Pokémon
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación para las tarjetas
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.pokemon-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-10px)';
                });
                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Efecto para los badges de tipos
            const typeBadges = document.querySelectorAll('.type-badge');
            typeBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function () {
                    this.style.transform = 'scale(1.1)';
                    this.style.transition = 'transform 0.2s';
                });
                badge.addEventListener('mouseleave', function () {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>

    <!-- Global Loader Script -->
    <script>
        let loadingTimeout;

        window.showLoadingScreen = function (mode = 'mini') {
            // mode: 'mini' (default, bottom-right) or 'full' (centered overlay)

            // Only show if the operation takes longer than 300ms to avoid flickering
            loadingTimeout = setTimeout(() => {
                const loader = document.getElementById('global-loader');
                const blocker = document.getElementById('input-blocker');

                if (loader) {
                    if (mode === 'full') {
                        loader.classList.add('full-screen');
                        loader.querySelector('.loader-text').innerText = 'CARGANDO...';
                    } else {
                        loader.classList.remove('full-screen');
                        loader.querySelector('.loader-text').innerText = 'PROCESANDO...';
                    }
                    loader.classList.add('visible');
                }

                if (blocker) blocker.classList.add('active');
            }, 300);
        };

        window.hideLoadingScreen = function () {
            // Cancel the showing if it finishes quickly
            if (loadingTimeout) clearTimeout(loadingTimeout);

            const loader = document.getElementById('global-loader');
            const blocker = document.getElementById('input-blocker');
            if (loader) loader.classList.remove('visible');
            if (blocker) blocker.classList.remove('active');
        };

        // Auto-show on navigation (FULL SCREEN mode for page loads)
        document.addEventListener('DOMContentLoaded', () => {
            // Intercept links to show loader (excluding external links or anchors)
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('javascript') && link.target !== '_blank') {
                        // Don't show if holding ctrl/cmd key (opening in new tab)
                        if (!e.ctrlKey && !e.metaKey) {
                            window.showLoadingScreen('full');
                        }
                    }
                });
            });

            // Also hide on pageshow (for back button cache history)
            window.addEventListener('pageshow', () => {
                window.hideLoadingScreen();
            });
        });
    </script>

    @stack('scripts')
</body>

</html>