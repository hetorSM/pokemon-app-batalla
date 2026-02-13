# Documentación Técnica: Proyecto Pokémon API

## 1. Visión General del Proyecto
Este proyecto es una aplicación web full-stack desarrollada en **Laravel 11** que simula la experiencia de un entrenador Pokémon con una estética "Sci-Fi / Tech" (Silph Co.). Combina el consumo de una API externa masiva (PokéAPI) con lógica de negocio compleja para batallas por turnos (PvE y PvP Local), gestión de equipos, uso de objetos y visualización de datos en tiempo real.

### Objetivos Técnicos
- **Consumo Eficiente de API:** Integración robusta con PokéAPI manejando tiempos de respuesta, caché y normalización de datos.
- **Arquitectura Escalable:** Uso de Patrón de Repositorio/Servicio para desacoplar la lógica de negocio (Batalla, IA, Objetos, Estados).
- **Experiencia de Usuario Inmersiva:** Interfaz reactiva con animaciones CSS/JS avanzadas, efectos de sonido (Cries) y diseño temático "Silph Co. Terminal".
- **Persistencia Híbrida:** Uso de sesiones para estado volatìl (batallas, equipo temporal) y caché para datos estáticos (Pokémon/Movimientos).

---

## 2. Arquitectura del Sistema

### Stack Tecnológico
- **Backend:** Laravel 11 (PHP 8.2+).
- **Frontend:** Blade Templates, CSS3 (Variables, Animations, Glassmorphism), Vanilla JavaScript (ES6+).
- **Infraestructura:** Docker (Laravel Sail) con contenedores para App, MySQL (opcional) y Redis.
- **Datos:** PokéAPI (Externa) + Caché local (Redis/File).

### Estructura de Servicios (Service Layer)
El proyecto extiende el MVC tradicional delegando la lógica compleja a servicios especializados:
1.  **`BattleService`:** Núcleo de la mecánica de batalla (cálculo de daño, fórmulas oficiales, turnos).
2.  **`AIService`:** Cerebro de la inteligencia artificial con 3 niveles de dificultad.
3.  **`ItemService`:** Gestión de inventario, efectos de objetos (curación, revivir) y validación de uso.
4.  **`StatusEffectService`:** Manejo de estados alterados (Quemadura, Parálisis, etc.) y su persistencia.
5.  **`PokemonHelper`:** Facade para la comunicación con PokéAPI y transformación de datos.

---

## 3. Integración con PokéAPI (`PokemonHelper`)

El núcleo de datos reside en `app/Helpers/PokemonHelper.php`.

### Características Principales
1.  **Caché Inteligente:** Almacenamiento de datos de Pokémon por 24 horas (`pokemon_{id}`) para minimizar latencia.
2.  **Normalización de Datos:** Transformación de respuestas JSON masivas en estructuras ligeras y optimizadas.
3.  **Sistema de Sonido (Cries):** Extracción y reproducción de "gritos" de Pokémon directamente desde la API para experiencia multimedia.
4.  **Smart Moveset:** Algoritmo de selección de movimientos que prioriza:
    -   Movimientos con daño (`power > 0`).
    -   **STAB** (Same Type Attack Bonus).
    -   Cobertura de tipos.

---

## 4. Motor de Batalla y Modos de Juego

La lógica de combate está orquestada por `BattleController` y apoyada por múltiples servicios.

### Modos de Juego
1.  **Un Jugador (PvE):** El usuario se enfrenta a una IA.
    -   Configuración de dificultad: Fácil, Normal, Difícil.
2.  **Multijugador Local (PvP):** Dos jugadores comparten el dispositivo, turnándose para elegir acciones ("Hotseat").

### Inteligencia Artificial (`AIService`)
La IA toma decisiones basadas en la dificultad seleccionada:
-   **Fácil:** Selección aleatoria de movimientos válidos.
-   **Normal:** Evalúa tabla de tipos (eficacia) y considera cambios (Switch) si está en desventaja crítica.
-   **Difícil:**
    -   Uso de **Objetos** (Pociones) cuando la salud es crítica.
    -   Predicción de daño ("Kill logic").
    -   Cambios estratégicos (Switching) anticipando ventaja de tipos.
    -   Priorización de movimientos con efectos de estado.

### Mecánicas Avanzadas
-   **Fórmulas Oficiales:** Daño calculado usando nivel, ataque/defensa (físico/especial), STAB, efectividad de tipos y varianza aleatoria (85-100%).
-   **Sistema de Objetos (`ItemService`):** Implementación de Pociones, Superpociones, Revivir, etc., utilizables en batalla (pierde turno).
-   **Estados Alterados (`StatusEffectService`):**
    -   Efectos volátiles y persistentes (Quemadura reduce ataque, Veneno daño progresivo, etc.).
    -   Procesamiento al inicio/final de turno según reglas oficiales.

---

## 5. Frontend y Diseño "Silph Co."

El frontend ha sido rediseñado con una estética **Cyberpunk / Sci-Fi** inspirada en la corporación "Silph Co." del universo Pokémon.

### Interfaz de Usuario (UI)
-   **Estética:** Fuentes monoespaciadas (`Orbitron`, `JetBrains Mono`), colores neón (Cyan, Red), efectos de "Glassmorphism" y bordes técnicos.
-   **Visualización de Datos:** Barras de estadísticas líquidas, medidores de HP circulares, y tarjetas holográficas para los Pokémon.
-   **Gestión de Equipo (`TeamController`):** Interfaz "Squad Logistics" con análisis estadístico del equipo (promedios de ATK/DEF/HP).

### Motor de Animaciones (Arena)
-   **VFX Dinámicos:** Partículas generadas por JS según el tipo de ataque (Fuego, Agua, Eléctrico).
-   **Feedback Visual:**
    -   Sacudidas de cámara y parpadeos en daño.
    -   Animaciones de entrada/salida de Pokéball.
    -   Transiciones suaves de barras de vida (Verde -> Amarillo -> Rojo).
-   **Sincronización:** Sistema de promesas (`Promise`) para asegurar que el texto del log coincida perfectamente con la acción visual en pantalla.

---

## 6. Desafíos Técnicos y Soluciones

1.  **Complejidad de la IA:** Crear una IA que no fuera injusta pero sí desafiante.
    *   *Solución:* Sistema de puntuación (Scoring System) en `AIService` que evalúa cada posible movimiento y asigna un peso basado en daño potencial y utilidad estratégica.
2.  **Sincronización de Estados (Backend vs Frontend):**
    *   *Solución:* El backend procesa el turno completo y devuelve un JSON con el resultado final + un array de "mensajes/eventos". El frontend reproduce estos eventos secuencialmente.
3.  **Gestión de Sesiones en PvP Local:**
    *   *Solución:* Estructura de datos unificada en `Session` (`current_battle`) que soporta dinámicamente si el oponente es 'ai' o 'player_2', permitiendo reutilizar el 90% del código de la arena.
