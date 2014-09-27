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
		$this->record($request);

		if (!$this->allowed($request)) {
			return $this->over_limit_response;
			// BWA HA HA HAH AHAHA HAHAHAAAAAA!!!!!
		}

		return $this->app->handle($request);
	}

	protected function allowed(Request $request)
	{
		$key = 'throttle.' . $request->getClientIp();
		return (bool) json_decode($this->client->get($key))->whitelisted;
	}

	protected function record(Request $request)
	{
		$key = 'throttle.' . $request->getClientIp();

		$profile = json_decode($this->client->get($key));

		if (!$profile) {
			$this->fresh($key);
			return;
		}

		if (time() > $profile->expire) {
			$this->fresh($key);
			return;
		}

		$this->updateProfile($key, $profile);
	}

	protected function updateProfile($key, $profile)
	{
		$visits = $profile->visits + 1;
		$whitelisted = $visits <= $this->max_visits;
		$expire = $profile->expire;
		$this->client->set($key, json_encode(compact('visits', 'whitelisted', 'expire')));
	}

	protected function fresh($key)
	{
		$this->client->del($key);
		$this->client->set($key, json_encode([
			  'visits' => 1,
			  'whitelisted' => true,
			  'expire' => time() + $this->interval_seconds
			]));
	}

} 