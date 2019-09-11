<?php
namespace CTIC\Account\Account\Domain\Command;

use CTIC\App\Base\Domain\Command\CommandInterface;

class AccountCommand implements CommandInterface
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $dbHost;
    /**
     * @var string
     */
    public $dbName;
    /**
     * @var string
     */
    public $dbUser;
    /**
     * @var string
     */
    public $dbPassword;
}