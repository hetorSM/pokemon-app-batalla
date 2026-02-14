@extends('layouts.app')

@section('title', 'Terminal de Acceso - Silph Co.')

@section('content')
<div class="silph-terminal-container min-vh-100 overflow-hidden position-relative">
    <!-- Animated Grid Background -->
    <div class="silph-grid-background"></div>
    <div class="silph-scanline"></div>

    <div class="container position-relative z-3 pt-5">
        <div class="text-center mb-5">
            <!-- Hero Mascot with Tech Aura -->
            <div class="hero-aura-container mb-4">
                <div class="tech-rings">
                    <div class="ring ring-1"></div>
                    <div class="ring ring-2"></div>
                    <div class="ring ring-3"></div>
                </div>
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/25.png"
                    alt="Pikachu" class="hero-mascot floating-hero">
            </div>

            <!-- Header Terminal -->
            <div class="terminal-header-box d-inline-block px-5 py-3 mb-4">
                <h1 class="display-4 fw-bold m-0 terminal-title">
                    <span class="text-white">SILPH_CO</span> <span class="text-cyan">// ACCESS_TERMINAL</span>
                </h1>
                <div class="terminal-status mt-2">
                    <span class="status-dot pulse"></span>
                    <span class="status-text neon-text">SECURE_LINK_STABLE</span>
                    <span class="ms-3 status-text opacity-50">OS_V.12.5.0</span>
                </div>
            </div>

            <p class="hero-lead lead text-white opacity-75 mb-5 mx-auto" style="max-width: 700px;">
                Bienvenido al simulador táctico de combate más avanzado del mundo.
                Gestiona tu arsenal genético y ejecuta protocolos de batalla de alta precisión.
            </p>
        </div>

        <!-- Action Grid -->
        <div class="row g-4 justify-content-center mb-5">
            <!-- Module 1: Pokedex -->
            <div class="col-lg-4 col-md-6">
                <div class="command-module glass-box h-100" style="--accent: var(--silph-cyan);">
                    <div class="module-header d-flex justify-content-between">
                        <span class="module-id">ID_01</span>
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div class="module-body text-center p-4">
                        <div class="module-icon-hub mb-3">
                            <i class="fas fa-book-atlas fa-3x"></i>
                        </div>
                        <h4 class="fw-bold">DATABANK_SCAN</h4>
                        <p class="opacity-75 small">Consulta el registro genético completo y las métricas de rendimiento
                            de todas las unidades conocidas.</p>
                        <a href="{{ route('pokemon.index') }}" class="btn-pokemon-retro blue mt-3 w-100">
                            EXPLORE_DATA
                        </a>
                    </div>
                </div>
            </div>

            <!-- Module 2: Team -->
            <div class="col-lg-4 col-md-6">
                <div class="command-module glass-box h-100" style="--accent: var(--silph-red);">
                    <div class="module-header d-flex justify-content-between">
                        <span class="module-id">ID_02</span>
                        <i class="fas fa-users-gear"></i>
                    </div>
                    <div class="module-body text-center p-4">
                        <div class="module-icon-hub mb-3">
                            <i class="fas fa-dna fa-3x"></i>
                        </div>
                        <h4 class="fw-bold">UNIT_SYNC</h4>
                        <p class="opacity-75 small">Configura tu escuadrón táctico de 6 unidades para la sincronización
                            neuronal antes del despliegue.</p>
                        <a href="{{ route('team.index') }}" class="btn-pokemon-retro mt-3 w-100">
                            SYNC_SQUAD
                        </a>
                    </div>
                </div>
            </div>

            <!-- Module 3: Battle -->
            <div class="col-lg-4 col-md-6">
                <div class="command-module glass-box h-100" style="--accent: #ffc107;">
                    <div class="module-header d-flex justify-content-between">
                        <span class="module-id">ID_03</span>
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="module-body text-center p-4">
                        <div class="module-icon-hub mb-3">
                            <i class="fas fa-crosshairs fa-3x"></i>
                        </div>
                        <h4 class="fw-bold">COMBAT_SIM</h4>
                        <p class="opacity-75 small">Inicia protocolos de combate por turnos contra algoritmos de IA de
                            nivel élite en entornos controlados.</p>
                        <a href="{{ route('battle.select-mode') }}" class="btn-pokemon-retro yellow mt-3 w-100">
                            LAUNCH_SIM
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Stats Bar -->
        <div class="terminal-footer-stats mt-5 p-3 rounded-2 border border-dark">
            <div class="row align-items-center opacity-50 small">
                <div class="col-md-3 d-flex align-items-center gap-2">
                    <span class="fw-bold text-cyan">LATENCY:</span>
                    <span>12ms</span>
                    <div class="mini-graph" style="height: 5px; width: 50px; background: #222;">
                        <div style="height: 100%; width: 70%; background: var(--silph-cyan);"></div>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-center gap-2">
                    <span class="fw-bold text-warning">TEMP:</span>
                    <span>32.4°C</span>
                </div>
                <div class="col-md-3 d-flex align-items-center gap-2">
                    <span class="fw-bold text-success">UPTIME:</span>
                    <span>99.9%</span>
                </div>
                <div class="col-md-3 text-end">
                    <span>SILPH_CO_SYSTEMS_OPERATIONAL</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
@endpush
@endsection