<?php

namespace App\Services;

class BattleLogService
{
    /**
     * Formatea un parámetro para ser resaltado en el log (HTML).
     *
     * @param string $text Texto a resaltar
     * @param string $type Tipo de resaltado ('pokemon', 'move', 'item', 'ability', 'effectiveness', 'damage', 'heal')
     * @return string
     */
    public function formatParam(string $text, string $type): string
    {
        $class = match ($type) {
            'pokemon' => 'text-warning fw-bold',
            'move' => 'text-cyan fw-bold',
            'item' => 'text-info',
            'ability' => 'text-primary',
            'effectiveness' => 'text-danger fw-bold',
            'damage' => 'text-danger',
            'heal' => 'text-success',
            default => 'text-white'
        };

        return "<span class=\"{$class}\">{$text}</span>";
    }

    /**
     * Agrega un mensaje al array de mensajes y opcionalmente al log de batalla si se proporciona.
     *
     * @param array $messages Array de mensajes acumulados en el turno actual
     * @param string $message Mensaje ya traducido/formateado
     * @param string|null $class Clase CSS para todo el mensaje (opcional)
     */
    public function addMessage(array &$messages, string $message, ?string $class = null): void
    {
        if ($class) {
            $message = "<span class=\"{$class}\">{$message}</span>";
        }
        $messages[] = $message;
    }

    /**
     * Persiste los mensajes en el log de la batalla.
     *
     * @param array $battle Datos de la batalla (referencia)
     * @param array $messages Mensajes a agregar
     */
    public function persistLogs(array &$battle, array $messages): void
    {
        if (!isset($battle['log'])) {
            $battle['log'] = [];
        }

        foreach ($messages as $msg) {
            $battle['log'][] = [
                'message' => $msg,
                'time' => now()->format('H:i:s'),
                'turn' => $battle['turn_number'] ?? 1
            ];
        }

        // Mantener solo los últimos 30 mensajes
        if (count($battle['log']) > 30) {
            $battle['log'] = array_slice($battle['log'], -30);
        }
    }
}