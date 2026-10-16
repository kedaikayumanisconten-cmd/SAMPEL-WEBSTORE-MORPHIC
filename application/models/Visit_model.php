<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Visit_model
 *
 * Traffic log behind the dashboard analytics. Every storefront page view is
 * appended to `page_visits`; the aggregation helpers below feed the charts
 * and the "Riwayat Kunjungan" listing.
 *
 * Bots are stored too (device = 'bot') so the raw log stays honest, but they
 * are excluded from the human visitor counters.
 */
class Visit_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'page_visits';

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Append a page view.
	 *
	 * @param  array $data
	 * @return bool
	 */
	public function record(array $data)
	{
		return $this->db->insert($this->table, array(
			'user_id'    => isset($data['user_id']) && $data['user_id'] ? (int) $data['user_id'] : NULL,
			'session_id' => isset($data['session_id']) ? substr((string) $data['session_id'], 0, 64) : NULL,
			'uri'        => substr((string) $data['uri'], 0, 160),
			'referer'    => isset($data['referer']) && $data['referer'] !== '' ? substr((string) $data['referer'], 0, 255) : NULL,
			'ip_address' => substr((string) $data['ip_address'], 0, 45),
			'user_agent' => isset($data['user_agent']) && $data['user_agent'] !== '' ? substr((string) $data['user_agent'], 0, 255) : NULL,
			'device'     => (string) $data['device'],
			'is_admin'   => ! empty($data['is_admin']) ? 1 : 0,
			'visited_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Drop rows older than the retention window so the log cannot grow
	 * forever. Called opportunistically (roughly once per day).
	 *
	 * @return void
	 */
	public function purge_old()
	{
		$days = (int) $this->config->item('visit_retention_days');

		if ($days <= 0)
		{
			return;
		}

		$this->db
			->where('visited_at <', date('Y-m-d H:i:s', strtotime('-'.$days.' days')))
			->delete($this->table);
	}

	/**
	 * Wipe the whole log (admin maintenance action).
	 *
	 * @return void
	 */
	public function truncate()
	{
		$this->db->truncate($this->table);
	}

	// ---------------------------------------------------------------------
	// Headline counters
	// ---------------------------------------------------------------------

	/**
	 * KPI block for the dashboard.
	 *
	 * @param  int $days
	 * @return array
	 */
	public function stats($days = 30)
	{
		$days   = max(1, (int) $days);
		$from   = $this->window_start($days);
		$humans = "device != 'bot'";

		$period = $this->db
			->select('COUNT(*) AS hits, COUNT(DISTINCT session_id) AS visitors, COUNT(DISTINCT ip_address) AS ips')
			->where('visited_at >=', $from)
			->where($humans, NULL, FALSE)
			->get($this->table)
			->row_array();

		$bots = $this->db
			->select('COUNT(*) AS hits')
			->where('visited_at >=', $from)
			->where('device', 'bot')
			->get($this->table)
			->row_array();

		$today = $this->db
			->select('COUNT(*) AS hits, COUNT(DISTINCT session_id) AS visitors')
			->where('visited_at >=', date('Y-m-d 00:00:00'))
			->where($humans, NULL, FALSE)
			->get($this->table)
			->row_array();

		$yesterday = $this->db
			->select('COUNT(*) AS hits')
			->where('visited_at >=', date('Y-m-d 00:00:00', strtotime('-1 day')))
			->where('visited_at <', date('Y-m-d 00:00:00'))
			->where($humans, NULL, FALSE)
			->get($this->table)
			->row_array();

		$members = $this->db
			->select('COUNT(*) AS hits')
			->where('visited_at >=', $from)
			->where('user_id IS NOT NULL', NULL, FALSE)
			->where($humans, NULL, FALSE)
			->get($this->table)
			->row_array();

		$hits      = (int) $period['hits'];
		$today_hits = (int) $today['hits'];
		$yday_hits  = (int) $yesterday['hits'];

		$trend = 0;

		if ($yday_hits > 0)
		{
			$trend = (int) round((($today_hits - $yday_hits) / $yday_hits) * 100);
		}
		elseif ($today_hits > 0)
		{
			$trend = 100;
		}

		return array(
			'window_days'    => $days,
			'hits'           => $hits,
			'today'          => $today_hits,
			'yesterday'      => $yday_hits,
			'trend'          => $trend,
			'unique'         => (int) $period['visitors'],
			'unique_today'   => (int) $today['visitors'],
			'ips'            => (int) $period['ips'],
			'bots'           => (int) $bots['hits'],
			'member_hits'    => (int) $members['hits'],
			'guest_hits'     => max(0, $hits - (int) $members['hits']),
			'per_day'        => $days > 0 ? round($hits / $days, 1) : 0,
			'total_rows'     => $this->count_all(array()),
		);
	}

	// ---------------------------------------------------------------------
	// Time series
	// ---------------------------------------------------------------------

	/**
	 * Visits + unique visitors per day.
	 *
	 * @param  int  $days
	 * @param  bool $include_bots
	 * @return array
	 */
	public function daily($days = 14, $include_bots = FALSE)
	{
		$days = max(1, (int) $days);

		$this->db
			->select('DATE(visited_at) AS day, COUNT(*) AS hits, COUNT(DISTINCT session_id) AS visitors')
			->where('visited_at >=', $this->window_start($days))
			->group_by('DATE(visited_at)')
			->order_by('day', 'ASC');

		if ( ! $include_bots)
		{
			$this->db->where('device !=', 'bot');
		}

		$rows = $this->db->get($this->table)->result_array();

		return $this->fill_days($rows, $days, array('hits' => 0, 'visitors' => 0), function ($row) {
			return array(
				'hits'     => (int) $row['hits'],
				'visitors' => (int) $row['visitors'],
			);
		});
	}

	/**
	 * Visits per hour of day (0-23), useful to spot peak traffic hours.
	 *
	 * @param  int $days
	 * @return array
	 */
	public function by_hour($days = 30)
	{
		$rows = $this->db
			->select('HOUR(visited_at) AS hour, COUNT(*) AS hits')
			->where('visited_at >=', $this->window_start($days))
			->where('device !=', 'bot')
			->group_by('HOUR(visited_at)')
			->get($this->table)
			->result_array();

		$indexed = array();

		foreach ($rows as $row)
		{
			$indexed[(int) $row['hour']] = (int) $row['hits'];
		}

		$series = array();

		for ($hour = 0; $hour < 24; $hour++)
		{
			$series[] = array(
				'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
				'value' => isset($indexed[$hour]) ? $indexed[$hour] : 0,
				'note'  => 'pukul '.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
			);
		}

		return $series;
	}

	// ---------------------------------------------------------------------
	// Breakdowns
	// ---------------------------------------------------------------------

	/**
	 * Most viewed pages.
	 *
	 * @param  int $days
	 * @param  int $limit
	 * @return array
	 */
	public function top_pages($days = 30, $limit = 6)
	{
		$rows = $this->db
			->select('uri, COUNT(*) AS hits, COUNT(DISTINCT session_id) AS visitors')
			->where('visited_at >=', $this->window_start($days))
			->where('device !=', 'bot')
			->group_by('uri')
			->order_by('hits', 'DESC')
			->limit(max(1, (int) $limit))
			->get($this->table)
			->result_array();

		$pages = array();

		foreach ($rows as $row)
		{
			$pages[] = array(
				'label' => visit_page_label($row['uri']),
				'value' => (int) $row['hits'],
				'sub'   => (int) $row['visitors'].' pengunjung',
				'uri'   => $row['uri'],
			);
		}

		return $pages;
	}

	/**
	 * Device split for the donut chart.
	 *
	 * @param  int  $days
	 * @param  bool $include_bots
	 * @return array
	 */
	public function devices($days = 30, $include_bots = FALSE)
	{
		$this->db
			->select('device, COUNT(*) AS hits')
			->where('visited_at >=', $this->window_start($days))
			->group_by('device')
			->order_by('hits', 'DESC');

		if ( ! $include_bots)
		{
			$this->db->where('device !=', 'bot');
		}

		$rows = $this->db->get($this->table)->result_array();
		$out  = array();

		foreach ($rows as $row)
		{
			$out[] = array(
				'label' => trim(strip_tags(str_replace('&#', '', device_label($row['device'])))),
				'value' => (int) $row['hits'],
			);
		}

		return $out;
	}

	/**
	 * Where visitors came from (external referers only).
	 *
	 * @param  int $days
	 * @param  int $limit
	 * @return array
	 */
	public function referrers($days = 30, $limit = 5)
	{
		$rows = $this->db
			->select('referer, COUNT(*) AS hits')
			->where('visited_at >=', $this->window_start($days))
			->where('device !=', 'bot')
			->where('referer IS NOT NULL', NULL, FALSE)
			->group_by('referer')
			->order_by('hits', 'DESC')
			->limit(max(1, (int) $limit))
			->get($this->table)
			->result_array();

		$out  = array();
		$host = parse_url(base_url(), PHP_URL_HOST);

		foreach ($rows as $row)
		{
			$ref_host = parse_url($row['referer'], PHP_URL_HOST);

			// Internal navigation is already covered by top_pages().
			if ($ref_host === NULL || $ref_host === $host)
			{
				continue;
			}

			$out[] = array(
				'label' => $ref_host,
				'value' => (int) $row['hits'],
			);
		}

		return array_slice($out, 0, max(1, (int) $limit));
	}

	/**
	 * Registered members vs guests.
	 *
	 * @param  int $days
	 * @return array
	 */
	public function audience_split($days = 30)
	{
		$row = $this->db
			->select('SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) AS guests, SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS members')
			->where('visited_at >=', $this->window_start($days))
			->where('device !=', 'bot')
			->get($this->table)
			->row_array();

		return array(
			array('label' => 'Pengunjung (guest)', 'value' => (int) $row['guests']),
			array('label' => 'Member masuk',       'value' => (int) $row['members']),
		);
	}

	// ---------------------------------------------------------------------
	// Listing
	// ---------------------------------------------------------------------

	/**
	 * Paginated raw log.
	 *
	 * @param  array $filters q, device, only_members
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function paginate(array $filters = array(), $limit = 20, $offset = 0)
	{
		$this->db
			->select('v.*, u.full_name, u.username, u.email, u.role')
			->from($this->table.' v')
			->join('users u', 'u.id = v.user_id', 'left')
			->order_by('v.visited_at', 'DESC')
			->limit(max(1, (int) $limit), max(0, (int) $offset));

		$this->apply_filters($filters);

		return $this->db->get()->result_array();
	}

	/**
	 * Total rows for the pagination library.
	 *
	 * @param  array $filters
	 * @return int
	 */
	public function count_all(array $filters = array())
	{
		$this->db->from($this->table.' v');
		$this->apply_filters($filters);

		return (int) $this->db->count_all_results();
	}

	/**
	 * Latest hits for the dashboard activity feed.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function recent($limit = 8)
	{
		return $this->db
			->select('v.*, u.full_name')
			->from($this->table.' v')
			->join('users u', 'u.id = v.user_id', 'left')
			->order_by('v.visited_at', 'DESC')
			->limit(max(1, (int) $limit))
			->get()
			->result_array();
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * @param  array $filters
	 * @return void
	 */
	protected function apply_filters(array $filters)
	{
		if ( ! empty($filters['device']) && in_array($filters['device'], array('desktop', 'mobile', 'tablet', 'bot'), TRUE))
		{
			$this->db->where('v.device', $filters['device']);
		}

		if ( ! empty($filters['only_members']))
		{
			$this->db->where('v.user_id IS NOT NULL', NULL, FALSE);
		}

		if ( ! empty($filters['q']))
		{
			$this->db
				->group_start()
					->like('v.uri', $filters['q'])
					->or_like('v.ip_address', $filters['q'])
					->or_like('u.full_name', $filters['q'])
					->or_like('u.email', $filters['q'])
				->group_end();
		}
	}

	/**
	 * Start of the rolling window (inclusive), as a datetime string.
	 *
	 * @param  int $days
	 * @return string
	 */
	protected function window_start($days)
	{
		return date('Y-m-d 00:00:00', strtotime('-'.(max(1, (int) $days) - 1).' days'));
	}

	/**
	 * Turn a sparse grouped result into a gapless day series.
	 *
	 * @param  array    $rows
	 * @param  int      $days
	 * @param  array    $defaults
	 * @param  callable $map
	 * @return array
	 */
	protected function fill_days(array $rows, $days, array $defaults, $map)
	{
		$indexed = array();

		foreach ($rows as $row)
		{
			$indexed[$row['day']] = $map($row);
		}

		$series = array();

		for ($i = (int) $days - 1; $i >= 0; $i--)
		{
			$day   = date('Y-m-d', strtotime('-'.$i.' days'));
			$value = isset($indexed[$day]) ? $indexed[$day] : $defaults;

			$series[] = array(
				'day'   => $day,
				'label' => date('d/m', strtotime($day)),
			) + $value;
		}

		return $series;
	}
}
