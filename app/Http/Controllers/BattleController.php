<?php

namespace App\Http\Controllers;

use App\Services\BattleService;
use App\Services\AIService;
use App\Services\StatusEffectService;
use App\Services\ItemService;
use App\Helpers\PokemonHelper;
use Illuminate\Http\Request;

class BattleController extends Controller
{
    protected $battleService;
    protected $aiService;
    protected $statusService;
    protected $itemService;

    public function __construct(BattleService $battleService, AIService $aiService, StatusEffectService $statusService, ItemService $itemService)
    {
        $this->battleService = $battleService;
        $this->aiService = $aiService;
        $this->statusService = $statusService;
        $this->itemService = $itemService;
    }

    /**
     * Página de selección de modo de batalla
     */
    public function selectMode()
    {
        return view('battle.select-mode');
    }

    /**
     * Configurar batalla contra IA
     */
    public function setupAI(Request $request)
    {
        $team = session('team', []);

        if (count($team) < 1) {
            return redirect()->route('team.index')
                ->with('error', 'Necesitas al menos 1 Pokémon en tu equipo');
        }

        return view('battle.setup-ai', [
            'team' => $this->loadTeam($team),
            'difficulties' => ['easy' => 'Fácil', 'normal' => 'Normal', 'hard' => 'Difícil'],
            'items' => $this->itemService->getAvailableItems(),
        ]);
    }

    /**
     * Configurar batalla de 2 jugadores
     */
    public function setupMultiplayer()
    {
        $team = session('team', []);

        if (count($team) < 1) {
            return redirect()->route('team.index')
                ->with('error', 'Necesitas al menos 1 Pokémon en tu equipo');
        }

        return view('battle.setup-multiplayer', [
            'team' => $this->loadTeam($team),
            'available_pokemon' => PokemonHelper::getSimplePokemonList()
        ]);
    }

    /**
     * Iniciar batalla local (Multijugador)
     */
    public function startMultiplayerBattle(Request $request)
    {
        $request->validate([
            'team_size' => 'required|integer|min:1|max:6',
            'level' => 'nullable|integer|min:1|max:100',
        ]);

        $level = $request->level ?? 50;

        // Jugador 1: Equipo de la sesión
        $playerTeamIds = array_slice(session('team', []), 0, $request->team_size);
        $playerTeam = [];
        foreach ($playerTeamIds as $id) {
            $pokemon = PokemonHelper::getPokemon($id);
            if ($pokemon) {
                $playerTeam[] = $this->battleService->preparePokemonForBattle($pokemon, $level);
            }
        }

        // Jugador 2: Equipo
        $aiTeam = [];
        if ($request->input('p2_mode') === 'manual' && $request->has('p2_pokemon')) {
            $p2Ids = $request->input('p2_pokemon');
            // Validar que sean correctos
            if (is_array($p2Ids) && count($p2Ids) == $request->team_size) {
                foreach ($p2Ids as $id) {
                    $pokemon = PokemonHelper::getPokemon($id);
                    if ($pokemon) {
                        $aiTeam[] = $this->battleService->preparePokemonForBattle($pokemon, $level);
                    }
                }
            }
        }

        // Si falló la manual o es aleatorio
        if (empty($aiTeam)) {
            $aiTeam = $this->battleService->generateRandomTeam($request->team_size, $level);
        }

        // Objetos "Starter Kit" para batalla local (Limitados)
        $starterKit = [
            1 => 2, // Poción (x2)
            2 => 1, // Superpoción (x1)
            11 => 1, // Revivir (x1)
            13 => 1, // Ataque X (x1)
        ];

        $sandboxItems = [];
        foreach ($starterKit as $id => $qty) {
            $item = $this->itemService->getItem($id);
            if ($item) {
                $sandboxItems[] = array_merge($item, ['quantity' => $qty]);
            }
        }

        $battleData = [
            'mode' => 'local',
            'difficulty' => 'normal', // Irrelevante en local
            'level' => $level,
            'player_team' => $playerTeam,
            'ai_team' => $aiTeam, // Representa al Jugador 2
            'player_active' => 0,
            'ai_active' => 0,
            'turn' => 'player', // Empieza Jugador 1
            'log' => [],
            'winner' => null,
            'player_items' => $sandboxItems,
            'ai_items' => $sandboxItems,
            'turn_number' => 1,
        ];

        session(['current_battle' => $battleData]);

        $this->addToLog("¡Comienza la batalla local! (Nivel {$level})");
        $this->addToLog("Jugador 1 vs Jugador 2");

        return redirect()->route('battle.arena');
    }

