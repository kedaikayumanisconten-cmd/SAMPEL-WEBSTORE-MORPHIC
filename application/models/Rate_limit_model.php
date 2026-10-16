<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate_limit_model
 *
 * Application level brute force protection backed by the `rate_limits` table.
 * It is intentionally simple: per IP + endpoint counters inside a rolling
 * window, escalating into a temporary block.
 */
class Rate_limit_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'rate_limits';

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Public API
	// ---------------------------------------------------------------------

	/**
	 * Is the current IP allowed to hit the endpoint right now?
	 *
	 * @param  string   $endpoint
	 * @param  string|null $ip Override for testing.
	 * @return array{allowed: bool, retry_after: int, attempts: int}
	 */
	public function check($endpoint, $ip = NULL)
	{
		$row = $this->row($endpoint, $ip === NULL ? $this->client_ip() : $ip);

		if ( ! $row)
		{
			return array('allowed' => TRUE, 'retry_after' => 0, 'attempts' => 0);
		}

		if ( ! empty($row['blocked_until']) && strtotime($row['blocked_until']) > time())
		{
			return array(
				'allowed'     => FALSE,
				'retry_after' => strtotime($row['blocked_until']) - time(),
				'attempts'    => (int) $row['attempts'],
			);
		}

		// A successful window that has fully decayed is treated as clean.
		if ($this->window_expired($row['last_attempt_at']))
		{
			return array('allowed' => TRUE, 'retry_after' => 0, 'attempts' => 0);
		}

		return array('allowed' => TRUE, 'retry_after' => 0, 'attempts' => (int) $row['attempts']);
	}

	/**
	 * Record a failed attempt and return the current state.
	 *
	 * @param  string $endpoint
	 * @return array{allowed: bool, retry_after: int, attempts: int}
	 */
	public function hit($endpoint)
	{
		$ip   = $this->client_ip();
		$row  = $this->row($endpoint, $ip);
		$now  = date('Y-m-d H:i:s');
		$max  = $this->max_attempts($endpoint);

		if ( ! $row)
		{
			$this->db->insert($this->table, array(
				'ip_address'      => $ip,
				'endpoint'        => $endpoint,
				'attempts'        => 1,
				'blocked_until'   => NULL,
				'last_attempt_at' => $now,
			));

			return array('allowed' => TRUE, 'retry_after' => 0, 'attempts' => 1);
		}

		$attempts = $this->window_expired($row['last_attempt_at']) ? 1 : ((int) $row['attempts'] + 1);

		$blocked_until = NULL;

		if ($attempts >= $max)
		{
			$blocked_until = date('Y-m-d H:i:s', time() + (int) $this->config->item('rate_limit_block_seconds'));
		}

		$this->db->where('id', (int) $row['id'])->update($this->table, array(
			'attempts'        => $attempts,
			'blocked_until'   => $blocked_until,
			'last_attempt_at' => $now,
		));

		return array(
			'allowed'     => ($blocked_until === NULL),
			'retry_after' => $blocked_until === NULL ? 0 : (int) $this->config->item('rate_limit_block_seconds'),
			'attempts'    => $attempts,
		);
	}

	/**
	 * Clear the counter after a successful action.
	 *
	 * @param  string $endpoint
	 * @return void
	 */
	public function clear($endpoint)
	{
		$this->db
			->where('ip_address', $this->client_ip())
			->where('endpoint', $endpoint)
			->delete($this->table);
	}

	/**
	 * Purge stale rows (called opportunistically, cheap to run).
	 *
	 * @return void
	 */
	public function purge_expired()
	{
		$this->db
			->where('last_attempt_at <', date('Y-m-d H:i:s', time() - 86400))
			->where('blocked_until IS NULL', NULL, FALSE)
			->delete($this->table);
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * @param  string $endpoint
	 * @param  string $ip
	 * @return array|null
	 */
	protected function row($endpoint, $ip)
	{
		$row = $this->db
			->where('ip_address', $ip)
			->where('endpoint', $endpoint)
			->get($this->table)
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Has the rolling window fully elapsed since the last attempt?
	 *
	 * @param  string $last_attempt_at
	 * @return bool
	 */
	protected function window_expired($last_attempt_at)
	{
		$window = (int) $this->config->item('rate_limit_window_seconds');

		if ($window <= 0)
		{
			return FALSE;
		}

		return strtotime($last_attempt_at) < (time() - $window);
	}

	/**
	 * Attempt ceiling for an endpoint (falls back to 5).
	 *
	 * @param  string $endpoint
	 * @return int
	 */
	protected function max_attempts($endpoint)
	{
		$limits = $this->config->item('rate_limit_max_attempts');

		if (is_array($limits) && isset($limits[$endpoint]))
		{
			return max(1, (int) $limits[$endpoint]);
		}

		return 5;
	}

	/**
	 * Request IP, truncated to the column width.
	 *
	 * @return string
	 */
	public function client_ip()
	{
		return substr((string) $this->input->ip_address(), 0, 45);
	}
}
