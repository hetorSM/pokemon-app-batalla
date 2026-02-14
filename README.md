# Pokémon App (Laravel & PokéAPI)

¡Bienvenido a la Pokémon App! Una aplicación web interactiva donde puedes explorar la Pokédex, gestionar tu propio equipo y combatir contra la IA o contra amigos en modo local.

## Instalación y Configuración

Este proyecto utiliza **Laravel Sail**, lo que facilita su ejecución mediante Docker sin necesidad de instalar PHP o Composer localmente.

### Requisitos Previos
- Docker Desktop instalado y corriendo.
- Git.

### Pasos para la instalación

1. **Clonar el repositorio:**
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd pokemon-app
   ```

2. **Copiar el archivo de entorno:**
   ```bash
   cp .env.example .env
   ```

3. **Instalar dependencias y levantar contenedores:**
   Si no tienes PHP instalado, puedes usar un contenedor temporal para instalar Composer:
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php83-composer:latest \
       composer install --ignore-platform-reqs
   ```

4. **Levantar el entorno con Sail:**
   ```bash
   ./vendor/bin/sail up -d
   ```

5. **Generar la clave de la aplicación:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **Ejecutar migraciones:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

7. **Instalar dependencias de Frontend:**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

La aplicación estará disponible en [http://localhost](http://localhost).

## Tecnologías Utilizadas

- **Framework**: Laravel 11
- **Contenedores**: Laravel Sail (Docker)
- **API**: PokéAPI
- **Frontend**: Blade, Vanilla JavaScript, CSS (Bootstrap)
- **Cliente HTTP**: GuzzleHttp
- **Base de datos**: MySQL

### Instalación del cliente HTTP

```bash
./vendor/bin/sail composer require guzzlehttp/guzzle
```   

## Funcionalidades Principales

- **Pokédex**: Listado dinámico de Pokémon con búsqueda y detalles.
- **Gestión de Equipos**: Crea, modifica e intercambia el orden de tus Pokémon seleccionados.
- **Sistema de Batalla**:
  - **VS IA**: Lógica de combate contra la máquina con selector de dificultad.
  - **Multijugador Local**: Modo Hotseat para 2 jugadores con selección manual o aleatoria de equipo para el Jugador 2.
- **Log de Combate**: Historial detallado de cada turno y acción.

## Documentación Adicional

- [Documento Técnico](./DocumentoTecnico.md): Detalles sobre la arquitectura y el flujo de datos.

---

# Acerca de Laravel

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

Laravel es un framework de aplicaciones web con una sintaxis expresiva y elegante. Creemos que el desarrollo debe ser una experiencia agradable y creativa para ser verdaderamente gratificante. Laravel intenta aliviar el dolor del desarrollo facilitando las tareas comunes utilizadas en la mayoría de los proyectos web, como:

- [Motor de enrutamiento simple y rápido](https://laravel.com/docs/routing).
- [Contenedor de inyección de dependencias potente](https://laravel.com/docs/container).
- Múltiples back-ends para almacenamiento de [sesiones](https://laravel.com/docs/session) y [caché](https://laravel.com/docs/cache).
- [ORM de base de datos](https://laravel.com/docs/eloquent) expresivo e intuitivo.
- [Migraciones de esquemas](https://laravel.com/docs/migrations) agnósticas a la base de datos.
- [Procesamiento de trabajos en segundo plano robusto](https://laravel.com/docs/queues).
- [Transmisión de eventos en tiempo real](https://laravel.com/docs/broadcasting).

## Aprendiendo Laravel

Laravel tiene la documentación más extensa y completa, además de una biblioteca de tutoriales en video, lo que facilita enormemente el comienzo con el framework.

Si no tienes ganas de leer, [Laracasts](https://laracasts.com) puede ayudarte. Laracasts contiene miles de tutoriales en video sobre una variedad de temas, incluyendo Laravel, PHP moderno, pruebas unitarias y JavaScript.

## Patrocinadores de Laravel

Queremos extender nuestro agradecimiento a los patrocinadores que financian el desarrollo de Laravel. Si estás interesado en convertirte en patrocinador, visita el [programa de socios de Laravel](https://partners.laravel.com).

## Licencia

El framework Laravel es software de código abierto bajo la [licencia MIT](https://opensource.org/licenses/MIT).

---
© 2026 - Proyecto desarrollado como aplicación híbrida Pokémon.
