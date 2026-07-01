<?php

namespace App\Commands;


class UsersCommand implements CommandInterface
{
    public function execute(array $arguments, int $userId): string
    {
        return 'کاربران';
    }
}