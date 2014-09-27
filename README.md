#throttle
========

Quick PHP ripoff of Ruby / Rack Throttle library - limit incoming requests from a given IP

Right now, only compatible with Predis. Would like to write some adapters in the future. Hoping FIG passes this [proposed cache standard interface](https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md) soon.

Use with a Silex App:

```php
    require_once __DIR__.'/../vendor/autoload.php';

    $app = new \Silex\Application();

    $stack = (new Stack\Builder())
	    ->push('pno\Throttle', new \Predis\Client());

    $app = $stack->resolve($app);

    $response = $app->handle(Symfony\Component\HttpFoundation\Request::createFromGlobals());
    $response->send();
    $app->terminate($request, $response);
```

It works by looking at # of requests / interval of time. The default is 3600 requests per 3600 seconds - i.e., one request per second for one hour. If an IP has hit the limit, Throttle responds by sending a Symfony 403 response with the message "Rate limit exceeded".

You can override any of those defaults by passing them to Throttle's constructor:

For example, to limit IPs to 1 request every 10 hours, with a more colorful response message, you could do something like this:

```php
    $response = new Symfony\Component\HttpFoundation\Response('STAY OFF MY LAWN!!! >(', 403);
    $stack = (new Stack\Builder())
        ->push('pno\Throttle', new Predis\Client(), 1, 36000, $response);
    ...
```