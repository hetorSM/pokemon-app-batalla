# Fallos y Mejoras Detectadas

Este documento detalla los problemas encontrados en el código y sugerencias de mejora.

## 1. Mantenibilidad del Código
- [ ] **BattleController Monolítico:** `BattleController` tiene más de 900 líneas y maneja lógica de negocio mezclada con control de flujo HTTP. Viola el principio de responsabilidad única.
- [ ] **Datos Hardcodeados:** `BattleService` (Tabla de Tipos), `ItemService` (Lista de Objetos) y `StatusEffectService` (Definiciones) contienen grandes arrays hardcodeados. Deberían moverse a base de datos o archivos de configuración para facilitar mantenimiento.
- [ ] **Complejidad en POkemonHelper:** `PokemonHelper` tiene demasiada responsabilidad (API, DB, Caché, Selección de Moves, Lógica de Evolución). Debería refactorizarse en servicios más pequeños o Repositorios.
- [ ] **Duplicación de Código:** Lógica de preparación de equipos duplicada en `startMultiplayerBattle` y `startAIBattle`.

## 2. Frontend y UI
- [ ] **Lógica en Vistas:** `arena.blade.php` contiene bloques `@php` con lógica de cálculo de porcentajes y estados. Esto debería pasarse desde el Controlador o un View Composer.
- [x] **CSS Embebido:** `arena.blade.php` tiene un bloque `<style>` de más de 300 líneas. Debería extraerse a un archivo CSS/SCSS dedicado o refactorizarse usando Tailwind CSS.
- [ ] **Strings Hardcodeados:** Textos en la interfaz (Blade y JS) no están internacionalizados (i18n), dificultando la traducción.

## 3. Estado y Datos
- [ ] **Abuso de Sesiones:** El estado de la batalla se almacena en un array gigante en la sesión (`current_battle`). Esto es frágil y difícil de tipar. Debería encapsularse en una clase `BattleState` o similar.
- [ ] **Transacciones Pesadas:** `PokemonHelper::getPokemon` realiza muchas operaciones de escritura en DB dentro de una transacción si hay cache miss, lo que podría causar timeouts.

## 4. Código Legado/Deprecado
- [ ] **Comentarios obsoletos:** `PokemonHelper.php` contiene comentarios sobre lógica antigua o "parches" ("Fallos para datos antiguos").
- [ ] **Números Mágicos:** Uso de `1025` como límite de Pokémon en `BattleService`.

---

## 5. Análisis del Agente y Propuestas de Implementación Segura

A continuación se clasifican los fallos según la viabilidad de reparación sin afectar la lógica o la API.

### ✅ SAFE TO IMPLEMENT (Seguro de Implementar)
Estas mejoras no cambian la lógica de negocio, solo la organización y limpieza. Son altamente recomendadas para empezar.

1.  **Extracción de CSS (COMPLETADO):**
    -   [x] *Problema:* `arena.blade.php` tiene >300 líneas de CSS.
    -   [x] *Solución:* Mover a `public/css/battle-arena.css` y vincular con `<link>`.
    -   [x] *Riesgo:* Mínimo (chequear ruta de assets).

2.  **Limpieza de Vistas (View Composers/Presenters) (PARCIAL):**
    -   [ ] *Problema:* Cálculos de % HP y clases CSS (warning/critical) dentro de `@php` en la vista.
    -   [ ] *Solución:* Calcular estos valores en el Controlador y pasarlos a la vista listos para usar, o usar un Presenter simple.
    -   *Riesgo:* Mínimo.

3.  **Configuración de Constantes (COMPLETADO):**
    -   [x] *Problema:* Arrays de colores de tipos, items iniciales y límites en `PokemonHelper` y `BattleController`.
    -   [x] *Solución:* Mover a archivos `config/pokemon.php` y `config/battle.php`.
    -   *Riesgo:* Mínimo.

4.  **Consistencia de Errores:**
    -   [ ] *Problema:* Algunos métodos devuelven `null` silenciosamente en `try-catch` (ej. `PokemonHelper`).
    -   [ ] *Solución:* Agregar Log::error con más contexto antes de retornar null.
    -   *Riesgo:* Nulo (mejora observabilidad).

### ⚠️ COMPLEX / RISKY (Requiere Refactorización Profunda)
Estas mejoras tocan el núcleo de la lógica. Se recomienda hacerlas en una rama separada o con tests exhaustivos.

1.  **Migración de Sesión a Base de Datos (`BattleState`):**
    -   *Beneficio:* Persistencia real, recuperación de partidas, APIs stateless.
    -   *Riesgo:* Alto. Rompería todas las batallas activas y requiere reescribir `BattleService` y `BattleController`.

2.  **Refactorización de `PokemonHelper`:**
    -   *Beneficio:* Testabilidad y claridad.
    -   *Riesgo:* Medio-Alto. Es usado en casi todos los controladores. Un cambio en la firma de `getPokemon` rompería todo.

3.  **Refactorización de `BattleController`:**
    -   *Beneficio:* Código más limpio.
    -   *Riesgo:* Medio. Al mover lógica a Servicios, hay que asegurar que el paso de parámetros (especialmente las referencias `&`) se mantenga correcto.

### 🚀 Plan de Acción Recomendado (Safe)

1.  Mover CSS de `arena.blade.php` a archivo externo.
2.  Mover arrays de configuración (Strings de tipos, colores) a archivos de config.
3.  Sanitizar `BattleController` extrayendo lógica visual (mensajes de log formateados) a un `BatllePresenter` o funciones privadas.
