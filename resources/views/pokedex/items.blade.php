@extends('layouts.app')

@section('title', 'Silph Co. Logistics - Item Database')

@section('content')
<div class="container mt-4 mb-5">
    <!-- Silph Co. Logistics Header -->
    <div class="pokedex-frame p-4 mb-5 shadow-lg" style="background: rgba(42, 45, 52, 0.95); border: 2px solid #000;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="digital-screen p-3 rounded-3 shadow-sm border border-dark mb-2"
                    style="background: #1a1c23;">
                    <h1 class="display-6 fw-bold m-0"
                        style="color: var(--silph-cyan); font-family: 'Orbitron', sans-serif; letter-spacing: 2px; text-shadow: 0 0 10px rgba(0, 242, 255, 0.3);">
                        <i class="fas fa-boxes-stacked me-2"></i> SILPH_CO // LOGISTICS
                    </h1>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="digital-text text-warning fw-bold">INVENTORY_SCAN: {{ count($items) }} UNITS
                        DETECTED</span>
                    <div class="silph-status-badge ms-2"
                        style="background: rgba(0, 255, 136, 0.1); color: #00ff88; border-color: rgba(0, 255, 136, 0.3);">
                        <div class="scanning-beam"></div>
                        <span class="pulse-dot" style="background: #00ff88;"></span> SECURE_LINK_ACTIVE
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <div class="digital-text small" style="color: #fff; opacity: 0.8;">
                    REF_ID: {{ time() }}<br>
                    LOC: <span class="text-info">SECTOR_7G_VAULT</span>
                </div>
            </div>
        </div>

        <div class="silph-grid-overlay mt-4"
            style="height: 3px; background: linear-gradient(90deg, var(--silph-cyan), transparent);"></div>

        <!-- Items Grid -->
        <div class="row g-4 mt-2">
            @foreach($items as $item)
            @php
            $catColor = match($item['category']) {
            'healing' => '#00ff88',
            'status_cure' => '#00f2ff',
            'revive' => '#ffcc00',
            'stat_boost' => '#ff4500',
            default => '#a0a0a0'
            };
            $catGlow = $catColor . '33'; // 20% opacity for glow
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="item-resource-capsule p-3 h-100"
                    style="--c-accent: {{ $catColor }}; --c-glow: {{ $catGlow }};">
                    <div class="capsule-top-accent"></div>
                    <div class="d-flex align-items-start gap-3 position-relative z-2">
                        <!-- Sprite Container -->
                        <div class="item-sprite-hub">
                            <div class="hub-ring"></div>
                            <img src="{{ $item['sprite'] }}" alt="{{ $item['name'] }}" class="item-img">
                        </div>

                        <!-- Info Area -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h5 class="item-id-text m-0">{{ strtoupper($item['name']) }}</h5>
                                <span class="tech-chip-small">
                                    {{ strtoupper($item['category']) }}
                                </span>
                            </div>
                            <div class="item-sub-name">{{ strtoupper($item['name_en'] ?? '') }}</div>

                            <p class="item-description-stream mt-3">
                                {{ $item['description'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Tech Footer -->
                    <div
                        class="item-tech-footer mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="val-display">
                            <span class="val-label">CREDITS_VAL:</span>
                            <span class="val-count">
                                @if(is_numeric($item['value']) && $item['value'] > 0)
                                {{ str_pad($item['value'], 4, '0', STR_PAD_LEFT) }}
                                @else
                                <span class="text-danger">INF_NULL</span>
                                @endif
                            </span>
                        </div>
                        <div class="load-bar">
                            @php
                            $loadWidth = is_numeric($item['value']) ? min(100, ($item['value']/200) * 100) : 100;
                            @endphp
                            <div class="load-fill" style="width: {{ $loadWidth }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('styles')
<style>
    .item-resource-capsule {
        background: rgba(35, 38, 45, 0.95);
        border: 2px solid #000;
        border-top: 4px solid var(--c-accent);
        border-radius: 8px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }

    .capsule-top-accent {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 40px;
        background: linear-gradient(to bottom, var(--c-glow), transparent);
        pointer-events: none;
        z-index: 1;
    }

    .item-resource-capsule:hover {
        transform: translateY(-8px) scale(1.02);
        background: rgba(45, 48, 55, 1);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 15px var(--c-glow);
        border-color: #000;
    }

    .item-sprite-hub {
        width: 75px;
        height: 75px;
        background: #000;
        border: 2px solid var(--c-accent);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 0 10px var(--c-glow);
    }

    .hub-ring {
        position: absolute;
        width: 140%;
        height: 140%;
        border: 1px solid var(--c-accent);
        opacity: 0.3;
        border-radius: 50%;
        animation: ring-pulse 3s infinite linear;
    }

    @keyframes ring-pulse {
        0% {
            transform: scale(0.4);
            opacity: 0.8;
        }

        100% {
            transform: scale(1.2);
            opacity: 0;
        }
    }

    .item-img {
        width: 52px;
        height: 52px;
        z-index: 2;
        filter: drop-shadow(0 0 8px var(--c-glow));
    }

    .item-id-text {
        font-family: 'Orbitron', sans-serif;
        font-size: 15px;
        letter-spacing: 0.5px;
        color: #fff;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .item-sub-name {
        font-size: 11px;
        color: var(--silph-cyan);
        font-weight: 800;
        letter-spacing: 0.5px;
        opacity: 0.7;
    }

    .tech-chip-small {
        font-size: 9px;
        font-weight: 900;
        padding: 3px 10px;
        border-radius: 4px;
        background: #000;
        border-left: 3px solid var(--c-accent);
        color: #fff;
        letter-spacing: 1px;
    }

    .item-description-stream {
        font-size: 13px;
        line-height: 1.5;
        color: #eee;
        height: 3.8em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        font-weight: 500;
    }

    .item-tech-footer {
        border-color: rgba(0, 0, 0, 0.5) !important;
        background: rgba(0, 0, 0, 0.2);
        padding: 10px;
        margin: -3px -15px -15px -15px;
        border-radius: 0 0 8px 8px;
    }

    .val-label {
        font-size: 10px;
        font-weight: 800;
        color: #888;
    }

    .val-count {
        font-family: 'JetBrains Mono', monospace;
        color: #fff;
        font-size: 15px;
        font-weight: 900;
        margin-left: 8px;
    }

    .load-bar {
        width: 80px;
        height: 6px;
        background: #000;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .load-fill {
        height: 100%;
        background: var(--c-accent);
        box-shadow: 0 0 10px var(--c-accent);
        border-radius: 10px;
    }

    .digital-text {
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.5px;
    }
</style>
@endpush
@endsection