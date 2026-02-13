# Presentación del Proyecto: Pokémon Battle Simulator

Esta guía contiene la estructura para una presentación de **12 diapositivas** sobre el proyecto.

---

### Diapositiva 1: Portada
- **Título Grande:** Pokémon Battle Simulator
- **Subtítulo:** Una experiencia full-stack con Laravel y PokéAPI.
- **Autor/Equipo:** Héctor
- **Dice:** "Buenos días. Hoy les presento 'Pokémon Battle Simulator', un proyecto que trasciende una simple web para convertirse en un videojuego funcional en el navegador."

---

### Diapositiva 2: ¿Qué es este proyecto?
- **Concepto:** Una aplicación web que simula la experiencia de los juegos de Pokémon.
- **Funcionalidades Clave:**
  - Enciclopedia (Pokédex) con datos reales.
  - Gestión estratégica de equipos (Team Builder).
  - Simulador de combate por turnos con IA.
- **Dice:** "No es solo una wiki. Es una plataforma interactiva donde puedes consultar datos, armar tu estrategia y ponerla a prueba en combate real."

---

### Diapositiva 3: Objetivos del Desarrollo
- **Técnico:** Implementar una arquitectura MVC robusta y consumir APIs externas complejas.
- **Usuario:** Crear una experiencia fluida (SPA-like) sin recargas constantes.
- **Reto:** Manejar lógica de videojuegos (turnos, daño, estados) en un entorno web stateless.
- **Dice:** "El objetivo principal fue desafiar las capacidades de Laravel para manejar lógica de estado compleja típica de videojuegos, no de sitios web convencionales."

---

### Diapositiva 4: Stack Tecnológico
- **Backend:** Laravel 11 (PHP 8.2).
- **Frontend:** Blade, Vanilla JS (ES6+), CSS3 Animations.
- **Datos:** Integración con PokéAPI (REST).
- **Infraestructura:** Docker (Laravel Sail), Redis (Caché).
- **Dice:** "Utilizamos Laravel 11 como motor robusto, Docker para asegurar un entorno reproducible, y Redis para que la aplicación vuele, a pesar de consumir datos externos pesados."

---

### Diapositiva 5: Arquitectura de Software
- **Patrón:** MVC + Service Layer.
- **Controllers:** `BattleController` (Manejo de HTTP).
- **Services:** `BattleService` (Lógica de negocio pura, cálculos de daño).
- **Helpers:** `PokemonHelper` (Normalización de datos de la API).
- **Dice:** "Para mantener el código limpio, separamos la lógica. El controlador solo recibe órdenes; el Servicio es el 'cerebro' que calcula el daño y decide qué pasa; el Helper habla con la API externa."

---

### Diapositiva 6: El Corazón de Datos (PokéAPI)
- **Desafío:** La API oficial es lenta y compleja (miles de relaciones).
- **Solución:** Helper personalizado con **Caché Inteligente (TTL 24h)**.
- **Proceso:** 
  1. Consulta Caché -> 2. Si falla, llama a API -> 3. Normaliza -> 4. Guarda.
- **Dice:** "La API de Pokémon es gigantesca. Implementamos una capa de caché que guarda lo esencial. La primera vez tarda 1 segundo; la segunda, milisegundos."

---

### Diapositiva 7: La Pokédex y Team Builder
- **Pokédex:** Búsqueda en tiempo real, filtros, y visualización de stats.
- **Team Builder:** Selección de 6 Pokémon.
- **Persistencia:** Uso de Sesiones para mantener el equipo activo sin necesidad de Login obligatorio.
- **Dice:** "Puedes buscar entre más de 1000 especies. Lo interesante es el Team Builder: el sistema recuerda tu selección mediante sesiones, permitiendo una experiencia fluida de 'entrar y jugar'."

---

### Diapositiva 8: Lógica de Combate (Battle Engine)
- **Sistema de Turnos:** Jugador -> IA (o viceversa según Velocidad).
- **Fórmulas Reales:** Implementación de la fórmula de daño de los juegos originales (Nivel, Poder, Ataque/Defensa, STAB).
- **IA:** Tres niveles de dificultad. La IA "Difícil" evalúa tipos (Agua vence a Fuego).
- **Dice:** "Esto no es un 'if' aleatorio. Implementamos las fórmulas matemáticas reales de Game Freak. Si tu Pokémon es tipo Agua, sus ataques de agua pegan un 50% más fuerte (STAB)."

---

### Diapositiva 9: Experiencia Visual (Frontend)
- **Animaciones:** Sistema de partículas CSS y JS.
- **Feedback:** 
  - *Shake* al recibir daño.
  - *Lunge* al atacar.
  - Barra de vida con transición suave.
- **Tecnología:** Fetch API para actualizaciones sin recarga.
- **Dice:** "Un juego entra por los ojos. Desarrollamos un motor de animaciones en JS que sincroniza los movimientos, partículas y sonidos, dando feedback visual antes de mostrar los números."

---

### Diapositiva 10: Retos Superados
1.  **Latencia:** Solucionado con Caché Redis y precarga.
2.  **Sincronía:** El backend responde rápido, el frontend debe "esperar" a la animación. Solucionado con Promesas JS.
3.  **Estado:** Mantener la integridad de la batalla entre peticiones HTTP stateless.
- **Dice:** "El mayor reto fue la asincronía. El servidor calcula el turno en 10ms, pero el usuario necesita ver 3 segundos de animación. Tuvimos que crear una cola de eventos en el frontend."

---

### Diapositiva 11: Demo / Flujo de Uso
- **Paso 1:** Selección de Equipo.
- **Paso 2:** Inicio de Batalla (Vs IA).
- **Paso 3:** Selección de Movimiento (Estratégico).
- **Paso 4:** Victoria/Derrota.
- **Dice:** "Veamos brevemente cómo fluye: Desde armar un equipo equilibrado hasta vencer a la IA aprovechando las debilidades de tipo." (Mostrar video rápido o screenshots).

---

### Diapositiva 12: Conclusión y Futuro
- **Logros:** Una app completa, rápida y visualmente atractiva.
- **Futuro:** 
  - Modo Multijugador Real (WebSockets).
  - Persistencia en Base de Datos (MySQL) para historial.
- **Cierre:** "Este proyecto demuestra que con las herramientas adecuadas (Laravel + Docker), podemos crear experiencias complejas y divertidas en la web moderna. ¡Gracias!"
