<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

	public function __construct(HttpKernelInterface $app, Predis\Client $client, $max_visits = 360, $interval_seconds = 3600, Response $over_limit_response = null)
	{
		$this->app = $app;
		$this->client = $client;
		$this->max_visits = $max_visits;
		$this->interval_seconds = $interval_seconds;
		$this->over_limit_response = $over_limit_response ?: new Response('Rate limit exceeded', 403);
	}

	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		$this->record($request);

		if (!$this->allowed($request)) {
			$this->over_limit_response;
			// BWA HA HA HAH AHAHA HAHAHAAAAAA!!!!!
		}

		return $this->app->handle($request);
	}

	protected function allowed(Request $request)
	{
		$key = 'throttle.' . $request->getClientIp();
		return json_decode($this->client->get($key))->whitelisted;
	}

	protected function record(Request $request)
	{
		$key = 'throttle.' . $request->getClientIp();
		if ($profile = $this->client->get($key)) {
			$this->updateProfile($key, json_decode($profile));
		} else {
			$this->client->set($key, json_encode(['visits' => 1, 'whitelisted' => true]));
			$this->client->expireat($key, $this->interval_seconds + time());
		}
	}

	protected function updateProfile($key, $profile)
	{
		$visits = $profile->visits + 1;
		$whitelisted = $visits < $this->max_visits;
		$this->client->set($key, json_encode(compact('visits', 'whitelisted')));
	}

} 