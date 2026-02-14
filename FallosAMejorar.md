# Fallos y Mejoras Detectadas

Este documento detalla el estado actual de las mejoras del proyecto, con un enfoque en refactorización incremental y segura.

## ✅ Mejoras Completadas (Historial)

Estas mejoras ya han sido implementadas y verificadas.
1.  **Frontend:** CSS extraído de `arena.blade.php` a `public/css/battle-arena.css`.
2.  **Configuración:** Colores de Tipos movidos a `config/pokemon.php`.
3.  **Lógica de Vistas:** Cálculos de HP y clases CSS movidos de la vista al Controlador.
4.  **Observabilidad:** Agregados logs de error en `PokemonHelper` para evitar fallos silenciosos.

---

## 🚀 Próximos Pasos (Incrementales y Seguros)

Para evitar romper el proyecto ("muerte por actualización masiva"), abordaremos los problemas complejos dividiéndolos en pequeñas operaciones seguras que no cambien la lógica principal de golpe.

### Fase 1: Limpieza de "Números Mágicos" y Constantes
*Objetivo: Eliminar valores hardcodeados dispersos sin tocar lógica compleja.*
- [ ] **Límite de Pokémon (1025):** Mover este número hardcodeado en `BattleService` y otros lugares a una constante en el modelo `Pokemon` o config.
- [ ] **Mensajes de Texto:** Identificar textos fijos en `BattleController` (ej: mensajes de log de batalla) y moverlos a archivos de traducción o constantes para facilitar cambios futuros.

### Fase 2: Reducción del Monolito (BattleController) - Parte 1
*Objetivo: Extraer lógica auxiliar sin romper el flujo principal.*
- [ ] **Extracción de Logs:** La lógica que formatea mensajes de batalla (HTML colors, nombres) ocupa mucho espacio. Moverla a un `BattleLogService` o `BattlePresenter`.
- [ ] **Validación de Acciones:** Extraer las validaciones de `action()` (¿es mi turno? ¿tengo HP?) a un FormRequest o método privado dedicado.

### Fase 3: Estandarización de `PokemonHelper`
*Objetivo: Mejorar la robustez de las transacciones de base de datos.*
- [ ] **Optimización de Transacciones:** Revisar `getPokemon` para asegurar que las transacciones DB no sean excesivamente largas, reduciendo riesgo de timeouts.
- [ ] **Tipado Estricto (Gradual):** Añadir tipos de retorno (`: array`, `: ?object`) a los métodos auxiliares pequeños para detectar errores antes.

---

## ⚠️ Mejoras Complejas (A largo plazo)

Estas requieren una planificación dedicada y tests exhaustivos antes de empezar.

1.  **Migración de Sesión a Base de Datos:**
    -   *Estado:* El estado de batalla vive en `session('current_battle')`.
    -   *Plan:* Crear modelos `Battle` y `BattleTurn` para persistir el estado. Esto permitirá reconexión y APIs reales.

2.  **Refactorización de `PokemonHelper` a Servicios:**
    -   *Estado:* La clase hace demasiadas cosas (API, DB, Lógica).
    -   *Plan:* Dividir en `PokemonRepository` (DB), `PokeApiService` (Externa) y `BattleCalculator` (Lógica).
