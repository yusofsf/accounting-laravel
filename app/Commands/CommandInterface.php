<?php

namespace App\Commands;

interface CommandInterface
{
    public function execute(array $arguments,int $userId):string;
}