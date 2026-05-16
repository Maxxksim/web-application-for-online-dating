<?php

namespace App\Services\traits;

use App\Models\User;

trait SortsUserIds
{
    private function sortUserIds(int $firstId, int $secondId): array
    {
        return $firstId < $secondId ? [$firstId, $secondId] : [$secondId, $firstId];
    }
}
