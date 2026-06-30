<?php

namespace App\Commands;

class HelpCommand implements CommandInterface
{
    public function execute(array $arguments,int $userId):string
    {
        return
            "دستورات

موجودی

شارژ مبلغ

برداشت مبلغ

خرید وزن قیمت

فروش وزن قیمت";
    }
}