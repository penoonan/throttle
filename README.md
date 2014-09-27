#Throttle

Throttle is a pretty lazy ripoff of Ruby / Rack's [datagraph/rack-throttle](https://github.com/datagraph/rack-throttle) library, allowing devs to limit incoming requests from a given IP.

It follows the [StackPHP](http://www.stackphp.com) middleware convention. The only cache driver it works with is Predis. I would like to write some adapters in the future to make this more configurable. Hopefully FIG passes this [proposed cache standard interface](https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md) soon, because that would make it much easier.

Sample usage with a Silex App:

```php
    require_once __DIR__.'/../vendor/autoload.php';

    $app = new \Silex\Application();

    $stack = (new Stack\Builder())
	    ->push('pno\Throttle', new \Predis\Client());

    $app = $stack->resolve($app);
```

It works by tracking the number of requests that come from a particular IP during a given interval of time. The default limit is 360 requests per 3600 seconds - i.e., one request per 10 seconds for each hour. If an IP has hit the limit, Throttle responds by sending a Symfony 403 response with the message "Rate limit exceeded".

You can override any of those defaults by passing them to Throttle's constructor:

For example, to limit IPs to 2 requests every 10 seconds, with a more colorful response message, you could do something like this:

```php
    $response = new Symfony\Component\HttpFoundation\Response('STAY OFF MY LAWN!!! >(', 403);
    $stack = (new Stack\Builder())
        ->push('pno\Throttle', new Predis\Client(), 2, 10, $response);
```

And ah yes, lest I forget: this has been added to packagist, for the convenience, wonder and bemusement of all:

 * "pno/throttle": "dev-master"