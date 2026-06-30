<?php

namespace App\Commands;

use App\Services\WalletService;

class BalanceCommand implements CommandInterface
{
    public function execute(array $arguments,int $userId):string
    {
        return app(WalletService::class)
            ->balance($userId);
    }
}