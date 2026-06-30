<?php

namespace App\Commands;

use App\Services\WalletService;

class DepositCommand implements CommandInterface
{
    public function execute(array $arguments,int $userId):string
    {
        return app(WalletService::class)
            ->deposit(
                $userId,
                $arguments[0] ?? 0
            );
    }
}