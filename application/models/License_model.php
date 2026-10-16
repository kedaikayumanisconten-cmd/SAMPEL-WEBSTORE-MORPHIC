<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * License_model
 *
 * Issues serial numbers and download tokens once an order is paid, and powers
 * the protected digital library / download endpoint.
 */
class License_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'user_licenses';

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Issuing
	// ---------------------------------------------------------------------

	/**
	 * Issue one license per order item, skipping rows that already exist so
	 * the method is safe to call twice for the same order.
	 *
	 * @param  int $order_id
	 * @return int Number of licenses created
	 */
	public function issue_for_order($order_id)
	{
		$order = $this->db->get_where('orders', array('id' => (int) $order_id))->row_array();

		if ( ! $order)
		{
			return 0;
		}

		$items    = $this->order_items($order_id);
		$created  = 0;
		$max      = (int) $this->config->item('default_max_downloads');

		foreach ($items as $item)
		{
			if ($this->exists($order_id, $item['product_id']))
			{
				continue;
			}

			$requires_key = (int) $item['requires_license_key'] === 1;

			$this->db->insert($this->table, array(
				'user_id'        => (int) $order['user_id'],
				'order_id'       => (int) $order_id,
				'product_id'     => (int) $item['product_id'],
				'license_key'    => $requires_key
					? $this->unique_license_key($item['slug'])
					: $this->free_license_key($item['id']),
				'download_token' => $this->unique_download_token(),
				'download_count' => 0,
				'max_downloads'  => $max > 0 ? $max : 10,
				'is_revoked'     => 0,
				'created_at'     => date('Y-m-d H:i:s'),
			));

			$created++;
		}

		return $created;
	}

	/**
	 * Order item lines, including the columns needed for license issuing.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	protected function order_items($order_id)
	{
		return $this->db
			->select('oi.id, oi.product_id, p.slug, p.requires_license_key')
			->from('order_items oi')
			->join('products p', 'p.id = oi.product_id', 'left')
			->where('oi.order_id', (int) $order_id)
			->order_by('oi.id', 'ASC')
			->get()
			->result_array();
	}

	/**
	 * Does a license already exist for this order + product pair?
	 *
	 * @param  int $order_id
	 * @param  int $product_id
	 * @return bool
	 */
	public function exists($order_id, $product_id)
	{
		return $this->db
			->where('order_id', (int) $order_id)
			->where('product_id', (int) $product_id)
			->count_all_results($this->table) > 0;
	}

	/**
	 * Generate a serial number in the MPH-XXXX-0000-0000-YYYY shape.
	 *
	 * @param  string|null $slug
	 * @return string
	 */
	protected function unique_license_key($slug = NULL)
	{
		$prefix = (string) $this->config->item('license_key_prefix');
		$tag    = $this->slug_tag($slug);

		do
		{
			$key = sprintf(
				'%s-%s-%04d-%04d-%s',
				$prefix,
				$tag,
				random_int(1000, 9999),
				random_int(1000, 9999),
				$this->random_letters(4)
			);
		}
		while ($this->key_taken($key));

		return $key;
	}

	/**
	 * Products that do not require a license still get a library entry, using
	 * a deterministic placeholder serial.
	 *
	 * @param  int $order_item_id
	 * @return string
	 */
	protected function free_license_key($order_item_id)
	{
		return 'NO-KEY-REQUIRED-ITEM-'.str_pad((string) $order_item_id, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * @param  string $key
	 * @return bool
	 */
	protected function key_taken($key)
	{
		return $this->db->where('license_key', $key)->count_all_results($this->table) > 0;
	}

	/**
	 * Create a random download token.
	 *
	 * @return string
	 */
	public function unique_download_token()
	{
		$prefix = (string) $this->config->item('download_token_prefix');

		do
		{
			$token = $prefix.bin2hex(random_bytes(16));
		}
		while ($this->db->where('download_token', $token)->count_all_results($this->table) > 0);

		return $token;
	}

	/**
	 * Replace a token (used to invalidate shared download links).
	 *
	 * @param  int $license_id
	 * @return string|false
	 */
	public function regenerate_token($license_id)
	{
		$token = $this->unique_download_token();

		$updated = $this->db->where('id', (int) $license_id)->update($this->table, array(
			'download_token' => $token,
		));

		return $updated ? $token : FALSE;
	}

	// ---------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------

	/**
	 * Fetch a license with its product attached, by download token.
	 *
	 * @param  string $token
	 * @return array|null
	 */
	public function get_by_token($token)
	{
		$row = $this->db
			->select('l.*, p.title, p.slug, p.file_path, p.file_format, p.file_size_mb,
					p.version, p.requires_license_key, p.thumbnail_url, p.preview_type, p.preview_url')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->where('l.download_token', $token)
			->get()
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fetch a single license row with product data.
	 *
	 * @param  int $id
	 * @return array|null
	 */
	public function get($id)
	{
		$row = $this->db
			->select('l.*, p.title, p.slug, p.file_path, p.file_format, p.file_size_mb,
					p.version, p.requires_license_key, p.thumbnail_url, p.preview_type, p.preview_url')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->where('l.id', (int) $id)
			->get()
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fully populated license row, verified to belong to a user.
	 *
	 * @param  int $license_id
	 * @param  int $user_id
	 * @return array|null
	 */
	public function get_for_user($license_id, $user_id)
	{
		$row = $this->db
			->select('l.*, p.title, p.slug, p.file_path, p.file_format, p.file_size_mb,
					p.version, p.requires_license_key, p.system_requirements, p.thumbnail_url,
					p.preview_type, p.preview_url, o.order_number')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->join('orders o', 'o.id = l.order_id', 'left')
			->where('l.id', (int) $license_id)
			->where('l.user_id', (int) $user_id)
			->get()
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * The customer's digital library.
	 *
	 * @param  int $user_id
	 * @param  int $limit
	 * @param  int $offset
	 * @return array
	 */
	public function get_library($user_id, $limit = 20, $offset = 0)
	{
		return $this->db
			->select('l.*, p.title, p.slug, p.file_format, p.file_size_mb, p.version,
					p.requires_license_key, p.thumbnail_url, p.preview_type, p.preview_url,
					o.order_number, o.paid_at, c.name AS category_name')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->join('orders o', 'o.id = l.order_id', 'left')
			->join('categories c', 'c.id = p.category_id', 'left')
			->where('l.user_id', (int) $user_id)
			->order_by('l.created_at', 'DESC')
			->limit((int) $limit, (int) $offset)
			->get()
			->result_array();
	}

	/**
	 * Count the customer's library entries.
	 *
	 * @param  int $user_id
	 * @return int
	 */
	public function count_library($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);
	}

	/**
	 * Licenses belonging to one order.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function get_by_order($order_id)
	{
		return $this->db
			->select('l.*, p.title, p.slug')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->where('l.order_id', (int) $order_id)
			->get()
			->result_array();
	}

	/**
	 * Does the user already own a product?
	 *
	 * @param  int $user_id
	 * @param  int $product_id
	 * @return bool
	 */
	public function user_owns($user_id, $product_id)
	{
		return $this->db
			->where('user_id', (int) $user_id)
			->where('product_id', (int) $product_id)
			->where('is_revoked', 0)
			->count_all_results($this->table) > 0;
	}

	/**
	 * Every product id the user holds a non-revoked license for, as a flat
	 * list of integers (used to hide buy buttons and block double purchases).
	 *
	 * @param  int $user_id
	 * @return array
	 */
	public function owned_product_ids($user_id)
	{
		$rows = $this->db
			->distinct()
			->select('product_id')
			->where('user_id', (int) $user_id)
			->where('is_revoked', 0)
			->get($this->table)
			->result_array();

		$ids = array();

		foreach ($rows as $row)
		{
			$ids[] = (int) $row['product_id'];
		}

		return $ids;
	}

	/**
	 * Admin listing of licenses.
	 *
	 * @param  array $filters q, status
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function get_admin_listing(array $filters = array(), $limit = 15, $offset = 0)
	{
		$this->_admin_select();
		$this->_apply_filters($filters);

		return $this->db->order_by('l.created_at', 'DESC')->limit((int) $limit, (int) $offset)->get()->result_array();
	}

	/**
	 * @param  array $filters
	 * @return int
	 */
	public function count_admin_listing(array $filters = array())
	{
		$this->db
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->join('users u', 'u.id = l.user_id', 'left');
		$this->_apply_filters($filters);

		return $this->db->count_all_results();
	}

	// ---------------------------------------------------------------------
	// Download quota
	// ---------------------------------------------------------------------

	/**
	 * Remaining downloads for a license row.
	 *
	 * @param  array $license
	 * @return int
	 */
	public function remaining_downloads(array $license)
	{
		return max(0, (int) $license['max_downloads'] - (int) $license['download_count']);
	}

	/**
	 * Is this license currently downloadable?
	 *
	 * @param  array $license
	 * @return bool
	 */
	public function is_downloadable(array $license)
	{
		return (int) $license['is_revoked'] === 0 && $this->remaining_downloads($license) > 0;
	}

	/**
	 * Record a successful download.
	 *
	 * @param  int $license_id
	 * @return void
	 */
	public function register_download($license_id)
	{
		$this->db->where('id', (int) $license_id)->update($this->table, array(
			'download_count'     => (int) $this->current_count($license_id) + 1,
			'last_downloaded_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Current counter value for a license.
	 *
	 * @param  int $license_id
	 * @return int
	 */
	protected function current_count($license_id)
	{
		$row = $this->db->select('download_count')->get_where($this->table, array('id' => (int) $license_id))->row_array();

		return $row ? (int) $row['download_count'] : 0;
	}

	/**
	 * Revoke or restore a license.
	 *
	 * @param  int  $license_id
	 * @param  bool $revoked
	 * @return bool
	 */
	public function set_revoked($license_id, $revoked)
	{
		return $this->db->where('id', (int) $license_id)->update($this->table, array(
			'is_revoked' => $revoked ? 1 : 0,
		));
	}

	/**
	 * Extend a license download quota.
	 *
	 * @param  int $license_id
	 * @param  int $max
	 * @return bool
	 */
	public function set_max_downloads($license_id, $max)
	{
		return $this->db->where('id', (int) $license_id)->update($this->table, array(
			'max_downloads' => max(0, (int) $max),
		));
	}

	/**
	 * Aggregate counters for the admin dashboard.
	 *
	 * @return array
	 */
	public function stats()
	{
		$row = $this->db
			->select('COUNT(*) AS total, SUM(is_revoked = 0) AS active, SUM(download_count) AS downloads')
			->get($this->table)
			->row_array();

		return array(
			'total'     => (int) ($row['total'] ?? 0),
			'active'    => (int) ($row['active'] ?? 0),
			'downloads' => (int) ($row['downloads'] ?? 0),
		);
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
			->select('l.*, p.title, p.slug, p.file_format, p.file_size_mb, p.thumbnail_url, u.full_name, u.email, o.order_number')
			->from($this->table.' l')
			->join('products p', 'p.id = l.product_id', 'left')
			->join('users u', 'u.id = l.user_id', 'left')
			->join('orders o', 'o.id = l.order_id', 'left');
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
				->like('l.license_key', $term)
				->or_like('p.title', $term)
				->or_like('u.email', $term)
				->or_like('o.order_number', $term)
				->group_end();
		}

		if (isset($filters['status']) && $filters['status'] !== '')
		{
			$this->db->where('l.is_revoked', (int) $filters['status']);
		}
	}

	/**
	 * Derive a 4 letter tag from a product slug.
	 *
	 * @param  string|null $slug
	 * @return string
	 */
	protected function slug_tag($slug)
	{
		$slug = preg_replace('/[^a-z0-9]/', '', strtolower((string) $slug));

		if ($slug === '')
		{
			return $this->random_letters(4);
		}

		$tag = strtoupper(substr($slug, 0, 4));

		return str_pad($tag, 4, 'X');
	}

	/**
	 * Uppercase random letters.
	 *
	 * @param  int $length
	 * @return string
	 */
	protected function random_letters($length = 4)
	{
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		$out      = '';

		for ($i = 0; $i < $length; $i++)
		{
			$out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
		}

		return $out;
	}
}
