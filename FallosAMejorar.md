# Fallos y Mejoras Detectadas

Este documento detalla los problemas encontrados en el código y sugerencias de mejora.

## 1. Mantenibilidad del Código
- [ ] **BattleController Monolítico:** `BattleController` tiene más de 900 líneas y maneja lógica de negocio mezclada con control de flujo HTTP. Viola el principio de responsabilidad única.
- [ ] **Datos Hardcodeados:** `BattleService` (Tabla de Tipos), `ItemService` (Lista de Objetos) y `StatusEffectService` (Definiciones) contienen grandes arrays hardcodeados. Deberían moverse a base de datos o archivos de configuración para facilitar mantenimiento.
- [ ] **Complejidad en POkemonHelper:** `PokemonHelper` tiene demasiada responsabilidad (API, DB, Caché, Selección de Moves, Lógica de Evolución). Debería refactorizarse en servicios más pequeños o Repositorios.
- [ ] **Duplicación de Código:** Lógica de preparación de equipos duplicada en `startMultiplayerBattle` y `startAIBattle`.

## 2. Frontend y UI
- [ ] **Lógica en Vistas:** `arena.blade.php` contiene bloques `@php` con lógica de cálculo de porcentajes y estados. Esto debería pasarse desde el Controlador o un View Composer.
- [ ] **CSS Embebido:** `arena.blade.php` tiene un bloque `<style>` de más de 300 líneas. Debería extraerse a un archivo CSS/SCSS dedicado o refactorizarse usando Tailwind CSS.
- [ ] **Strings Hardcodeados:** Textos en la interfaz (Blade y JS) no están internacionalizados (i18n), dificultando la traducción.

## 3. Estado y Datos
- [ ] **Abuso de Sesiones:** El estado de la batalla se almacena en un array gigante en la sesión (`current_battle`). Esto es frágil y difícil de tipar. Debería encapsularse en una clase `BattleState` o similar.
- [ ] **Transacciones Pesadas:** `PokemonHelper::getPokemon` realiza muchas operaciones de escritura en DB dentro de una transacción si hay cache miss, lo que podría causar timeouts.

## 4. Código Legado/Deprecado
- [ ] **Comentarios obsoletos:** `PokemonHelper.php` contiene comentarios sobre lógica antigua o "parches" ("Fallos para datos antiguos").
- [ ] **Números Mágicos:** Uso de `1025` como límite de Pokémon en `BattleService`.
