<?php
namespace CTIC\Account\Account\Domain\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use CTIC\Account\Account\Application\CreateAccount;
use CTIC\Account\Account\Domain\Command\AccountCommand;

class AccountFixture extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $accountCommandRenault = new AccountCommand();
        $accountCommandRenault->name = 'name';
        $accountCommandRenault->dbHost = 'db_host';
        $accountCommandRenault->dbName = 'db_name';
        $accountCommandRenault->dbUser = 'db_user';
        $accountCommandRenault->dbPassword = 'db_password';
        $accountRenault = CreateAccount::create($accountCommandRenault);
        $manager->persist($accountRenault);

        $manager->flush();
    }
}