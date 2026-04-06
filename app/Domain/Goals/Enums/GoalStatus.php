<?php

namespace App\Domain\Goals\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * Get the label for the goal status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Completed => 'Completada',
            self::Archived => 'Archivada',
        };
    }
}
