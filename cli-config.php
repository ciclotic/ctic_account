<?php
require_once __DIR__.'/vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use CTIC\App\Account\Domain\Fixture\AccountFixture;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__.'/config.yml'));

$paths = array("src");
$isDevMode = $config['general']['dev_mode'];

// the connection configuration
$dbParams = array(
    'driver'    => $config['db']['driver'],
    'user'      => $config['db']['user'],
    'password'  => $config['db']['password'],
    'host'      => $config['db']['host'],
    'dbname'    => $config['db']['dbname']
);

$config = Setup::createConfiguration($isDevMode);
try{
    $driver = new AnnotationDriver(new AnnotationReader(), $paths);
}catch (AnnotationException $ae)
{
    echo $ae->getMessage();
}

// registering noop annotation autoloader - allow all annotations by default
AnnotationRegistry::registerLoader('class_exists');
$config->setMetadataDriverImpl($driver);
$config->setProxyDir('proxy');

try{
    $entityManager = EntityManager::create($dbParams, $config);
}catch (ORMException $oe)
{
    echo $oe->getMessage();
}

$helperSet = ConsoleRunner::createHelperSet($entityManager);

// Retrieve default console application
$cli = ConsoleRunner::createApplication($helperSet);

// Runs console application
$cli->run();
