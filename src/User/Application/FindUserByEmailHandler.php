<?php declare (strict_types = 1);

namespace Wallet\User\Application;

use Wallet\User\Application\FindUserByEmail;
use Wallet\User\Infrastructure\DbalUsers;

class FindUserByEmailHandler
{
    /**
     * @var \Wallet\User\Infrastructure\DbalUsers
     */
    protected $users;

    /**
     * @param \Wallet\User\Infrastructure\DbalUsers $users
     */
    public function __construct(DbalUsers $users)
    {
        $this->users = $users;
    }

    /**
     * @param \Wallet\User\Application\FindUserByEmail $command
     * @return null|\Wallet\User\Application\Query\UserView
     */
    public function execute(FindUserByEmail $command)
    {
        return $this->users->findByEmail($command->email());
    }
}
