<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Order_model
 *
 * Owns the checkout transaction: it creates the orders row together with its
 * order_items snapshot inside a DB transaction, then exposes the customer and
 * admin read/write operations.
 */
class Order_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'orders';

	/**
	 * Valid payment statuses, mirroring the ENUM column.
	 *
	 * @var array
	 */
	protected $statuses = array('pending', 'paid', 'failed', 'cancelled');

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Create an order from cart lines.
	 *
	 * @param  int    $user_id
	 * @param  array  $items          Each: product_id, title, price
	 * @param  string $payment_method
	 * @param  string $notes
	 * @return array|false Newly created order row, or FALSE on failure
	 */
	public function create_order($user_id, array $items, $payment_method = 'bank_transfer', $notes = '')
	{
		if (empty($items))
		{
			return FALSE;
		}

		$total = 0.0;

		foreach ($items as $item)
		{
			$total += (float) $item['price'];
		}

		$this->db->trans_begin();

		$this->db->insert($this->table, array(
			'order_number'   => $this->generate_order_number(),
			'user_id'        => (int) $user_id,
			'total_amount'   => $total,
			'payment_method' => $payment_method,
			'payment_status' => 'pending',
			'notes'          => $notes !== '' ? $notes : NULL,
			'created_at'     => date('Y-m-d H:i:s'),
		));

		$order_id = (int) $this->db->insert_id();

		foreach ($items as $item)
		{
			$this->db->insert('order_items', array(
				'order_id'               => $order_id,
				'product_id'             => (int) $item['product_id'],
				'product_title_snapshot' => $item['title'],
				'price_at_purchase'      => (float) $item['price'],
				'created_at'             => date('Y-m-d H:i:s'),
			));
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();

			return FALSE;
		}

		$this->db->trans_commit();

		return $this->get($order_id);
	}

	/**
	 * Mark an order as paid. Returns FALSE when the order is already paid so
	 * callers can avoid issuing duplicate licenses.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	public function mark_paid($order_id)
	{
		$order = $this->get($order_id);

		if ( ! $order || $order['payment_status'] === 'paid')
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $order_id)->update($this->table, array(
			'payment_status' => 'paid',
			'paid_at'        => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Change the payment status.
	 *
	 * @param  int    $order_id
	 * @param  string $status
	 * @return bool
	 */
	public function set_status($order_id, $status)
	{
		if ( ! in_array($status, $this->statuses, TRUE))
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $order_id)->update($this->table, array(
			'payment_status' => $status,
		));
	}

	/**
	 * Attach / replace the uploaded payment proof.
	 *
	 * @param  int    $order_id
	 * @param  string $relative_path
	 * @return bool
	 */
	public function attach_proof($order_id, $relative_path)
	{
		return $this->db->where('id', (int) $order_id)->update($this->table, array(
			'payment_proof' => $relative_path,
		));
	}

	/**
	 * Attach the buyer's checkout note.
	 *
	 * @param  int    $order_id
	 * @param  string $notes
	 * @return bool
	 */
	public function update_notes($order_id, $notes)
	{
		return $this->db->where('id', (int) $order_id)->update($this->table, array(
			'notes' => $notes !== '' ? $notes : NULL,
		));
	}

	// ---------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------

	/**
	 * Fetch an order row with the customer's basic details attached.
	 *
	 * @param  int $id
	 * @return array|null
	 */
	public function get($id)
	{
		$row = $this->db
			->select('o.*, u.full_name, u.email')
			->from($this->table.' o')
			->join('users u', 'u.id = o.user_id', 'left')
			->where('o.id', (int) $id)
			->get()
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fetch an order by its human readable order number.
	 *
	 * @param  string $number
	 * @return array|null
	 */
	public function get_by_number($number)
	{
		$row = $this->db->get_where($this->table, array('order_number' => $number))->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fetch an order which is guaranteed to belong to a given user.
	 *
	 * @param  int $order_id
	 * @param  int $user_id
	 * @return array|null
	 */
	public function get_for_user($order_id, $user_id)
	{
		$row = $this->db
			->select('o.*, u.full_name, u.email')
			->from($this->table.' o')
			->join('users u', 'u.id = o.user_id', 'left')
			->where('o.id', (int) $order_id)
			->where('o.user_id', (int) $user_id)
			->get()
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Order item lines with their current product data.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function get_items($order_id)
	{
		return $this->db
			->select('oi.*, p.slug, p.file_format, p.requires_license_key, p.thumbnail_url')
			->from('order_items oi')
			->join('products p', 'p.id = oi.product_id', 'left')
			->where('oi.order_id', (int) $order_id)
			->order_by('oi.id', 'ASC')
			->get()
			->result_array();
	}

	/**
	 * Paginated order history for one customer.
	 *
	 * @param  int $user_id
	 * @param  int $limit
	 * @param  int $offset
	 * @return array
	 */
	public function get_for_user_list($user_id, $limit = 10, $offset = 0)
	{
		return $this->db
			->where('user_id', (int) $user_id)
			->order_by('created_at', 'DESC')
			->limit((int) $limit, (int) $offset)
			->get($this->table)
			->result_array();
	}

	/**
	 * Count a customer's orders.
	 *
	 * @param  int $user_id
	 * @return int
	 */
	public function count_for_user($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);
	}

	/**
	 * Paginated admin listing with a status filter and search.
	 *
	 * @param  array $filters q, status, method
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function get_admin_listing(array $filters = array(), $limit = 15, $offset = 0)
	{
		$this->_admin_select();
		$this->_apply_filters($filters);

		return $this->db->order_by('o.created_at', 'DESC')->limit((int) $limit, (int) $offset)->get()->result_array();
	}

	/**
	 * Count rows for the admin listing.
	 *
	 * @param  array $filters
	 * @return int
	 */
	public function count_admin_listing(array $filters = array())
	{
		$this->db->from($this->table.' o')->join('users u', 'u.id = o.user_id', 'left');
		$this->_apply_filters($filters);

		return $this->db->count_all_results();
	}

	/**
	 * Count orders in a given status.
	 *
	 * @param  string $status
	 * @return int
	 */
	public function count_by_status($status)
	{
		return (int) $this->db->where('payment_status', $status)->count_all_results($this->table);
	}

	/**
	 * Latest orders for the admin dashboard.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function get_recent($limit = 6)
	{
		$this->_admin_select();

		return $this->db->order_by('o.created_at', 'DESC')->limit((int) $limit)->get()->result_array();
	}

	/**
	 * Dashboard aggregates.
	 *
	 * @return array
	 */
	public function stats()
	{
		$totals = $this->db
			->select('COUNT(*) AS total,
				SUM(payment_status = "paid") AS paid,
				SUM(payment_status = "pending") AS pending,
				SUM(payment_status = "failed") AS failed,
				SUM(payment_status = "cancelled") AS cancelled,
				SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) AS revenue')
			->get($this->table)
			->row_array();

		return array(
			'total'     => (int) ($totals['total'] ?? 0),
			'paid'      => (int) ($totals['paid'] ?? 0),
			'pending'   => (int) ($totals['pending'] ?? 0),
			'failed'    => (int) ($totals['failed'] ?? 0),
			'cancelled' => (int) ($totals['cancelled'] ?? 0),
			'revenue'   => (float) ($totals['revenue'] ?? 0),
		);
	}

	/**
	 * Order count per payment status, ready for the donut chart.
	 *
	 * @return array
	 */
	public function status_breakdown()
	{
		$rows = $this->db
			->select('payment_status, COUNT(*) AS total')
			->group_by('payment_status')
			->get($this->table)
			->result_array();

		$labels = array(
			'paid'      => 'Lunas',
			'pending'   => 'Menunggu',
			'failed'    => 'Gagal',
			'cancelled' => 'Dibatalkan',
			'refunded'  => 'Dana kembali',
		);

		$colors = array(
			'paid'      => '#34d399',
			'pending'   => '#fbbf24',
			'failed'    => '#f87171',
			'cancelled' => '#6a719b',
			'refunded'  => '#38bdf8',
		);

		$out = array();

		// Keep a stable, meaningful order instead of whatever SQL returns.
		foreach ($labels as $status => $label)
		{
			foreach ($rows as $row)
			{
				if ($row['payment_status'] !== $status)
				{
					continue;
				}

				$out[] = array(
					'label' => $label,
					'value' => (int) $row['total'],
					'color' => $colors[$status],
				);
			}
		}

		return $out;
	}

	/**
	 * Orders created per day, regardless of payment status. Pairs nicely with
	 * the revenue series: volume on the bars, money on the line.
	 *
	 * @param  int $days
	 * @return array
	 */
	public function orders_by_day($days = 14)
	{
		$days = max(1, (int) $days);

		$rows = $this->db
			->select('DATE(created_at) AS day, COUNT(*) AS orders, SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) AS value')
			->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-'.($days - 1).' days')))
			->group_by('DATE(created_at)')
			->order_by('day', 'ASC')
			->get($this->table)
			->result_array();

		$indexed = array();

		foreach ($rows as $row)
		{
			$indexed[$row['day']] = $row;
		}

		$series = array();

		for ($i = $days - 1; $i >= 0; $i--)
		{
			$day = date('Y-m-d', strtotime('-'.$i.' days'));

			$series[] = array(
				'day'   => $day,
				'label' => date('d/m', strtotime($day)),
				'value' => isset($indexed[$day]) ? (int) $indexed[$day]['orders'] : 0,
				'note'  => 'Rp '.number_format(isset($indexed[$day]) ? (float) $indexed[$day]['value'] : 0, 0, ',', '.').' lunas',
			);
		}

		return $series;
	}

	/**
	 * Latest paid orders, for the purchase history feed.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function get_recent_paid($limit = 5)
	{
		$this->_admin_select();
		$this->db->where('o.payment_status', 'paid');

		return $this->db->order_by('o.paid_at', 'DESC')->limit((int) $limit)->get()->result_array();
	}

	/**
	 * Daily revenue for the last N days (used by the admin dashboard chart).
	 *
	 * @param  int $days
	 * @return array
	 */
	public function revenue_by_day($days = 7)
	{
		$rows = $this->db
			->select('DATE(paid_at) AS day, SUM(total_amount) AS revenue, COUNT(*) AS orders')
			->where('payment_status', 'paid')
			->where('paid_at >=', date('Y-m-d 00:00:00', strtotime('-'.((int) $days - 1).' days')))
			->group_by('DATE(paid_at)')
			->order_by('day', 'ASC')
			->get($this->table)
			->result_array();

		$indexed = array();

		foreach ($rows as $row)
		{
			$indexed[$row['day']] = $row;
		}

		$series = array();

		for ($i = (int) $days - 1; $i >= 0; $i--)
		{
			$day = date('Y-m-d', strtotime('-'.$i.' days'));

			$series[] = array(
				'day'     => $day,
				'label'   => date('d/m', strtotime($day)),
				'revenue' => isset($indexed[$day]) ? (float) $indexed[$day]['revenue'] : 0.0,
				'orders'  => isset($indexed[$day]) ? (int) $indexed[$day]['orders'] : 0,
			);
		}

		return $series;
	}

	/**
	 * Build a unique, human readable order number: MPH-YYYYMMDD-XXXX.
	 *
	 * @return string
	 */
	public function generate_order_number()
	{
		$prefix = 'MPH-'.date('Ymd').'-';

		$last = $this->db
			->select('order_number')
			->like('order_number', $prefix, 'after')
			->order_by('id', 'DESC')
			->limit(1)
			->get($this->table)
			->row_array();

		$sequence = 1;

		if ($last && preg_match('/-(\d+)$/', $last['order_number'], $m))
		{
			$sequence = (int) $m[1] + 1;
		}

		$number = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

		// Guard against a race / manual numbering gap.
		while ($this->db->where('order_number', $number)->count_all_results($this->table) > 0)
		{
			$sequence++;
			$number = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
		}

		return $number;
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * @return void
	 */
	protected function _admin_select()
	{
		$this->db
			->select('o.*, u.full_name, u.email')
			->from($this->table.' o')
			->join('users u', 'u.id = o.user_id', 'left');
	}

	/**
	 * @param  array $filters
	 * @return void
	 */
	protected function _apply_filters(array $filters)
	{
		if ( ! empty($filters['q']))
		{
			$term = $filters['q'];

			$this->db->group_start()
				->like('o.order_number', $term)
				->or_like('u.full_name', $term)
				->or_like('u.email', $term)
				->group_end();
		}

		if ( ! empty($filters['status']))
		{
			$this->db->where('o.payment_status', $filters['status']);
		}

		if ( ! empty($filters['method']))
		{
			$this->db->where('o.payment_method', $filters['method']);
		}
	}
}