    /**
     * Iniciar batalla contra IA
     */
    public function startAIBattle(Request $request)
    {
        $request->validate([
            'difficulty' => 'required|in:easy,normal,hard',
            'team_size' => 'required|integer|min:1|max:6',
            'level' => 'nullable|integer|min:1|max:100',
        ]);

        $level = $request->level ?? 50;

        // Preparar equipo del jugador con stats de nivel y movimientos reales
        $playerTeamIds = array_slice(session('team', []), 0, $request->team_size);
        $playerTeam = [];
        foreach ($playerTeamIds as $id) {
            $pokemon = PokemonHelper::getPokemon($id);
            if ($pokemon) {
                $playerTeam[] = $this->battleService->preparePokemonForBattle($pokemon, $level);
            }
        }

        // Generar equipo de IA con movimientos reales y stats de nivel
        $aiTeam = $this->battleService->generateRandomTeam($request->team_size, $level);

        // Preparar objetos del jugador
        $playerItems = [];
        $selectedItems = $request->input('items', []);
        foreach ($selectedItems as $itemId) {
            $item = $this->itemService->getItem((int)$itemId);
            if ($item) {
                $playerItems[] = array_merge($item, ['quantity' => 1]);
            }
        }

        // Objetos de la IA (algunos según dificultad)
        $aiItems = [];
        if ($request->difficulty === 'normal') {
            $aiItems = [
                array_merge($this->itemService->getItem(1), ['quantity' => 2]), // Pociones
            ];
        }
        elseif ($request->difficulty === 'hard') {
            $aiItems = [
                array_merge($this->itemService->getItem(3), ['quantity' => 2]), // Hiperpociones
                array_merge($this->itemService->getItem(11), ['quantity' => 1]), // Revivir
            ];
        }

        $battleData = [
            'mode' => 'ai',
            'difficulty' => $request->difficulty,
            'level' => $level,
            'player_team' => $playerTeam,
            'ai_team' => $aiTeam,
            'player_active' => 0,
            'ai_active' => 0,
            'turn' => 'player',
            'log' => [],
            'winner' => null,
            'player_items' => $playerItems,
            'ai_items' => $aiItems,
            'turn_number' => 1,
        ];

        session(['current_battle' => $battleData]);

        // Primer mensaje del log
        $this->addToLog("¡Comienza la batalla! (Nivel {$level})");
        $this->addToLog("Tu {$playerTeam[0]['name']} se enfrenta a {$aiTeam[0]['name']} de la IA!");

        return redirect()->route('battle.arena');
    }

    /**
     * Arena de batalla
     */
    public function arena()
    {
        $battle = session('current_battle');

        if (!$battle) {
            return redirect()->route('battle.select-mode');
        }

        $p = $battle['player_team'][$battle['player_active']];
        $ai = $battle['ai_team'][$battle['ai_active']];

        $pMaxHp = $p['max_hp'] ?? $p['battle_stats']['hp'] ?? $p['stats']['hp'];
        $pHpPct = round(($p['current_hp'] / max($pMaxHp, 1)) * 100);
        $playerHpClass = $pHpPct <= 20 ? 'critical' : ($pHpPct <= 50 ? 'warning' : '');

        $aiMaxHp = $ai['max_hp'] ?? $ai['battle_stats']['hp'] ?? $ai['stats']['hp'];
        $aiHpPct = round(($ai['current_hp'] / max($aiMaxHp, 1)) * 100);
        $aiHpClass = $aiHpPct <= 20 ? 'critical' : ($aiHpPct <= 50 ? 'warning' : '');

        return view('battle.arena', [
            'battle' => $battle,
            'p' => $p,
            'ai' => $ai,
            'pMaxHp' => $pMaxHp,
            'pHpPct' => $pHpPct,
            'playerHpClass' => $playerHpClass,
            'aiMaxHp' => $aiMaxHp,
            'aiHpPct' => $aiHpPct,
            'aiHpClass' => $aiHpClass
        ]);
    }

