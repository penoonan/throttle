<?php
namespace pno;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Predis\Client;

class Throttle implements HttpKernelInterface{

	/**
	 * @var HttpKernelInterface
	 */
	private $app;
	/**
	 * @var \Predis\Client
	 */
	private $client;

	/**
	 * @var Response
	 */
	private $over_limit_response;

	/**
	 * @var int
	 */
	private $max_visits;

	/**
	 * @var int
	 */
	private $interval_seconds;

	/**
	 * @param HttpKernelInterface $app
	 * @param Client $client
	 * @param int $max_visits
	 * @param int $interval_seconds
	 * @param Response $over_limit_response
	 */
	public function __construct(HttpKernelInterface $app, Client $client, $max_visits = 360, $interval_seconds = 3600, Response $over_limit_response = null)
	{
		$this->app = $app;
		$this->client = $client;
		$this->max_visits = $max_visits;
		$this->interval_seconds = $interval_seconds;
		$this->over_limit_response = $over_limit_response ?: new Response('Rate limit exceeded', 403);
	}

	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		$key = 'throttle.'.$request->getClientIp();

		$this->record($key);

		if (!$this->allowed($key)) {
			return $this->over_limit_response; // BWA HA HA HAH AHAHA HAHAHAAAAAA!!!!!
		}

		return $this->app->handle($request);
	}

	protected function allowed($key)
	{
		$visits = (int) $this->client->get($key);
		return $visits <= $this->max_visits;
	}

	protected function record($key)
	{
		$visits = $this->client->get($key);

		$this->client->incr($key);

		if (!$visits) {
			$this->client->expireat($key, time() + $this->interval_seconds);
		}
	}

} 