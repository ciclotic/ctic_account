<?php
namespace CTIC\Account\Account\Infrastructure\Repository;

use CTIC\Account\Account\Domain\Account;
use CTIC\App\Base\Infrastructure\Repository\EntityRepository;

class AccountRepository extends EntityRepository
{
    /**
     * @return Account[]
     */
    public function findAllOrderedByName(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.name', 'ASC')
            ->getQuery();

        return $qb->execute();
    }
}