    /**
     * Realizar acción del jugador
     */
    public function action(Request $request)
    {
        $battle = session('current_battle');

        if (!$battle || $battle['winner']) {
            return response()->json(['error' => 'Batalla no encontrada o finalizada']);
        }

        // Determinar quién actúa
        // En modo IA: siempre es 'player' (la IA usa aiAction)
        // En modo Local: puede ser 'player' o 'ai' (Jugador 2)

        $actor = $battle['turn'];

        if ($battle['mode'] === 'ai' && $actor !== 'player') {
            return response()->json(['error' => 'No es tu turno']);
        }

        $action = $request->action;

        $playerIdx = $battle['player_active'];
        $aiIdx = $battle['ai_active'];
        $messages = [];
        $animationData = null;

        // Definir equipos atacante y defensor según el turno
        if ($actor === 'player') {
            $attackerTeam = & $battle['player_team'];
            $defenderTeam = & $battle['ai_team'];
            $attackerIdx = $playerIdx;
            $defenderIdx = $aiIdx;
            $attackerName = $attackerTeam[$attackerIdx]['name'];
        }
        else {
            // Turno del Jugador 2 (usando keys de 'ai')
            $attackerTeam = & $battle['ai_team'];
            $defenderTeam = & $battle['player_team'];
            $attackerIdx = $aiIdx;
            $defenderIdx = $playerIdx;
            $attackerName = "J2 " . $attackerTeam[$attackerIdx]['name'];
        }

        // Procesar estado del ATACANTE al inicio de su turno
        $statusResult = $this->statusService->processStatusEffects($attackerTeam[$attackerIdx]);
        foreach ($statusResult['messages'] as $msg) {
            $messages[] = $msg;
        }

        // Verificar si se debilitó por estado
        if ($attackerTeam[$attackerIdx]['current_hp'] <= 0) {
            $messages[] = "¡{$attackerName} se ha debilitado por el estado alterado!";
            $attackerTeam[$attackerIdx]['current_hp'] = 0;

            // Buscar siguiente para el atacante
            $nextPokemon = $this->findNextAvailablePokemon($attackerTeam, $attackerIdx);
            if ($nextPokemon !== false) {
                if ($actor === 'player')
                    $battle['player_active'] = $nextPokemon;
                else
                    $battle['ai_active'] = $nextPokemon;

                $messages[] = "¡Envías a {$attackerTeam[$nextPokemon]['name']}!";
            }

            // Verificar si perdió
            if ($this->battleService->checkVictory($attackerTeam)) {
                $battle['winner'] = ($actor === 'player') ? 'ai' : 'player';
                $messages[] = "¡" . ($actor === 'player' ? "Jugador 2" : "Jugador 1") . " ha ganado!";
                $this->saveBattleState($battle, $messages);
                return $this->battleJsonResponse($battle, $messages, 'status_damage');
            }

            // Cambiar turno
            $battle['turn'] = ($actor === 'player') ? 'ai' : 'player';
            $this->saveBattleState($battle, $messages);
            return $this->battleJsonResponse($battle, $messages, 'status_damage');
        }

        // Si no puede actuar
        if (!$statusResult['can_act'] && $action !== 'switch') {
            $battle['turn'] = ($actor === 'player') ? 'ai' : 'player';
            $this->saveBattleState($battle, $messages);
            return $this->battleJsonResponse($battle, $messages, 'cant_act');
        }

        $action = $request->action;

        switch ($action) {
            case 'move':
                $moveIndex = (int)($request->move_index ?? 0);
                $atkPokemon = & $attackerTeam[$attackerIdx];
                $defPokemon = & $defenderTeam[$defenderIdx];

                // Determinar movimiento a usar
                if ($moveIndex == -1 || empty($atkPokemon['moves'])) {
                    $move = $this->battleService->getStruggleMove();
                    $messages[] = "¡{$attackerName} no tiene movimientos! Usa Forcejeo.";
                }
                else {
                    $moves = $atkPokemon['moves'];
                    // Verificar si tiene PP disponibles
                    $hasAnyPP = false;
                    foreach ($moves as $m) {
                        if (($m['current_pp'] ?? 0) > 0) {
                            $hasAnyPP = true;
                            break;
                        }
                    }

                    if (!$hasAnyPP) {
                        $move = $this->battleService->getStruggleMove();
                        $messages[] = "¡{$attackerName} no tiene PP! Usa Forcejeo.";
                    }
                    elseif (!isset($moves[$moveIndex]) || ($moves[$moveIndex]['current_pp'] ?? 0) <= 0) {
                        return response()->json(['error' => 'Ese movimiento no tiene PP disponible.']);
                    }
                    else {
                        $move = $moves[$moveIndex];
                        // Decrementar PP
                        $attackerTeam[$attackerIdx]['moves'][$moveIndex]['current_pp']--;
                    }
                }

                // Track animation data
                $animationData = [
                    'type' => 'attack',
                    'attacker' => $actor,
                    'move_type' => $move['type'] ?? 'normal',
                    'move_name' => $move['name_es'] ?? ucfirst(str_replace('-', ' ', $move['name'])),
                    'damage_class' => $move['damage_class'] ?? 'physical',
                ];

                // Calcular daño
                $level = $battle['level'] ?? 50;
                $result = $this->battleService->calculateDamage($atkPokemon, $defPokemon, $move, $level);
                $moveName = $move['name_es'] ?? ucfirst(str_replace('-', ' ', $move['name']));

                if ($result['missed']) {
                    $messages[] = "¡{$attackerName} usó {$moveName}, pero falló!";
                }
                else {
                    $defenderTeam[$defenderIdx]['current_hp'] = max(0, $defPokemon['current_hp'] - $result['damage']);
                    $messages[] = "¡{$attackerName} usó {$moveName}! (-{$result['damage']} HP)";

                    if ($result['critical'])
                        $messages[] = "¡Golpe crítico!";
                    if ($eff = $this->battleService->getEffectivenessMessage($result['effectiveness']))
                        $messages[] = $eff;

                    // Intentar aplicar efecto de estado del movimiento
                    if (!empty($move['status_effect']) && $defenderTeam[$defenderIdx]['current_hp'] > 0) {
                        $statusChance = $move['status_chance'] ?? 0;
                        if ($statusChance > 0) {
                            $sRes = $this->statusService->applyStatus(
                                $defenderTeam[$defenderIdx],
                                $move['status_effect'],
                                $statusChance
                            );
                            if ($sRes['applied'] && $sRes['message']) {
                                $messages[] = $sRes['message'];
                            }
                        }
                    }

                    // Daño de retroceso por Forcejeo
                    if (!empty($move['is_struggle'])) {
                        $recoil = max(1, floor($result['damage'] / 4));
                        $attackerTeam[$attackerIdx]['current_hp'] = max(0, $atkPokemon['current_hp'] - $recoil);
                        $messages[] = "¡{$attackerName} recibe {$recoil} de daño de retroceso!";
                    }
                }

                // Verificar si el atacante se debilitó (por retroceso)
                if ($attackerTeam[$attackerIdx]['current_hp'] <= 0) {
                    $attackerTeam[$attackerIdx]['current_hp'] = 0;
                    $messages[] = "¡{$attackerName} se ha debilitado por el retroceso!";

                    if ($this->battleService->checkVictory($attackerTeam)) {
                        $battle['winner'] = ($actor === 'player') ? 'ai' : 'player';
                        $winMsg = ($actor === 'player') ? "Jugador 2/IA" : "Jugador 1";
                        if ($battle['mode'] === 'ai')
                            $winMsg = "La IA";
                        $messages[] = "¡{$winMsg} ha ganado!";
                        $this->saveBattleState($battle, $messages);
                        return $this->battleJsonResponse($battle, $messages, $action);
                    }

                    $nextAtk = $this->findNextAvailablePokemon($attackerTeam, $attackerIdx);
                    if ($nextAtk !== false) {
                        if ($actor === 'player')
                            $battle['player_active'] = $nextAtk;
                        else
                            $battle['ai_active'] = $nextAtk;
                        $messages[] = "¡Envías a {$attackerTeam[$nextAtk]['name']}!";
                    }
                }

                // Verificar si el Pokémon defensor se debilitó
                if ($defenderTeam[$defenderIdx]['current_hp'] <= 0) {
                    $messages[] = "¡{$defPokemon['name']} se ha debilitado!";
                    $nextDef = $this->findNextAvailablePokemon($defenderTeam, $defenderIdx);
                    if ($nextDef !== false) {
                        if ($actor === 'player')
                            $battle['ai_active'] = $nextDef;
                        else
                            $battle['player_active'] = $nextDef;
                        $messages[] = ($actor === 'player' ? "Jugador 2" : "Jugador 1") . " envía a {$defenderTeam[$nextDef]['name']}!";
                    }
                }
                break;

            case 'item':
                $itemId = $request->item_id;
                $targetIndex = $request->target ?? $request->target_index ?? $attackerIdx;
                $targetPokemon = & $attackerTeam[$targetIndex];

                // Buscar el item en el inventario del jugador
                $itemFound = false;
                $itemsArray = ($actor === 'player') ? 'player_items' : 'ai_items';
                foreach ($battle[$itemsArray] as $key => &$invItem) {
                    if ($invItem['id'] == $itemId && ($invItem['quantity'] ?? 0) > 0) {
                        if (!$this->itemService->canUseItem($itemId, $targetPokemon)) {
                            return response()->json(['error' => 'No se puede usar este objeto ahora.']);
                        }
                        $useResult = $this->itemService->useItem($itemId, $attackerTeam[$targetIndex]);
                        if ($useResult['success']) {
                            $invItem['quantity']--;
                            $messages[] = $useResult['message'];
                        }
                        else {
                            return response()->json(['error' => $useResult['message']]);
                        }
                        $itemFound = true;
                        break;
                    }
                }
                unset($invItem);

                if (!$itemFound) {
                    return response()->json(['error' => 'No tienes ese objeto.']);
                }
                break;

            case 'switch':
                $target = $request->target;
                if ($target >= 0 && $target < count($attackerTeam) &&
                $target != $attackerIdx &&
                $this->battleService->canContinue($attackerTeam[$target])) {
                    if ($actor === 'player')
                        $battle['player_active'] = $target;
                    else
                        $battle['ai_active'] = $target;
                    $messages[] = "¡{$attackerName} cambia a {$attackerTeam[$target]['name']}!";
                }
                else {
                    return response()->json(['error' => 'No puedes cambiar a ese Pokémon.']);
                }
                break;

            default:
                return response()->json(['error' => 'Acción no válida.']);
        }

        // Verificar victoria (si se debilitó todo el equipo defensor)
        if ($this->battleService->checkVictory($defenderTeam)) {
            $battle['winner'] = $actor; // El que atacó ganó
            $messages[] = "¡" . ($actor === 'player' ? "Jugador 1" : "Jugador 2") . " ha ganado!";
            $this->saveBattleState($battle, $messages);
            return $this->battleJsonResponse($battle, $messages, $action, $animationData);
        }

        // Pasar turno
        $battle['turn'] = ($actor === 'player') ? 'ai' : 'player';
        $this->saveBattleState($battle, $messages);

        return $this->battleJsonResponse($battle, $messages, $action, $animationData);
    }

