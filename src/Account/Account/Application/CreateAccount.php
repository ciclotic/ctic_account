<?php
namespace CTIC\Account\Account\Application;

use CTIC\Account\Account\Domain\Command\AccountCommand;
use CTIC\Account\Account\Domain\Account;
use CTIC\App\Base\Application\CreateInterface;
use CTIC\App\Base\Domain\Command\CommandInterface;
use CTIC\App\Base\Domain\EntityInterface;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;

class CreateAccount implements CreateInterface
{
    /**
     * @param CommandInterface|AccountCommand $command
     * @return EntityInterface|Account
     */
    public static function create(CommandInterface $command): EntityInterface
    {
        $account = new Account();
        $account->name = $command->name;
        $account->dbHost = $command->dbHost;
        $account->dbName = $command->dbName;
        $account->dbUser = $command->dbUser;
        $account->dbPassword = $command->dbPassword;

        return $account;
    }
}