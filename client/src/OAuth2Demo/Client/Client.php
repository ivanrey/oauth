<?php

namespace OAuth2Demo\Client;

use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Guzzle\Http\Client as GuzzleClient;

class Client implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // set up silex application
        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__.'/../../../views',
        ));
        // sets twig extension for client debug rendering
        $app['twig']->addExtension(new Twig\JsonStringifyExtension());

        // set up the service container
        $this->setup($app);

        // create http client
        $app['http_client'] = new GuzzleClient();

        // create the session
        $app->register(new SessionServiceProvider());

        if (!$app['session']->isStarted()) {
            $app['session']->start();
        }

        // creates a new controller based on the default route
        $routing = $app['controllers_factory'];

        // Set corresponding endpoints on the controller classes
        Controllers\Homepage::addRoutes($routing);
        Controllers\ReceiveAuthorizationCode::addRoutes($routing);
        Controllers\RequestToken::addRoutes($routing);
        Controllers\RequestResource::addRoutes($routing);
        Controllers\Authentication::addRoutes($routing);

        return $routing;
    }

    private function setup(Application $app)
    {
        if (!file_exists($sqliteFile = __DIR__.'/../../../data/oauth.sqlite')) {
            $this->generateSqliteDb();
        }

        $app['pdo'] = $app->share(function () use ($sqliteFile) {
            return new \PDO('sqlite:'.$sqliteFile);
        });

        $app['db'] = $app->share(function (Application $app) {
            return new Db($app['pdo']);
        });

        $app['parameters'] = $this->loadParameters();
    }

    public function loadParameters()
    {
        /** load the parameters configuration */
        $parameterFile = __DIR__.'/../../../data/parameters.json';
        if (!$parameters = json_decode(file_get_contents($parameterFile), true)) {
            throw new Exception('unable to parse parameters file: '.$parameterFile);
        }

        return $parameters;
    }

    private function generateSqliteDb()
    {
        include_once(__DIR__.'/../../../data/rebuild_db.php');
    }
}