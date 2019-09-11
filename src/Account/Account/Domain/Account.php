<?php
namespace CTIC\Account\Account\Domain;

use Doctrine\ORM\Mapping as ORM;
use CTIC\App\Base\Domain\IdentifiableTrait;
use CTIC\Account\Account\Domain\Validation\AccountValidation;

/**
 * @ORM\Entity(repositoryClass="CTIC\Account\Account\Infrastructure\Repository\AccountRepository")
 * @ORM\Table(name="AccountClient")
 */
class Account implements AccountInterface
{
    use IdentifiableTrait;
    use AccountValidation;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    public $dbHost;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    public $dbName;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    public $dbUser;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    public $dbPassword;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDbHost(): string
    {
        return $this->dbHost;
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getDbUser(): string
    {
        return $this->dbUser;
    }

    /**
     * @return string
     */
    public function getDbPassword(): string
    {
        return $this->dbPassword;
    }
}