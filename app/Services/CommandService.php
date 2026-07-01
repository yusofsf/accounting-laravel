<?php

namespace App\Services;

use App\Commands\BalanceCommand;
use App\Commands\BuyCommand;
use App\Commands\DepositCommand;
use App\Commands\HelpCommand;
use App\Commands\InventoryCommand;
use App\Commands\SellCommand;

class CommandService
{

    protected array $commands = [

        'موجودی پولی' => BalanceCommand::class,

        'موجودی انبار' => UsersCommand::class,

        'کمک' => HelpCommand::class,

        'خرید' => BuyCommand::class,

        'فروش' => SellCommand::class,

        'انبار' => InventoryCommand::class,
    ];

    public function execute($text,$userId)
    {

        $parts=explode(' ',$text);

        $command=$parts[0];

        unset($parts[0]);

        $arguments=array_values($parts);

        if(!isset($this->commands[$command]))
            return "دستور پیدا نشد.";

        return app(
            $this->commands[$command]
        )->execute(
            $arguments,
            $userId
        );

    }

}