@extends('layouts.app')

@section('title', 'Arena de Batalla')

@section('content')
<div id="battleArena">
    {{-- ===== BATTLEFIELD ===== --}}
    <div class="pokemon-battlefield">
        {{-- Background layers --}}
        <div class="bf-sky"></div>
        <div class="bf-ground"></div>

        {{-- ENEMY INFO BOX (top-left) --}}
        <div class="pkmn-info-box enemy-info">
            <div class="info-box-inner">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="pkmn-name" id="aiName">{{ strtoupper($ai['name']) }}</span>
                    <span class="pkmn-level" id="aiLevel">Nv{{ $ai['level'] ?? 50 }}</span>
                </div>
                <div class="hp-bar-container">
                    <span class="hp-label">PS</span>
                    <div class="hp-bar-track">
                        <div class="hp-bar-fill {{ $aiHpClass }}" id="aiHpBar" style="width: {{ $aiHpPct }}%"></div>
                    </div>
                </div>
                {{-- Enemy team pokeballs --}}
                <div class="team-pokeballs mt-1" id="aiTeamBalls">
                    @foreach($battle['ai_team'] as $idx => $tm)
                    <div class="mini-pokeball {{ $tm['current_hp'] <= 0 ? 'fainted' : '' }}"></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ENEMY SPRITE (top-right) --}}
        <div class="pkmn-sprite enemy-sprite">
            <img src="{{ $ai['image'] }}" alt="{{ $ai['name'] }}" id="aiSprite" class="sprite-img enemy-img">
            <div class="sprite-shadow enemy-shadow"></div>
        </div>

        {{-- PLAYER SPRITE (bottom-left) --}}
        <div class="pkmn-sprite player-sprite">
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" id="playerSprite" class="sprite-img player-img">
            <div class="sprite-shadow player-shadow"></div>
        </div>

        {{-- VFX OVERLAY for attack animations --}}
        <div class="vfx-layer" id="vfxLayer">
            <div class="vfx-particles" id="vfxParticles"></div>
            <div class="vfx-flash" id="vfxFlash"></div>
        </div>

        {{-- PLAYER INFO BOX (bottom-right) --}}
        <div class="pkmn-info-box player-info">
            <div class="info-box-inner">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="pkmn-name" id="playerName">{{ strtoupper($p['name']) }}</span>
                    <span class="pkmn-level" id="playerLevel">Nv{{ $p['level'] ?? 50 }}</span>
                </div>
                <div class="hp-bar-container">
                    <span class="hp-label">PS</span>
                    <div class="hp-bar-track">
                        <div class="hp-bar-fill {{ $pHpPct <= 20 ? 'critical' : ($pHpPct <= 50 ? 'warning' : '') }}"
                            id="playerHpBar" style="width: {{ $pHpPct }}%"></div>
                    </div>
                </div>
                <div class="hp-text" id="playerHpText">{{ $p['current_hp'] }} / {{ $pMaxHp }}</div>
                {{-- Player team pokeballs --}}
                <div class="team-pokeballs mt-1" id="playerTeamBalls">
                    @foreach($battle['player_team'] as $idx => $tm)
                    <div class="mini-pokeball {{ $tm['current_hp'] <= 0 ? 'fainted' : '' }}"></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ===== BATTLE CONSOLE (bottom panel) ===== --}}
    <div class="battle-console">
        @if (!$battle['winner'])
        @php
        $isTurn = ($battle['mode'] == 'local') ? true : ($battle['turn'] == 'player');
        $sides = ($battle['mode'] == 'local') ? ['player', 'ai'] : ['player'];
        @endphp

        {{-- MESSAGE BOX --}}
        <div class="message-box" id="messageBox">
            <div class="message-text" id="messageText">¿Qué debería hacer {{ $p['name'] }}?</div>
        </div>

        {{-- MAIN ACTION BUTTONS --}}
        <div class="action-panel" id="mainActions">
            @foreach($sides as $side)
            @php
            $isPlayerSide = $side == 'player';
            $sideTeam = $isPlayerSide ? $battle['player_team'] : $battle['ai_team'];
            $sideIdx = $isPlayerSide ? $battle['player_active'] : $battle['ai_active'];
            $sideItems = $isPlayerSide ? ($battle['player_items'] ?? []) : ($battle['ai_items'] ?? []);
            $sidePokemon = $sideTeam[$sideIdx];
            $sideIsTurn = ($battle['mode'] == 'local') ? ($battle['turn'] == $side) : ($side == 'player');
            $sideLabel = ($battle['mode'] == 'local') ? ($isPlayerSide ? 'J1' : 'J2') : '';
            @endphp

            <div class="side-controls {{ count($sides) > 1 ? 'dual-mode' : '' }} {{ !$sideIsTurn ? 'waiting-turn' : '' }}"
                id="sideControls_{{ $side }}">

                @if($battle['mode'] == 'local')
                <div class="side-label {{ $isPlayerSide ? 'label-blue' : 'label-red' }}">
                    {{ $sideLabel }} — {{ $sidePokemon['name'] }}
                    @if(!$sideIsTurn)<span class="waiting-badge">ESPERANDO</span>@endif
                </div>
                @endif

                {{-- MENU BUTTONS --}}
                <div class="menu-buttons" id="menuButtons_{{ $side }}">
                    <button class="menu-btn btn-fight" onclick="showPanel('moves', '{{ $side }}')">LUCHAR</button>
                    <button class="menu-btn btn-bag" onclick="showPanel('items', '{{ $side }}')">MOCHILA</button>
                    <button class="menu-btn btn-pokemon" onclick="showPanel('switch', '{{ $side }}')">POKÉMON</button>
                    <button class="menu-btn btn-run"
                        onclick="if(confirm('¿Huir de la batalla?')) { if(window.showLoadingScreen) window.showLoadingScreen(); window.location='{{ route('battle.finish') }}'; }">HUIR</button>
                </div>

                {{-- MOVES PANEL --}}
                <div class="sub-panel d-none" id="movesPanel_{{ $side }}">
                    <div class="moves-grid">
                        @php $moves = $sidePokemon['moves'] ?? []; @endphp
                        @foreach($moves as $idx => $move)
                        @php
                        $moveColor = \App\Helpers\PokemonHelper::getTypeColor($move['type'] ?? 'normal');
                        $hasPP = ($move['current_pp'] ?? 0) > 0;
                        @endphp
                        <button class="move-btn {{ !$hasPP ? 'no-pp' : '' }}" style="--move-color: {{ $moveColor }}" {{
                            !$hasPP ? 'disabled' : '' }}
                            onclick="performAction('move', {move_index: {{ $idx }}}, '{{ $side }}')">
                            <span class="move-name">{{ strtoupper($move['name_es'] ?? $move['name'] ?? '???') }}</span>
                            <span class="move-meta">
                                <span class="move-type-tag" style="background:{{ $moveColor }}">{{
                                    strtoupper($move['type'] ?? 'NORMAL') }}</span>
                                <span class="move-pp">PP {{ $move['current_pp'] ?? $move['pp'] ?? 0 }}/{{ $move['pp'] ??
                                    0 }}</span>
                            </span>
                        </button>
                        @endforeach
                        @if(empty($moves))
                        <button class="move-btn" style="--move-color: #a8a878; grid-column: span 2"
                            onclick="performAction('move', {move_index: -1}, '{{ $side }}')">
                            <span class="move-name">FORCEJEO</span>
                            <span class="move-meta"><span class="move-pp">—</span></span>
                        </button>
                        @endif
                    </div>
                    <button class="back-btn" onclick="showPanel('main', '{{ $side }}')">← ATRÁS</button>
                </div>

                {{-- ITEMS PANEL --}}
                <div class="sub-panel d-none" id="itemsPanel_{{ $side }}">
                    @php $displayItems = array_filter($sideItems, function($i) { return ($i['quantity'] ?? 0) > 0; });
                    @endphp
                    @if(empty($displayItems))
                    <div class="empty-panel-msg">No hay objetos disponibles.</div>
                    @else
                    <div class="items-list">
                        @foreach($displayItems as $invItem)
                        <button class="item-btn" onclick="useItem({{ $invItem['id'] }}, '{{ $side }}')">
                            <img src="{{ $invItem['sprite'] }}" alt="{{ $invItem['name'] }}" class="item-sprite">
                            <span class="item-name">{{ $invItem['name'] }}</span>
                            <span class="item-qty">x{{ $invItem['quantity'] }}</span>
                        </button>
                        @endforeach
                    </div>
                    @endif
                    <button class="back-btn" onclick="showPanel('main', '{{ $side }}')">← ATRÁS</button>
                </div>

                {{-- ITEM TARGET PANEL --}}
                <div class="sub-panel d-none" id="itemTargetPanel_{{ $side }}">
                    <div class="empty-panel-msg mb-2">¿A quién quieres usar el objeto?</div>
                    <div class="switch-list">
                        @foreach ($sideTeam as $index => $pokemon)
                        <button class="switch-btn" onclick="selectItemTarget({{ $index }}, '{{ $side }}')">
                            <img src="{{ $pokemon['image'] }}"
                                class="switch-sprite {{ $pokemon['current_hp'] <= 0 ? 'fainted-sprite' : '' }}">
                            <div class="switch-info">
                                <span class="switch-name">{{ $pokemon['name'] }}</span>
                                <span class="switch-hp">{{ $pokemon['current_hp'] }}/{{ $pokemon['max_hp'] ??
                                    $pokemon['battle_stats']['hp'] ?? '?' }} PS</span>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    <button class="back-btn" onclick="cancelItemSelection('{{ $side }}')">← CANCELAR</button>
                </div>

                {{-- SWITCH PANEL --}}
                <div class="sub-panel d-none" id="switchPanel_{{ $side }}">
                    <div class="switch-list">
                        @foreach ($sideTeam as $index => $pokemon)
                        @if($index != $sideIdx && $pokemon['current_hp'] > 0)
                        <button class="switch-btn"
                            onclick="performAction('switch', {target: {{ $index }}}, '{{ $side }}')">
                            <img src="{{ $pokemon['image'] }}" class="switch-sprite">
                            <div class="switch-info">
                                <span class="switch-name">{{ $pokemon['name'] }}</span>
                                <span class="switch-hp">{{ $pokemon['current_hp'] }}/{{ $pokemon['max_hp'] ??
                                    $pokemon['battle_stats']['hp'] ?? '?' }} PS</span>
                            </div>
                        </button>
                        @endif
                        @endforeach
                    </div>
                    <button class="back-btn" onclick="showPanel('main', '{{ $side }}')">← ATRÁS</button>
                </div>
            </div>
            @endforeach
        </div>

        @else
        {{-- ===== VICTORY / DEFEAT ===== --}}
        <div class="battle-result">
            @if($battle['mode'] == 'local')
            @if($battle['winner'] == 'player')
            <div class="result-icon">🏆</div>
            <h2 class="result-title win">¡JUGADOR 1 GANA!</h2>
            <p class="result-sub">¡Has derrotado al Jugador 2!</p>
            @else
            <div class="result-icon">🏆</div>
            <h2 class="result-title win">¡JUGADOR 2 GANA!</h2>
            <p class="result-sub">¡Has derrotado al Jugador 1!</p>
            @endif
            @else
            @if($battle['winner'] == 'player')
            <div class="result-icon">🏆</div>
            <h2 class="result-title win">¡VICTORIA!</h2>
            <p class="result-sub">¡Has derrotado a la IA!</p>
            @else
            <div class="result-icon">💀</div>
            <h2 class="result-title lose">DERROTA</h2>
            <p class="result-sub">La IA te ha vencido...</p>
            @endif
            @endif
            <div class="result-actions">
                <a href="{{ $battle['mode'] == 'local' ? route('battle.setup.multiplayer') : route('battle.setup.ai') }}"
                    class="btn-pokemon-retro" style="margin-right:25px">
                    <span style="margin-left:20px">REVANCHA</span>
                </a>
                <a href="{{ route('battle.finish') }}" class="btn-pokemon-retro blue">
                    <span style="margin-left:20px">MENÚ</span>
                </a>
            </div>
        </div>
        @endif

        {{-- BATTLE LOG (collapsible) --}}
        <div class="battle-log-panel">
            <button class="log-toggle" onclick="document.getElementById('logContent').classList.toggle('d-none')">
                📜 LOG DE BATALLA
            </button>
            <div class="log-content d-none" id="logContent">
                @foreach (array_reverse($battle['log'] ?? []) as $logEntry)
                <div class="log-entry">
                    <span class="log-time">[{{ $logEntry['time'] }}]</span>
                    <span class="log-msg">{{ $logEntry['message'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/battle-arena.css') }}">
@endpush

@push('scripts')
<script>
    // ==========================================
    // BATTLE ARENA JAVASCRIPT — Dynamic Panels
    // ==========================================

    const battleMode = @json($battle['mode']);
    const csrfToken = '{{ csrf_token() }}';
    let pendingItemId = null;
    let isProcessing = false;

    // Store current battle state for dynamic panel rebuilding
    let battleState = {
        player: {
            pokemon: @json($battle['player_team'][$battle['player_active']]),
            team: @json($battle['player_team']),
            items: @json($battle['player_items'] ?? []),
            active_index: @json($battle['player_active'])
        },
        ai: {
            pokemon: @json($battle['ai_team'][$battle['ai_active']]),
            team: @json($battle['ai_team']),
            items: @json($battle['ai_items'] ?? []),
            active_index: @json($battle['ai_active'])
        },
        turn: @json($battle['turn'])
    };

    // Type color map for JS-side rendering
    const typeColors = {
        normal: '#A8A878', fire: '#F08030', water: '#6890F0', grass: '#78C850',
        electric: '#F8D030', ice: '#98D8D8', fighting: '#C03028', poison: '#A040A0',
        ground: '#E0C068', flying: '#A890F0', psychic: '#F85888', bug: '#A8B820',
        rock: '#B8A038', ghost: '#705898', dragon: '#7038F8', dark: '#705848',
        steel: '#B8B8D0', fairy: '#EE99AC'
    };

    function getTypeColor(type) {
        return typeColors[(type || 'normal').toLowerCase()] || '#777777';
    }

    // --- Panel Navigation ---
    function showPanel(panel, side) {
        const panels = ['menuButtons', 'movesPanel', 'itemsPanel', 'switchPanel', 'itemTargetPanel'];
        panels.forEach(p => {
            const el = document.getElementById(p + '_' + side);
            if (el) el.classList.add('d-none');
        });

        if (panel === 'main') {
            document.getElementById('menuButtons_' + side)?.classList.remove('d-none');
        } else {
            document.getElementById(panel + 'Panel_' + side)?.classList.remove('d-none');
        }
    }

    // --- Use Item (show target selection) ---
    function useItem(itemId, side) {
        pendingItemId = itemId;
        showPanel('itemTarget', side);
    }

    function selectItemTarget(targetIndex, side) {
        if (pendingItemId === null) return;
        performAction('item', { item_id: pendingItemId, target: targetIndex }, side);
        pendingItemId = null;
    }

    function cancelItemSelection(side) {
        pendingItemId = null;
        showPanel('items', side);
    }

    // ==========================================
    // DYNAMIC PANEL REBUILDING
    // ==========================================

    function rebuildSidePanels(side) {
        const sideData = battleState[side === 'ai' ? 'ai' : 'player'];
        const activeIdx = sideData.active_index;
        const team = sideData.team;
        const items = sideData.items || [];
        // Use sideData.pokemon which always has full move data with current PP
        const activePokemon = sideData.pokemon || team[activeIdx];

        rebuildMovesPanel(side, activePokemon);
        rebuildItemsPanel(side, items);
        rebuildItemTargetPanel(side, team);
        rebuildSwitchPanel(side, team, activeIdx);
    }

    function rebuildMovesPanel(side, pokemon) {
        const panel = document.getElementById('movesPanel_' + side);
        if (!panel) return;

        const moves = pokemon.moves || [];
        const grid = panel.querySelector('.moves-grid');
        if (!grid) return;

        let html = '';
        if (moves.length === 0) {
            html = `<button class="move-btn" style="--move-color: #a8a878; grid-column: span 2"
                        onclick="performAction('move', {move_index: -1}, '${side}')">
                        <span class="move-name">FORCEJEO</span>
                        <span class="move-meta"><span class="move-pp">—</span></span>
                    </button>`;
        } else {
            // Check if any move has PP
            const hasAnyPP = moves.some(m => (m.current_pp || 0) > 0);

            moves.forEach((move, idx) => {
                const moveColor = getTypeColor(move.type);
                const hasPP = (move.current_pp || 0) > 0;
                const moveName = (move.name_es || move.name || '').toUpperCase();
                const moveType = (move.type || 'NORMAL').toUpperCase();

                html += `<button class="move-btn ${!hasPP ? 'no-pp' : ''}" style="--move-color: ${moveColor}"
                            ${!hasPP ? 'disabled' : ''}
                            onclick="performAction('move', {move_index: ${idx}}, '${side}')">
                            <span class="move-name">${moveName}</span>
                            <span class="move-meta">
                                <span class="move-type-tag" style="background:${moveColor}">${moveType}</span>
                                <span class="move-pp">PP ${move.current_pp || 0}/${move.pp || 0}</span>
                            </span>
                        </button>`;
            });

            // Add Struggle button if no PP left on any move
            if (!hasAnyPP) {
                html += `<button class="move-btn" style="--move-color: #a8a878; grid-column: span 2"
                            onclick="performAction('move', {move_index: -1}, '${side}')">
                            <span class="move-name">FORCEJEO</span>
                            <span class="move-meta"><span class="move-pp">—</span></span>
                        </button>`;
            }
        }

        grid.innerHTML = html;
    }

    function rebuildItemsPanel(side, items) {
        const panel = document.getElementById('itemsPanel_' + side);
        if (!panel) return;

        const backBtn = panel.querySelector('.back-btn');
        const availableItems = items.filter(i => (i.quantity || 0) > 0);

        let html = '';
        if (availableItems.length === 0) {
            html = '<div class="empty-panel-msg">No hay objetos disponibles.</div>';
        } else {
            html = '<div class="items-list">';
            availableItems.forEach(item => {
                html += `<button class="item-btn" onclick="useItem(${item.id}, '${side}')">
                            <img src="${item.sprite || ''}" alt="${item.name}" class="item-sprite">
                            <span class="item-name">${item.name}</span>
                            <span class="item-qty">x${item.quantity}</span>
                        </button>`;
            });
            html += '</div>';
        }

        // Replace content before the back button
        const existingContent = panel.querySelector('.items-list') || panel.querySelector('.empty-panel-msg');
        if (existingContent) existingContent.remove();
        backBtn.insertAdjacentHTML('beforebegin', html);
    }

    function rebuildItemTargetPanel(side, team) {
        const panel = document.getElementById('itemTargetPanel_' + side);
        if (!panel) return;

        const switchList = panel.querySelector('.switch-list');
        if (!switchList) return;

        let html = '';
        team.forEach((pokemon, index) => {
            const maxHp = pokemon.max_hp || 1;
            const isFainted = pokemon.current_hp <= 0;
            html += `<button class="switch-btn" onclick="selectItemTarget(${index}, '${side}')">
                        <img src="${pokemon.image}" class="switch-sprite ${isFainted ? 'fainted-sprite' : ''}">
                        <div class="switch-info">
                            <span class="switch-name">${pokemon.name}</span>
                            <span class="switch-hp">${pokemon.current_hp}/${maxHp} PS</span>
                        </div>
                    </button>`;
        });

        switchList.innerHTML = html;
    }

    function rebuildSwitchPanel(side, team, activeIdx) {
        const panel = document.getElementById('switchPanel_' + side);
        if (!panel) return;

        const switchList = panel.querySelector('.switch-list');
        if (!switchList) return;

        let html = '';
        team.forEach((pokemon, index) => {
            // Show Pokémon that are NOT currently active AND have HP > 0
            if (index !== activeIdx && pokemon.current_hp > 0) {
                const maxHp = pokemon.max_hp || 1;
                html += `<button class="switch-btn" onclick="performAction('switch', {target: ${index}}, '${side}')">
                            <img src="${pokemon.image}" class="switch-sprite">
                            <div class="switch-info">
                                <span class="switch-name">${pokemon.name}</span>
                                <span class="switch-hp">${pokemon.current_hp}/${maxHp} PS</span>
                            </div>
                        </button>`;
            }
        });

        if (html === '') {
            html = '<div class="empty-panel-msg">No hay Pokémon disponibles para el cambio.</div>';
        }

        switchList.innerHTML = html;
    }

    // --- Perform Action (AJAX) ---
    function performAction(action, data, side) {
        if (isProcessing) return;
        isProcessing = true;
        if (window.showLoadingScreen) window.showLoadingScreen();

        const msgBox = document.getElementById('messageText');
        if (msgBox) msgBox.textContent = 'Procesando...';

        const body = { action, side, ...data, _token: csrfToken };

        fetch('{{ route("battle.action") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body)
        })
            .then(r => r.json())
            .then(data => {
                if (window.hideLoadingScreen) window.hideLoadingScreen();
                if (data.error) {
                    if (msgBox) msgBox.textContent = data.error;
                    isProcessing = false;
                    showPanel('main', side);
                    return;
                }

                // Play attack animation FIRST, then show messages and update UI
                const animPromise = (data.animation && data.animation.type === 'attack')
                    ? playAttackAnimation(data.animation.attacker, data.animation.move_type)
                    : Promise.resolve();

                animPromise.then(() => {
                    showMessages(data.messages || [], () => {
                        updateBattleUI(data);
                        isProcessing = false;

                        if (battleMode === 'ai' && data.turn === 'ai' && !data.winner) {
                            setTimeout(triggerAIAction, 1000);
                        }
                    });
                });
            })
            .catch(err => {
                if (window.hideLoadingScreen) window.hideLoadingScreen();
                console.error('Battle error:', err);
                if (msgBox) msgBox.textContent = 'Error de conexión. Intenta de nuevo.';
                isProcessing = false;
                showPanel('main', side);
            });
    }

    // --- Show messages sequentially ---
    function showMessages(messages, callback) {
        const msgBox = document.getElementById('messageText');
        if (!messages.length || !msgBox) { callback(); return; }

        let i = 0;
        function showNext() {
            if (i >= messages.length) { callback(); return; }
            msgBox.textContent = messages[i];

            // Add log entry
            const logContent = document.getElementById('logContent');
            if (logContent) {
                const now = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const entry = document.createElement('div');
                entry.className = 'log-entry';
                entry.innerHTML = `<span class="log-time">[${now}]</span><span class="log-msg">${messages[i]}</span>`;
                logContent.prepend(entry);
            }

            i++;
            setTimeout(showNext, 1200);
        }
        showNext();
    }

    // ==========================================
    // ANIMATION ENGINE
    // ==========================================

    const TYPE_COLORS = {
        fire: '#ff6600', water: '#4488ff', electric: '#ffcc00', grass: '#44bb22',
        ice: '#66ccff', fighting: '#cc4400', poison: '#9933cc', ground: '#aa8844',
        flying: '#8899ff', psychic: '#ff55aa', bug: '#88aa00', rock: '#aa8855',
        ghost: '#664499', dragon: '#7733cc', dark: '#444444', steel: '#99aabb',
        fairy: '#ff88cc', normal: '#aaaaaa'
    };

    function playAttackAnimation(attacker, moveType) {
        return new Promise(resolve => {
            const attackerSide = (attacker === 'player') ? 'player' : 'ai';
            const targetSide = (attacker === 'player') ? 'ai' : 'player';
            const attackerSprite = document.getElementById(attackerSide + 'Sprite');
            const targetSprite = document.getElementById(targetSide + 'Sprite');
            const battlefield = document.querySelector('.pokemon-battlefield');

            if (!attackerSprite || !targetSprite) { resolve(); return; }

            // Step 1: Attacker lunges (350ms)
            attackerSprite.style.animation = 'none';
            attackerSprite.offsetHeight;
            attackerSprite.classList.add('lunge-' + attackerSide);

            // Step 2: At peak of lunge, VFX on target
            setTimeout(() => {
                const flash = document.getElementById('vfxFlash');
                if (flash) {
                    flash.style.background = TYPE_COLORS[moveType] || '#fff';
                    flash.classList.add('vfx-screen-flash');
                    setTimeout(() => flash.classList.remove('vfx-screen-flash'), 300);
                }

                if (moveType === 'ground' && battlefield) {
                    battlefield.classList.add('screen-shake');
                    setTimeout(() => battlefield.classList.remove('screen-shake'), 400);
                }

                spawnTypeParticles(targetSprite, moveType);
                targetSprite.classList.add('hit-flash');
                setTimeout(() => targetSprite.classList.add('damage-shake'), 100);
            }, 150);

            // Step 3: Clean up (~850ms total)
            setTimeout(() => {
                attackerSprite.classList.remove('lunge-' + attackerSide);
                targetSprite.classList.remove('hit-flash', 'damage-shake');
                attackerSprite.style.animation = '';
                const particles = document.getElementById('vfxParticles');
                if (particles) particles.innerHTML = '';
                resolve();
            }, 850);
        });
    }

    function spawnTypeParticles(targetSprite, moveType) {
        const particles = document.getElementById('vfxParticles');
        if (!particles || !targetSprite) return;

        const bf = document.querySelector('.pokemon-battlefield');
        const bfRect = bf.getBoundingClientRect();
        const spriteRect = targetSprite.getBoundingClientRect();
        const cx = spriteRect.left - bfRect.left + spriteRect.width / 2;
        const cy = spriteRect.top - bfRect.top + spriteRect.height / 2;

        const container = document.createElement('div');
        container.className = `vfx-${moveType}`;
        container.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;';

        for (let i = 0; i < 12; i++) {
            const p = document.createElement('div');
            p.className = 'vfx-particle';
            const size = 8 + Math.random() * 16;
            const angle = (360 / 12) * i + (Math.random() * 30 - 15);
            const dist = 20 + Math.random() * 40;
            const px = cx + Math.cos(angle * Math.PI / 180) * dist - size / 2;
            const py = cy + Math.sin(angle * Math.PI / 180) * dist - size / 2;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.left = px + 'px';
            p.style.top = py + 'px';
            p.style.setProperty('--angle', angle + 'deg');
            p.style.animationDelay = (Math.random() * 0.15) + 's';
            container.appendChild(p);
        }
        particles.appendChild(container);
    }

    function playSwitchAnimation(side) {
        const sprite = document.getElementById(side + 'Sprite');
        if (!sprite) return;
        sprite.style.animation = 'none';
        sprite.offsetHeight;
        sprite.classList.add('switch-entrance');
        setTimeout(() => {
            sprite.classList.remove('switch-entrance');
            sprite.style.animation = '';
        }, 650);
    }

    function animateHpBar(side, fromPct, toPct) {
        const hpBar = document.getElementById(side + 'HpBar');
        if (!hpBar) return;
        hpBar.style.transition = 'width 0.8s ease-out';
        hpBar.style.width = toPct + '%';
        hpBar.className = 'hp-bar-fill' + (toPct <= 20 ? ' critical' : (toPct <= 50 ? ' warning' : ''));

        const hpText = document.getElementById(side + 'HpText');
        if (hpText) {
            const parts = hpText.textContent.split('/');
            const maxHp = parseInt(parts[1]?.trim()) || 1;
            const fromHp = Math.round(fromPct * maxHp / 100);
            const toHp = Math.round(toPct * maxHp / 100);
            if (fromHp !== toHp) {
                let current = fromHp;
                const step = fromHp > toHp ? -1 : 1;
                const interval = Math.max(20, 600 / Math.abs(fromHp - toHp));
                const counter = setInterval(() => {
                    current += step;
                    hpText.textContent = current + ' / ' + maxHp;
                    if (current === toHp) clearInterval(counter);
                }, interval);
            }
        }
    }

    function getHpPct(side) {
        const bar = document.getElementById(side + 'HpBar');
        if (!bar) return 100;
        return parseFloat(bar.style.width) || 0;
    }

    // --- Update UI from JSON response ---
    function updateBattleUI(data) {
        if (data.winner) {
            setTimeout(() => location.reload(), 500);
            return;
        }

        const oldPlayerHpPct = getHpPct('player');
        const oldAiHpPct = getHpPct('ai');
        let playerSwitched = false, aiSwitched = false;

        if (data.player) {
            const oldImage = battleState.player?.pokemon?.image;
            battleState.player = {
                pokemon: data.player.pokemon,
                team: data.player.team,
                items: data.player.items || battleState.player.items,
                active_index: data.player.active_index
            };
            playerSwitched = oldImage && oldImage !== data.player.pokemon.image;
            updatePokemonDisplay('player', data.player.pokemon, data.player.team);
        }

        if (data.ai) {
            const oldImage = battleState.ai?.pokemon?.image;
            battleState.ai = {
                pokemon: data.ai.pokemon,
                team: data.ai.team,
                items: data.ai.items || battleState.ai.items,
                active_index: data.ai.active_index
            };
            aiSwitched = oldImage && oldImage !== data.ai.pokemon.image;
            updatePokemonDisplay('ai', data.ai.pokemon, data.ai.team);
        }

        // Animate HP changes
        const newPlayerHpPct = getHpPct('player');
        const newAiHpPct = getHpPct('ai');
        if (oldPlayerHpPct !== newPlayerHpPct) animateHpBar('player', oldPlayerHpPct, newPlayerHpPct);
        if (oldAiHpPct !== newAiHpPct) animateHpBar('ai', oldAiHpPct, newAiHpPct);

        // Switch-in animation
        if (playerSwitched) playSwitchAnimation('player');
        if (aiSwitched) playSwitchAnimation('ai');

        // Faint animation
        if (data.player?.pokemon?.current_hp <= 0) {
            const s = document.getElementById('playerSprite');
            if (s) s.classList.add('faint-anim');
        }
        if (data.ai?.pokemon?.current_hp <= 0) {
            const s = document.getElementById('aiSprite');
            if (s) s.classList.add('faint-anim');
        }

        battleState.turn = data.turn;
        updateTurnState(data.turn);

        const sides = battleMode === 'local' ? ['player', 'ai'] : ['player'];
        sides.forEach(s => {
            rebuildSidePanels(s);
            showPanel('main', s);
        });

        const msgBox = document.getElementById('messageText');
        if (msgBox) {
            if (battleMode === 'local') {
                const turnSide = battleState[data.turn];
                const turnPokemon = turnSide.team[turnSide.active_index];
                const label = data.turn === 'player' ? 'J1' : 'J2';
                msgBox.textContent = `${label}: ¿Qué debería hacer ${turnPokemon.name}?`;
            } else if (data.player) {
                msgBox.textContent = `¿Qué debería hacer ${data.player.pokemon.name}?`;
            }
        }
    }

    function updatePokemonDisplay(side, pokemon, team) {
        // Update sprite
        const sprite = document.getElementById(side + 'Sprite');
        if (sprite) {
            const oldSrc = sprite.src;
            sprite.src = pokemon.image;
            if (oldSrc !== pokemon.image) {
                sprite.classList.remove('faint-anim');
                sprite.style.opacity = '1';
                sprite.style.transform = '';
                sprite.style.animation = 'none';
                sprite.offsetHeight;
                sprite.style.animation = '';
            }
            // Hit effect
            if (pokemon.current_hp < parseInt(sprite.dataset.lastHp || 9999)) {
                sprite.classList.add('hit-flash');
                setTimeout(() => sprite.classList.remove('hit-flash'), 300);
            }
            sprite.dataset.lastHp = pokemon.current_hp;
        }

        // Update name
        const nameEl = document.getElementById(side + 'Name');
        if (nameEl) nameEl.textContent = pokemon.name.toUpperCase();

        // Update level
        const levelEl = document.getElementById(side + 'Level');
        if (levelEl) levelEl.textContent = 'Nv' + (pokemon.level || 50);

        // Update HP bar
        const maxHp = pokemon.max_hp || 1;
        const hpPct = Math.round((pokemon.current_hp / maxHp) * 100);
        const hpBar = document.getElementById(side + 'HpBar');
        if (hpBar) {
            hpBar.style.width = hpPct + '%';
            hpBar.className = 'hp-bar-fill' + (hpPct <= 20 ? ' critical' : (hpPct <= 50 ? ' warning' : ''));
        }

        // Update HP text (player only)
        const hpText = document.getElementById(side + 'HpText');
        if (hpText) hpText.textContent = pokemon.current_hp + ' / ' + maxHp;

        // Update team pokeballs
        if (team) {
            const balls = document.getElementById(side + 'TeamBalls');
            if (balls) {
                balls.innerHTML = team.map(t =>
                    `<div class="mini-pokeball ${t.current_hp <= 0 ? 'fainted' : ''}"></div>`
                ).join('');
            }
        }
    }

    function updateTurnState(turn) {
        if (battleMode !== 'local') return;
        ['player', 'ai'].forEach(side => {
            const ctrl = document.getElementById('sideControls_' + side);
            if (ctrl) {
                ctrl.classList.toggle('waiting-turn', side !== turn);
                const badge = ctrl.querySelector('.waiting-badge');
                if (badge) badge.style.display = (side !== turn) ? 'inline' : 'none';
            }
        });
    }

    // --- AI Action ---
    function triggerAIAction() {
        if (isProcessing) return; // Prevent overlapping actions
        isProcessing = true;

        if (window.showLoadingScreen) window.showLoadingScreen();
        const msgBox = document.getElementById('messageText');
        if (msgBox) msgBox.textContent = 'La IA está pensando...';

        fetch('{{ route("battle.ai-action") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ _token: csrfToken })
        })
            .then(r => r.json())
            .then(data => {
                if (window.hideLoadingScreen) window.hideLoadingScreen();
                if (data.error) {
                    if (msgBox) msgBox.textContent = data.error;
                    isProcessing = false;
                    return;
                }
                showMessages(data.messages || [], () => {
                    updateBattleUI(data);
                    isProcessing = false;
                });
            })
            .catch(err => {
                if (window.hideLoadingScreen) window.hideLoadingScreen();
                console.error('AI action error:', err);
                if (msgBox) msgBox.textContent = 'Error de IA. Recargando...';
                isProcessing = false;
                setTimeout(() => location.reload(), 2000);
            });
    }

    // On page load: if AI mode and it's AI turn, trigger AI
    document.addEventListener('DOMContentLoaded', function () {
        @if (!($battle['winner'] ?? false))
            @if ($battle['mode'] == 'ai' && $battle['turn'] == 'ai')
            setTimeout(triggerAIAction, 1000);
        @endif
        @endif
    });
</script>
@endpush