    private function saveBattleState($battle, $messages)
    {
        foreach ($messages as $m)
            $this->addToLog($m);
        session(['current_battle' => $battle]);
    }

    /**
     * Acción de la IA
     */
    public function aiAction()
    {
        $battle = session('current_battle');

        if (!$battle || $battle['turn'] !== 'ai' || $battle['winner']) {
            return response()->json(['error' => 'No es el turno de la IA']);
        }

        $aiIdx = $battle['ai_active'];
        $playerIdx = $battle['player_active'];
        $messages = [];
        $animationData = null;

        // Procesar estado alterado del Pokémon de la IA
        $statusResult = $this->statusService->processStatusEffects($battle['ai_team'][$aiIdx]);
        foreach ($statusResult['messages'] as $msg) {
            $messages[] = $msg;
        }

        // Verificar si murió por daño de estado
        if ($battle['ai_team'][$aiIdx]['current_hp'] <= 0) {
            $messages[] = "¡{$battle['ai_team'][$aiIdx]['name']} se ha debilitado!";
            $battle['ai_team'][$aiIdx]['current_hp'] = 0;

            $nextAI = $this->findNextAvailablePokemon($battle['ai_team'], $aiIdx);
            if ($nextAI !== false) {
                $battle['ai_active'] = $nextAI;
                $messages[] = "La IA envía a {$battle['ai_team'][$nextAI]['name']}!";
            }

            if ($this->battleService->checkVictory($battle['ai_team'])) {
                $battle['winner'] = 'player';
                $messages[] = "¡Has ganado la batalla!";
                foreach ($messages as $m)
                    $this->addToLog($m);
                session(['current_battle' => $battle]);
                return $this->battleJsonResponse($battle, $messages, 'status_damage');
            }

            foreach ($messages as $m)
                $this->addToLog($m);
            $battle['turn'] = 'player';
            $battle['turn_number'] = ($battle['turn_number'] ?? 1) + 1;
            session(['current_battle' => $battle]);
            return $this->battleJsonResponse($battle, $messages, 'status_damage');
        }

        // Si el estado le impide actuar
        if (!$statusResult['can_act']) {
            foreach ($messages as $m)
                $this->addToLog($m);
            $battle['turn'] = 'player';
            $battle['turn_number'] = ($battle['turn_number'] ?? 1) + 1;
            session(['current_battle' => $battle]);
            return $this->battleJsonResponse($battle, $messages, 'cant_act');
        }

        // Obtener decisión de la IA
        $aiDecision = $this->aiService->decideAction(
            $battle['ai_team'],
            $battle['player_team'],
            $aiIdx,
            $playerIdx,
            $battle['difficulty'] ?? 'normal',
            $battle['ai_items'] ?? []
        );

        $aiPokemon = & $battle['ai_team'][$aiIdx];
        $playerPokemon = & $battle['player_team'][$playerIdx];

        switch ($aiDecision['action']) {
            case 'move':
                $moveIndex = (int)($aiDecision['move_index'] ?? 0);

                if ($moveIndex == -1 || empty($aiPokemon['moves'])) {
                    $move = $this->battleService->getStruggleMove();
                    $messages[] = "¡{$aiPokemon['name']} usa Forcejeo!";
                }
                else {
                    $moves = $aiPokemon['moves'];
                    if (isset($moves[$moveIndex]) && ($moves[$moveIndex]['current_pp'] ?? 0) > 0) {
                        $move = $moves[$moveIndex];
                        $battle['ai_team'][$aiIdx]['moves'][$moveIndex]['current_pp']--;
                    }
                    else {
                        $move = $this->battleService->getStruggleMove();
                        $messages[] = "¡{$aiPokemon['name']} usa Forcejeo!";
                    }
                }

                // Track animation data
                $animationData = [
                    'type' => 'attack',
                    'attacker' => 'ai',
                    'move_type' => $move['type'] ?? 'normal',
                    'move_name' => $move['name_es'] ?? ucfirst(str_replace('-', ' ', $move['name'])),
                    'damage_class' => $move['damage_class'] ?? 'physical',
                ];

                // Calcular daño
                $level = $battle['level'] ?? 50;
                $result = $this->battleService->calculateDamage($aiPokemon, $playerPokemon, $move, $level);
                $moveName = $move['name_es'] ?? ucfirst(str_replace('-', ' ', $move['name']));

                if ($result['missed']) {
                    $messages[] = "¡{$aiPokemon['name']} usó {$moveName}, pero falló!";
                }
                else {
                    if ($result['damage'] > 0) {
                        $battle['player_team'][$playerIdx]['current_hp'] = max(0, $playerPokemon['current_hp'] - $result['damage']);
                        $messages[] = "¡{$aiPokemon['name']} usó {$moveName}! (-{$result['damage']} HP)";

                        $effMsg = $this->battleService->getEffectivenessMessage($result['effectiveness']);
                        if ($effMsg)
                            $messages[] = $effMsg;

                        if ($result['critical']) {
                            $messages[] = "¡Golpe crítico!";
                        }
                    }
                    else {
                        $messages[] = "¡{$aiPokemon['name']} usó {$moveName}!";
                    }

                    // Aplicar efecto de estado
                    if (!empty($move['status_effect'])) {
                        if ($move['status_effect'] === 'heal') {
                            $maxHp = $aiPokemon['max_hp'] ?? $aiPokemon['battle_stats']['hp'];
                            $healAmount = floor($maxHp / 2);
                            $oldHp = $aiPokemon['current_hp'];
                            $battle['ai_team'][$aiIdx]['current_hp'] = min($maxHp, $oldHp + $healAmount);
                            $actualHeal = $battle['ai_team'][$aiIdx]['current_hp'] - $oldHp;
                            $messages[] = "¡{$aiPokemon['name']} recuperó salud! (+{$actualHeal} HP)";
                        }
                        elseif ($battle['player_team'][$playerIdx]['current_hp'] > 0) {
                            $statusChance = $move['status_chance'] ?? 0;
                            if ($statusChance > 0) {
                                $sResult = $this->statusService->applyStatus(
                                    $battle['player_team'][$playerIdx],
                                    $move['status_effect'],
                                    $statusChance
                                );
                                if ($sResult['applied'] && $sResult['message']) {
                                    $messages[] = $sResult['message'];
                                }
                            }
                        }
                    }

                    // Retroceso Forcejeo
                    if (!empty($move['is_struggle'])) {
                        $recoil = max(1, floor($result['damage'] / 4));
                        $battle['ai_team'][$aiIdx]['current_hp'] = max(0, $aiPokemon['current_hp'] - $recoil);
                        $messages[] = "¡{$aiPokemon['name']} recibe {$recoil} de daño de retroceso!";
                    }
                }

                // Verificar si la IA se debilitó (por retroceso)
                if ($battle['ai_team'][$aiIdx]['current_hp'] <= 0) {
                    $battle['ai_team'][$aiIdx]['current_hp'] = 0;
                    $messages[] = "¡{$aiPokemon['name']} se ha debilitado por el retroceso!";

                    if ($this->battleService->checkVictory($battle['ai_team'])) {
                        $battle['winner'] = 'player';
                        $messages[] = "¡Has ganado la batalla!";
                        $this->saveBattleState($battle, $messages);
                        return $this->battleJsonResponse($battle, $messages, $aiDecision['action']);
                    }

                    $nextAI = $this->findNextAvailablePokemon($battle['ai_team'], $aiIdx);
                    if ($nextAI !== false) {
                        $battle['ai_active'] = $nextAI;
                        $messages[] = "La IA envía a {$battle['ai_team'][$nextAI]['name']}!";
                    }
                }

                // Verificar si el Pokémon del jugador se debilitó
                if ($battle['player_team'][$playerIdx]['current_hp'] <= 0) {
                    $messages[] = "¡{$playerPokemon['name']} se ha debilitado!";

                    $nextPlayer = $this->findNextAvailablePokemon($battle['player_team'], $playerIdx);
                    if ($nextPlayer !== false) {
                        $battle['player_active'] = $nextPlayer;
                        $messages[] = "¡Envías a {$battle['player_team'][$nextPlayer]['name']}!";
                    }
                }
                break;

            case 'item':
                $itemId = $aiDecision['item_id'] ?? null;
                $targetIndex = $aiDecision['target_index'] ?? $aiIdx;

                if ($itemId && isset($battle['ai_items'])) {
                    foreach ($battle['ai_items'] as $key => &$invItem) {
                        if ($invItem['id'] == $itemId && ($invItem['quantity'] ?? 0) > 0) {
                            $useResult = $this->itemService->useItem($itemId, $battle['ai_team'][$targetIndex]);
                            if ($useResult['success']) {
                                $invItem['quantity']--;
                                $messages[] = "La IA: " . $useResult['message'];
                            }
                            break;
                        }
                    }
                    unset($invItem);
                }
                break;

            case 'switch':
                $target = $aiDecision['target'] ?? 0;
                if ($target >= 0 && $target < count($battle['ai_team']) &&
                $target != $aiIdx &&
                $this->battleService->canContinue($battle['ai_team'][$target])) {
                    $battle['ai_active'] = $target;
                    $messages[] = "La IA cambia a {$battle['ai_team'][$target]['name']}!";
                }
                break;
        }

        // Verificar victoria de la IA
        if ($this->battleService->checkVictory($battle['player_team'])) {
            $battle['winner'] = 'ai';
            $messages[] = "¡La IA ha ganado la batalla!";
            foreach ($messages as $m)
                $this->addToLog($m);
            session(['current_battle' => $battle]);
            return $this->battleJsonResponse($battle, $messages, $aiDecision['action']);
        }

        // Volver al turno del jugador
        $battle['turn'] = 'player';
        $battle['turn_number'] = ($battle['turn_number'] ?? 1) + 1;
        foreach ($messages as $m)
            $this->addToLog($m);
        session(['current_battle' => $battle]);

        return $this->battleJsonResponse($battle, $messages, $aiDecision['action']);
    }

