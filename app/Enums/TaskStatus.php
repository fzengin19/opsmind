<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Beklemede',
            self::Todo => 'Yapılacak',
            self::InProgress => 'Devam Ediyor',
            self::Review => 'İnceleme',
            self::Done => 'Tamamlandı',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Backlog => '#6b7280',
            self::Todo => '#3b82f6',
            self::InProgress => '#f59e0b',
            self::Review => '#8b5cf6',
            self::Done => '#10b981',
        };
    }
}
