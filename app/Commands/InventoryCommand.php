<?php

namespace App\Commands;

use App\Services\InventoryService;

class InventoryCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        return app(InventoryService::class)
            ->inventory();
    }
}