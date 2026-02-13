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
    <style>
        :root {
            --silph-red: #cc0000;
            --silph-dark: #0f1115;
            --silph-cyan: #00f2ff;
            --tech-satin: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            --tech-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        body {
            background-color: var(--silph-dark);
            background-image:
                radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.02) 1px, transparent 0);
            background-size: 40px 40px;
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* Tech-Elite Button Base (Improved Visibility) */
        .btn-tech-elite {
            position: relative;
            background: #3a3f4b;
            /* Lightened from #2a2d34 for visibility */
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            padding: 12px 24px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5pt;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.4s var(--pro-easing);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* High-Contrast Retro Pokémon Button with Pokeball Icon */
        .btn-pokemon-retro {
            position: relative;
            background: #ff0000;
            color: #fff;
            border: 3px solid #000;
            border-radius: 12px;
            padding: 12px 30px 12px 50px;
            /* Space for icon */
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            box-shadow:
                inset -4px -4px 0px rgba(0, 0, 0, 0.3),
                inset 4px 4px 0px rgba(255, 255, 255, 0.4),
                0 8px 0px #000;
            transition: all 0.1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        /* Pure CSS Pokeball Icon */
        .btn-pokemon-retro::before {
            content: '';
            position: absolute;
            left: 12px;
            width: 24px;
            height: 24px;
            background: linear-gradient(to bottom, #ff0000 50%, #ffffff 50%);
            border: 2px solid #000;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .btn-pokemon-retro::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #fff;
            border: 2px solid #000;
            border-radius: 50%;
            z-index: 3;
        }

        .btn-pokemon-retro:hover {
            filter: brightness(1.2);
            color: #fff;
            transform: translateY(-2px);
            box-shadow:
                inset -4px -4px 0px rgba(0, 0, 0, 0.3),
                inset 4px 4px 0px rgba(255, 255, 255, 0.4),
                0 10px 0px #000;
        }

        .btn-pokemon-retro:active {
            transform: translateY(6px);
            box-shadow:
                inset -2px -2px 0px rgba(0, 0, 0, 0.3),
                inset 2px 2px 0px rgba(255, 255, 255, 0.4),
                0 2px 0px #000;
        }

        .btn-pokemon-retro.blue {
            background-color: #007bff;
        }

        .btn-pokemon-retro.blue::before {
            background: linear-gradient(to bottom, #007bff 50%, #ffffff 50%);
        }

        .btn-pokemon-retro.green {
            background-color: #28a745;
        }

        .btn-pokemon-retro.green::before {
            background: linear-gradient(to bottom, #28a745 50%, #ffffff 50%);
        }

        .btn-pokemon-retro.yellow {
            background-color: #ffc107;
            color: #000;
        }

        .btn-pokemon-retro.yellow::before {
            background: linear-gradient(to bottom, #ffc107 50%, #ffffff 50%);
        }

        .btn-pokemon-retro.yellow::after {
            background: #fff;
        }

        .btn-pokemon-retro.yellow:hover {
            color: #000;
        }

        /* circular Pokeball Pagination */
        .pagination-pokeball {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(to bottom, #ff0000 50%, #ffffff 50%);
            border: 3px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            color: #000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .pagination-pokeball::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 3px;
            background: #000;
            top: 50%;
            transform: translateY(-50%);
            left: 0;
            z-index: 1;
        }

        .pagination-pokeball .page-num {
            position: relative;
            z-index: 5;
            background: #fff;
            border: 3px solid #000;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .pagination-pokeball:hover {
            transform: scale(1.15) rotate(15deg);
            box-shadow: 0 6px 15px rgba(255, 0, 0, 0.4);
        }

        .pagination-pokeball.active {
            border-color: #00f2ff;
            box-shadow: 0 0 20px #00f2ff;
        }

        .pagination-pokeball.active .page-num {
            background: #00f2ff;
            border-color: #000;
        }

        /* Material Finish & Rim Light for Tech-Elite */
        .btn-tech-elite::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--tech-satin);
            border-radius: inherit;
            pointer-events: none;
        }

        .btn-tech-elite::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .btn-tech-elite:hover {
            background: #4a4f5b;
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Elemental Glow Shadows */
        .btn-tech-glow[style*="--glow-color"] {
            box-shadow: 0 0 15px var(--glow-color);
        }

        /* Navbar Refinement */
        .pokemon-navbar {
            background: #000;
            border-bottom: 2px solid var(--silph-red);
            padding: 15px 0;
            backdrop-filter: blur(10px);
        }

        .pokemon-navbar .navbar-brand {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
            color: #fff !important;
            text-shadow: none;
        }

        .pokemon-navbar .nav-link {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
        }

        .pokemon-navbar .nav-link:hover {
            opacity: 1;
            color: var(--silph-cyan) !important;
        }

        @media (max-width: 768px) {
            .pokemon-navbar .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
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

    @stack('scripts')
</body>

</html>