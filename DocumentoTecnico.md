# Documentación Técnica: Proyecto Pokémon API Battle System

## 1. Visión General del Proyecto
Este proyecto es una aplicación web desarrollada en **Laravel 11** que simula un sistema de batallas Pokémon. Integra consumo de API externa (PokéAPI), persistencia de datos local, mecánica de batallas por turnos (PvE y PvP Local) y una interfaz gráfica temática inmersiva bajo la estética "Silph Co.".

### Objetivos Técnicos
- **Consumo Híbrido de Datos:** Estrategia de "Cache-First" utilizando Redis/File Cache, Base de Datos MySQL y Fallback a PokéAPI.
- **Arquitectura de Servicios:** Desacoplamiento de lógica de negocio en servicios especializados (`BattleService`, `AIService`, `BattleCalculator`, etc.).
- **Persistencia de Estado:** Uso de Sesiones para manejar el estado volátil de las batallas y Base de Datos para datos estáticos (Pokémon, Movimientos, Objetos).
- **Interfaz Temática:** Diseño frontend avanzado simulando una terminal de "Silph Co.", con animaciones CSS, grids interactivos y efectos de sonido.

---

## 2. Arquitectura del Sistema

### Stack Tecnológico
- **Framework Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** Blade Templates + JavaScript Vanilla + CSS Custom (Estética Cyberpunk/Terminal).
- **Base de Datos:** MySQL / SQLite (Entorno local).
- **Dependencias Clave:** `guzzlehttp/guzzle` (Cliente HTTP), `laravel/sanctum` (Auth API).

### Estructura de Directorios Clave
```
app/
├── Helpers/
│   └── PokemonHelper.php       # Fachada de acceso a datos (API/DB/Cache)
├── Http/
│   └── Controllers/
│       ├── BattleController.php # Orquestador de batallas (PvE y PvP Local)
│       ├── PokemonController.php # Catálogo, búsqueda y detalles
│       └── TeamController.php    # Gestión de equipo (Session-based)
├── Models/
│   ├── Pokemon.php             # Modelo Eloquent principal
│   ├── Move.php                # Movimientos y sus detalles
│   └── User.php                # Usuario del sistema
└── Services/
    ├── BattleService.php       # Lógica core de batalla y estados
    ├── BattleCalculator.php    # Cálculos matemáticos (Daño, Stats, Scores)
    ├── AIService.php           # Lógica de decisión de la IA
    ├── ItemService.php         # Lógica de objetos de batalla
    └── StatusEffectService.php # Estados alterados (Veneno, Parálisis, etc.)
```

---

## 3. Lógica de Negocio y Servicios

### 3.1. Gestión de Datos (`PokemonHelper` y `PokemonRepository`)
El `PokemonHelper` actúa como la única fuente de verdad, apoyándose en repositorios y servicios:
1.  **Cache Hit:** Retorna datos cacheados para alto rendimiento.
2.  **DB Hit:** Busca en base de datos local.
3.  **API Fallback:** Consulta PokéAPI, procesa la respuesta masiva, guarda en DB (transaccional) y cachea.
    -   *Evoluciones:* Se procesan cadenas de evolución completas.
    -   *Movimientos:* Se filtran y clasifican por método de aprendizaje.

### 3.2. Sistema de Batalla (`BattleService` y `BattleController`)
Soporta dos modalidades principales: **PvE (Jugador vs IA)** y **PvP Local (Hotseat)**.

-   **Estado de Batalla:** Almacenado en Sesión (`current_battle`). Contiene arrays completos para `player_team` y `ai_team` (usado como Jugador 2 en PvP).
-   **Configuración PvP:**
    -   Permite selección manual de equipos para ambos jugadores o generación aleatoria.
    -   Validación estricta de movimientos y objetos seleccionados.
-   **Cálculo de Daño (`BattleCalculator`):**
    -   Fórmula oficial de Pokémon (Nivel 50 por defecto).
    -   Considera STAB, Efectividad de Tipos (x0.25 - x4), Críticos, Rangos de Rol (0.85-1.00) y Estados (Quemadura).
-   **Flujo de Turnos:**
    1.  Validación de acción (Movimiento/Item/Cambio).
    2.  Check de Estados (Status Effects) pre-turno.
    3.  Ejecución de acción y cálculo de consecuencias.
    4.  Switch forzado si un Pokémon se debilita.
    5.  Verificación de victoria global.

### 3.3. Inteligencia Artificial (`AIService`)
Tres niveles de dificultad para PvE:
-   **Easy:** Selección aleatoria válida.
-   **Normal:** Selección basada en daño potencial máximo.
-   **Hard:** Estrategia completa:
    -   Uso inteligente de objetos curativos.
    -   Predicción de cambios (Switching) ante desventaja de tipos.
    -   Priorización de movimientos de estado y "Kill confirmation".

### 3.4. Objetos y Pokedex
-   **Items:** Sistema de inventario con efectos programados (Curación HP, Revivir, Curar Estado, Boost Stats). Visualización tipo "Logistics" en Pokedex.
-   **Cries:** Integración de sonidos de Pokémon ("Resonance Scan") en la vista de detalles.

---

## 4. Estructura de Base de Datos
Datos normalizados para evitar dependencia constante de la API externa:

-   `pokemons`: Datos base (id, nombre, sprites, stats base).
-   `types` / `stats`: Catálogos maestros.
-   `moves`: Datos detallados de movimientos (poder, precisión, clase, efecto).
-   `pokemon_moves`: Relación pivot con detalles de aprendizaje (nivel, método).
-   `pokemon_stats`: Relación pivot para stats base.

---

## 5. Frontend (Blade & UI)
La interfaz ha evolucionado hacia una estética "Silph Co." inmersiva.

-   **Team Management (`team/index`):**
    -   Diseño de "Biometric Containment Units".
    -   Grid interactivo con medidores de estadísticas en tiempo real.
    -   Panel de "Fleet Sync Analysis" (Promedios del equipo).
-   **Pokedex (`pokemon/index` & `show`):**
    -   Vista de detalle estilo "Terminal de Datos".
    -   Gráficos de barras animados para Stats.
    -   Reproducción de audio (Cries).
    -   Tablas de movimientos separadas por Natural (Nivel) y Técnico (MT/Tutor).
-   **Battle Arena:**
    -   Logs de batalla detallados.
    -   Animaciones de sprites y barras de salud reactivas (colores de estado crítico).
    -   Controles dinámicos según el estado del turno.

---

## 6. Puntos Críticos y Futuras Mejoras
1.  **Monolito en Controlador:** `BattleController` sigue manejando mucha lógica de orquestación, aunque se ha delegado cálculo a servicios.
2.  **Persistencia Volátil:** El uso de Sesiones limita la escalabilidad a partidas asíncronas o multijugador online real.
3.  **Hardcoded Data Logic:** Algunas configuraciones de tipos y objetos residen en arrays PHP (`BattleService`), lo que requiere despliegue para cambios de balance.
4.  **Complejidad Frontend:** La mezcla de lógica PHP en vistas Blade para lograr la estética compleja dificulta la migración a un framework JS completo (Vue/React) en el futuro.
