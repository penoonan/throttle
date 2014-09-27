#throttle
========

Quick PHP ripoff of Ruby / Rack Throttle library - limit incoming requests from a given IP

Right now, only compatible with Predis. Would like to write some adapters in the future. Hoping FIG passes this [proposed cache standard interface](https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md) soon.

Use:

```php
    require_once __DIR__.'/../vendor/autoload.php';

    $app = new \Silex\Application();

    $stack = (new Stack\Builder())
	    ->push('Throttle', new \Predis\Client())
    ;

    $app = $stack->resolve($app);

    $response = $app->handle(Symfony\Component\HttpFoundation\Request::createFromGlobals());
    $response->send();
    $app->terminate($request, $response);
```php