    /**
     * Finalizar batalla
     */
    public function finish()
    {
        session()->forget('current_battle');
        return redirect()->route('battle.select-mode')
            ->with('success', 'Batalla finalizada');
    }

    /**
     * Generar respuesta JSON completa con el estado de batalla
     */
    private function battleJsonResponse($battle, $messages, $action, $animationData = null)
    {
        $playerIdx = $battle['player_active'];
        $aiIdx = $battle['ai_active'];
        $playerPokemon = $battle['player_team'][$playerIdx];
        $aiPokemon = $battle['ai_team'][$aiIdx];

        return response()->json([
            'success' => true,
            'action' => $action,
            'messages' => $messages,
            'winner' => $battle['winner'],
            'turn' => $battle['turn'],
            'turn_number' => $battle['turn_number'] ?? 1,
            'animation' => $animationData,
            'player' => [
                'active_index' => $playerIdx,
                'pokemon' => [
                    'name' => $playerPokemon['name'],
                    'id' => $playerPokemon['id'],
                    'image' => $playerPokemon['image'],
                    'current_hp' => $playerPokemon['current_hp'],
                    'max_hp' => $playerPokemon['max_hp'],
                    'status' => $playerPokemon['status'] ?? null,
                    'types' => $playerPokemon['types'] ?? [],
                    'moves' => $playerPokemon['moves'] ?? [],
                    'level' => $playerPokemon['level'] ?? 50,
                ],
                'team' => array_map(function ($p) {
            return [
                        'name' => $p['name'],
                        'id' => $p['id'],
                        'image' => $p['image'],
                        'current_hp' => $p['current_hp'],
                        'max_hp' => $p['max_hp'],
                        'status' => $p['status'] ?? null,
                        'types' => $p['types'] ?? [],
                        'moves' => $p['moves'] ?? [],
                        'level' => $p['level'] ?? 50,
                    ];
        }, $battle['player_team']),
                'items' => $battle['player_items'] ?? [],
            ],
            'ai' => [
                'active_index' => $aiIdx,
                'pokemon' => [
                    'name' => $aiPokemon['name'],
                    'id' => $aiPokemon['id'],
                    'image' => $aiPokemon['image'],
                    'current_hp' => $aiPokemon['current_hp'],
                    'max_hp' => $aiPokemon['max_hp'],
                    'status' => $aiPokemon['status'] ?? null,
                    'types' => $aiPokemon['types'] ?? [],
                    'level' => $aiPokemon['level'] ?? 50,
                ],
                'team' => array_map(function ($p) {
            return [
                        'name' => $p['name'],
                        'id' => $p['id'],
                        'image' => $p['image'],
                        'current_hp' => $p['current_hp'],
                        'max_hp' => $p['max_hp'],
                        'status' => $p['status'] ?? null,
                        'types' => $p['types'] ?? [],
                        'moves' => $p['moves'] ?? [],
                        'level' => $p['level'] ?? 50,
                    ];
        }, $battle['ai_team']),
            ],
            'log' => array_slice($battle['log'] ?? [], -10),
        ]);
    }

    /**
     * Cargar equipo completo con datos
     */
    private function loadTeam($teamIds)
    {
        $team = [];
        foreach ($teamIds as $id) {
            $pokemon = PokemonHelper::getPokemon($id);
            if ($pokemon) {
                $team[] = $pokemon;
            }
        }
        return $team;
    }

    /**
     * Añadir mensaje al log
     */
    private function addToLog($message)
    {
        $battle = session('current_battle', []);
        $battle['log'][] = [
            'message' => $message,
            'time' => now()->format('H:i:s')
        ];
        // Mantener solo los últimos 30 mensajes
        if (count($battle['log']) > 30) {
            $battle['log'] = array_slice($battle['log'], -30);
        }
        session(['current_battle' => $battle]);
    }

    /**
     * Encontrar siguiente Pokémon disponible
     */
    private function findNextAvailablePokemon($team, $currentIndex)
    {
        foreach ($team as $index => $pokemon) {
            if ($index !== $currentIndex && $this->battleService->canContinue($pokemon)) {
                return $index;
            }
        }
        return false;
    }
}