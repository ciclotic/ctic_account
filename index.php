<?php
/**
 * Created by PhpStorm.
 * User: Valentí
 * Date: 05/11/2018
 * Time: 11:31
 */

require_once __DIR__.'/vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use CTIC\App\Base\Infrastructure\Kernel\Kernel;
use CTIC\App\Base\Application\CreateResourceController;
use CTIC\App\Base\Infrastructure\Controller\Command\ResourceControllerCommand;
use CTIC\App\Base\Infrastructure\Controller\ResourceController;
use CTIC\App\Permission\Domain\Permission;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\Translator;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationException;
use Metadata\Driver\FileLocator as MetadataFileLocator;
use Symfony\Component\Yaml\Yaml;

// General params
$configYaml = Yaml::parse(file_get_contents(__DIR__.'/config.yml'));

$isDevMode = $configYaml['general']['dev_mode'];
$defaultLocale = $configYaml['general']['locale']['default'];
$kernel = new Kernel();

// General settings
ini_set('session.cookie_domain', '.' . $configYaml['general']['host']);

// Metadata FileLocator initialize
$metadataFileLocator = new MetadataFileLocator(array('/'));

// Routes params
$fileLocator = new FileLocator($kernel);
if (array_key_exists('REQUEST_URI', $_SERVER)) {
    $path = explode('?', $_SERVER['REQUEST_URI']);
    $queryString = (isset($path[1])) ? $path[1] : '';
    $path = $path[0];
} else {
    $path = '/';
    $queryString = '';
}
$requestContext = new RequestContext('http://' . $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD'], $_SERVER['SERVER_NAME'], 'http', 80, 443, $path, $queryString);

$router = new Router(
    new YamlFileLoader($fileLocator),
    __DIR__ . '/routes.yaml',
    array(), //'cache_dir' => __DIR__.'/cache'
    $requestContext
);

$routeParams = $router->match($path);

// Initialize container
$container = new Container();

// Initialize session
$session = new Session();

// Initialize Translator
$translator = new Translator($defaultLocale);

// DB params
$dbPaths = array("src");

// the connection configuration
$dbParams = array(
    'driver'    => $configYaml['db']['driver'],
    'user'      => $configYaml['db']['user'],
    'password'  => $configYaml['db']['password'],
    'host'      => $configYaml['db']['host'],
    'dbname'    => $configYaml['db']['dbname']
);

$config = Setup::createConfiguration($isDevMode);

try{
    $driver = new AnnotationDriver(new AnnotationReader(), $dbPaths);
}catch (AnnotationException $ae)
{
    echo $ae->getMessage();
}

// registering noop annotation autoloader - allow all annotations by default
AnnotationRegistry::registerLoader('class_exists');
$config->setMetadataDriverImpl($driver);
$config->setAutoGenerateProxyClasses(false);
$config->setProxyDir('proxy');

// App configuration
$app = new Silex\Application();

$twigParams = array(
    'path' => array(
        __DIR__.'/views',
        str_replace('cuentas', '', __DIR__.'config/views/Common')
    )
    //'cache' => __DIR__.'/cache'
);

$method = strtolower($_SERVER['REQUEST_METHOD']);

$app->$method($path, function (Request $request) use($app, $dbParams, $config, $routeParams, $router, $defaultLocale, $metadataFileLocator, $container, $session, $translator, $twigParams) {
    try{
        $entityManager = EntityManager::create($dbParams, $config);
    }catch (ORMException $oe)
    {
        throw new Exception('Ha ocurrido un error con la base de datos.');
    }
    try{
        if (empty($routeParams['_controller']) || empty($routeParams['_controller_method'])) {
            throw new Exception('No controller found in current route.');
        } else {
            $controllerClass = $routeParams['_controller'];
            $controllerMethod = $routeParams['_controller_method'];
        }

        if (empty($routeParams['_controller_command'])) {
            $controllerCommand = 'CTIC\App\Base\Infrastructure\Controller\Command\ResourceControllerCommand';
        } else {
            $controllerCommand = $routeParams['_controller_command'];
        }

        if(!empty($routeParams['id']))
        {
            $request->attributes->add(array('id' => $routeParams['id']));
        }

        if (empty($routeParams['_controller_create'])) {
            $controller = new $controllerClass();
        } else {
            $controllerCreate = $routeParams['_controller_create'];

            $command = new $controllerCommand();
            $command->manager = $entityManager;
            $permissionRepository = $entityManager->getRepository(Permission::class);

            $controller = $controllerCreate::create(
                $command,
                $permissionRepository,
                $router,
                $session,
                $translator,
                $defaultLocale,
                $request,
                $metadataFileLocator,
                $container,
                $twigParams
            );
        }

        return $controller->$controllerMethod($request);
    } catch (Exception $e) {
        throw new Exception('Ha ocurrido un error al cargar la url.');
    }
});

$app->error(function (\Exception $e, Request $request, $code) {
    switch ($code) {
        case 500:
            $message = 'Ha ocurrido un error en el servidor.';
            break;
        case 404:
            $message = 'La página no se ha encontrado.';
            break;
        default:
            $message = 'Ha ocurrido un error inesperado.';
    }
    return new Response($message);
});

$app->run();