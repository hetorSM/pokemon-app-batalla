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
<link rel="stylesheet" href="{{ asset('css/pokedex.css') }}">
@endpush
@endsection