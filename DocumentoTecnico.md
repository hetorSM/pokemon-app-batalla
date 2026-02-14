# Documentación Técnica: Proyecto Pokémon API Battle System

## 1. Visión General del Proyecto
Este proyecto es una aplicación web desarrollada en **Laravel 11** que simula un sistema de batallas Pokémon. Integra consumo de API externa (PokéAPI), persistencia de datos local, mecánica de batallas por turnos (PvE y PvP Local) y una interfaz gráfica temática.

### Objetivos Técnicos
- **Consumo Híbrido de Datos:** Estrategia de "Cache-First" utilizando Redis/File Cache, Base de Datos MySQL y Fallback a PokéAPI.
- **Arquitectura de Servicios:** Desacoplamiento de lógica de negocio en servicios especializados (`BattleService`, `AIService`, etc.).
- **Persistencia de Estado:** Uso de Sesiones para manejar el estado volátil de las batallas y Base de Datos para datos estáticos (Pokémon, Movimientos).

---

## 2. Arquitectura del Sistema

### Stack Tecnológico
- **Framework Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** Blade Templates + JavaScript Vanilla + CSS Custom (con Tailwind instalado pero uso limitado).
- **Base de Datos:** MySQL / SQLite (Entorno local).
- **Dependencias Clave:** `guzzlehttp/guzzle` (Cliente HTTP), `laravel/sanctum` (Auth API).

### Estructura de Directorios Clave
```
app/
├── Helpers/
│   └── PokemonHelper.php       # Fachada de acceso a datos (API/DB/Cache)
├── Http/
│   └── Controllers/
│       ├── BattleController.php # Controlador monolítico de la lógica de batalla
│       ├── PokemonController.php # Catálogo y búsqueda
│       └── TeamController.php    # Gestión de equipo (Session-based)
├── Models/
│   ├── Pokemon.php             # Modelo Eloquent principal
│   ├── Move.php                # Movimientos y sus detalles
│   └── User.php                # Usuario del sistema
└── Services/
    ├── BattleService.php       # Cálculos de daño y mecánicas core
    ├── AIService.php           # Lógica de decisión de la IA
    ├── ItemService.php         # Catálogo y lógica de objetos
    └── StatusEffectService.php # Estados alterados (Veneno, Parálisis, etc.)
```

---

## 3. Lógica de Negocio y Servicios

### 3.1. Gestión de Datos (`PokemonHelper`)
El `PokemonHelper` actúa como la única fuente de verdad para los datos de Pokémon. Implementa un patrón de **Proxy/Cache** complejo:
1.  **Cache Hit:** Retorna datos de `Cache::get()`.
2.  **DB Hit:** Si no está en caché, busca en MySQL (`pokemons` table). Si existe, guarda en caché y retorna.
3.  **API Fallback:** Si no está en DB, consulta PokéAPI.
    -   Parsea la respuesta masiva.
    -   **Transacción DB:** Guarda el Pokémon, Tipos, Stats, Sprites, Cries y Movimientos en la base de datos relacional.
    -   Normaliza los datos para el frontend.
    -   Guarda en caché y retorna.

### 3.2. Sistema de Batalla (`BattleService` y `BattleController`)
El núcleo del juego.
-   **Estado de Batalla:** Se almacena completamente en la Sesión del usuario (`current_battle`). Es un array asociativo que contiene los equipos, turnos, logs y estado actual.
-   **Cálculo de Daño:** Implementa la fórmula oficial de Pokémon (Nivel 50 estandarizado):
    \[
    Damage = \left( \frac{(\frac{2 \times Level}{5} + 2) \times Power \times A/D}{50} + 2 \right) \times Modifier
    \]
    Donde *Modifier* incluye STAB (x1.5), Tipos (x0.25 - x4), Crítico (x1.5) y Aleatoriedad (0.85 - 1.00).
-   **Flujo de Turnos:**
    1.  Validación de acción.
    2.  Procesamiento de Estados (Status Effects) al inicio del turno.
    3.  Ejecución de Movimiento/Item/Cambio.
    4.  Verificación de condiciones de victoria.
    5.  Cambio de turno.

### 3.3. Inteligencia Artificial (`AIService`)
Posee tres niveles de dificultad:
-   **Easy:** Selección totalmente aleatoria de movimientos válidos.
-   **Normal:** Selecciona el movimiento con mayor daño potencial basado en efectividad de tipos. Considera cambiar de Pokémon si la salud es crítica (<25%).
-   **Hard:**
    -   Uso de Objetos (Pociones/Revivir) si la salud es baja.
    -   Predicción de cambios (Switching) anticipando desventajas de tipo.
    -   Priorización de movimientos de estado (status effects) si el rival está sano.
    -   Cálculo de daño letal ("Kill confirmation").

### 3.4. Objetos y Estados
-   **ItemService:** Gestiona un inventario hardcodeado (Pociones, Curas de Estado, Revivir, Potenciadores X). Valida reglas de uso (ej. no usar Potion si HP está lleno).
-   **StatusEffectService:** Gestiona estados persistentes (Quemadura, Parálisis, Veneno, Sueño, Congelado). Calcula efectos pasivos (daño por turno, impedir movimiento).

---

## 4. Estructura de Base de Datos
El sistema normaliza la respuesta de PokéAPI en tablas relacionales para evitar consultas JSON pesadas.

-   `pokemons`: Datos base (id, nombre, height, weight).
-   `types`: Catálogo de tipos elementales.
-   `stats`: Catálogo de estadísticas (hp, attack, defense, etc.).
-   `moves`: Catálogo de movimientos, incluyendo potencia, precisión y clase.
-   `pokemon_types`, `pokemon_stats`, `pokemon_moves`: Tablas pivote para las relaciones Muchos-a-Muchos.
-   `pokemon_sprites`: URLs de imágenes, separado para limpieza.
-   `pokemon_cries`: URLs de archivos de audio.

---

## 5. Frontend (Vistas)
-   **Motor:** Blade.
-   **Estilos:** CSS personalizado embebido (`<style>` en `arena.blade.php`) + layouts base.
-   **Interactividad:**
    -   **Arena:** `arena.blade.php` maneja la visualización de la batalla. Utiliza JavaScript (parcialmente embebido y externo) para animaciones, control de turnos y actualizaciones de la UI (Barras de HP, Sprites).
    -   **Pokedex/Team:** Vistas estándar CRUD para visualizar y gestionar el equipo de 6 Pokémon (almacenado en Sesión).

---

## 6. Puntos Críticos y Deuda Técnica Detectada
1.  **Monolito en Controlador:** `BattleController` contiene demasiada lógica de orquestación y presentación.
2.  **Complejidad de Sesión:** El estado de la batalla es frágil y difícil de migrar a persistencia real (base de datos) para partidas asíncronas.
3.  **Datos Hardcodeados:** Tablas de tipos, listas de objetos y definiciones de estados están en arrays PHP, dificultando la actualización dinámica sin desplegar código.
4.  **Frontend Acoplado:** Estilos y lógica PHP mezclados en las vistas Blade dificultan el mantenimiento del frontend.
