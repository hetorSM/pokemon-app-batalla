# Fallos y Mejoras Detectadas

Este documento detalla el estado actual de las mejoras del proyecto, con un enfoque en refactorización incremental y segura.

## ✅ Mejoras Completadas (Historial)

Estas mejoras ya han sido implementadas y verificadas.
1.  **Frontend:** CSS extraído de `arena.blade.php` a `public/css/battle-arena.css`.
2.  **Configuración:** Colores de Tipos movidos a `config/pokemon.php`.
3.  **Lógica de Vistas:** Cálculos de HP y clases CSS movidos de la vista al Controlador.
4.  **Observabilidad:** Agregados logs de error en `PokemonHelper` para evitar fallos silenciosos.
5.  **Números Mágicos:** Creada constante `Pokemon::MAX_ID` (1025) y reemplazada en `BattleService` y `PokemonHelper`.
6.  **Internacionalización:** Creado `lang/es/battle.php` y eliminados textos hardcodeados en `BattleController` y `BattleService`. Configurado `APP_LOCALE=es`.
7.  **Limpieza Frontend (CSS):** Extraído todo el CSS embebido de las vistas (`battle`, `pokedex`, `team`, `layouts`, `welcome`) a archivos CSS estáticos en `public/css/`.

---

## 🚀 Próximos Pasos (Incrementales y Seguros)

Para evitar romper el proyecto ("muerte por actualización masiva"), abordaremos los problemas complejos dividiéndolos en pequeñas operaciones seguras que no cambien la lógica principal de golpe.



### Fase 2: Reducción del Monolito (BattleController) - Parte 1
*Objetivo: Extraer lógica auxiliar sin romper el flujo principal.*
- [x] **Extracción de Logs:** La lógica que formatea mensajes de batalla (HTML colors, nombres) ocupa mucho espacio. Moverla a un `BattleLogService` o `BattlePresenter`.
- [x] **Validación de Acciones:** Extraer las validaciones de `action()` (¿es mi turno? ¿tengo HP?) a un FormRequest o método privado dedicado.

### Fase 3: Estandarización de `PokemonHelper`
*Objetivo: Mejorar la robustez de las transacciones de base de datos.*
- [x] **Optimización de Transacciones:** Revisar `getPokemon` para asegurar que las transacciones DB no sean excesivamente largas, reduciendo riesgo de timeouts.
- [x] **Tipado Estricto (Gradual):** Añadir tipos de retorno (`: array`, `: ?object`) a los métodos auxiliares pequeños para detectar errores antes.

---

## ⚠️ Mejoras Complejas (A largo plazo)

Estas requieren una planificación dedicada y tests exhaustivos antes de empezar.

1.  **Refactorización de `PokemonHelper` a Servicios:**
    -   *Estado:* La clase hace demasiadas cosas (API, DB, Lógica).
    -   *Plan:* Dividir en `PokemonRepository` (DB), `PokeApiService` (Externa) y `BattleCalculator` (Lógica).
