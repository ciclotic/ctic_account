<?php
namespace CTIC\Account\Account\Domain;

use CTIC\App\Base\Domain\EntityInterface;
use CTIC\App\Base\Domain\IdentifiableInterface;

interface AccountInterface extends IdentifiableInterface, EntityInterface
{
    public function getName(): string;

    public function getDbHost(): string;

    public function getDbName(): string;

    public function getDbUser(): string;

    public function getDbPassword(): string;
}