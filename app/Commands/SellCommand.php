<?php

namespace App\Commands;

use App\Services\InventoryService;

class SellCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        $weight = $arguments[0] ?? 0;
        $price = $arguments[1] ?? 0;

        return app(InventoryService::class)
            ->sell($userId, $weight, $price);
    }
}