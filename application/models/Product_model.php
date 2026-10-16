<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product_model
 *
 * Catalog queries for the storefront plus the admin CRUD operations.
 * Backed by the `products` table joined with `categories`.
 */
class Product_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'products';

	/**
	 * Columns the caller is allowed to submit.
	 *
	 * @var array
	 */
	protected $fillable = array(
		'category_id', 'title', 'slug', 'short_description', 'description',
		'price', 'version', 'os_support', 'file_format', 'file_size_mb',
		'system_requirements', 'thumbnail_url', 'thumbnail_credit', 'preview_type',
		'preview_url', 'file_path', 'requires_license_key', 'is_featured', 'is_active',
	);

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Read - storefront
	// ---------------------------------------------------------------------

	/**
	 * Paginated, filtered product listing for the public shop.
	 *
	 * @param  array $filters q, category_id, category_slug, target_group, os_support, featured, sort
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function get_storefront(array $filters = array(), $limit = 9, $offset = 0)
	{
		$this->_base_select();
		$this->_apply_filters($filters, TRUE);
		$this->_apply_sort($filters);

		return $this->db->limit((int) $limit, (int) $offset)->get()->result_array();
	}

	/**
	 * Count rows matching the storefront filters.
	 *
	 * @param  array $filters
	 * @return int
	 */
	public function count_storefront(array $filters = array())
	{
		$this->db->from($this->table.' p')->join('categories c', 'c.id = p.category_id', 'left');
		$this->_apply_filters($filters, TRUE);

		return $this->db->count_all_results();
	}

	/**
	 * Featured products for the landing page.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function get_featured($limit = 4)
	{
		$this->_base_select();
		$this->db
			->where('p.is_active', 1)
			->where('p.is_featured', 1)
			->order_by('p.created_at', 'DESC')
			->limit((int) $limit);

		return $this->db->get()->result_array();
	}

	/**
	 * Latest products across the whole catalog.
	 *
	 * @param  int $limit
	 * @param  int $exclude_id
	 * @return array
	 */
	public function get_latest($limit = 6, $exclude_id = 0)
	{
		$this->_base_select();
		$this->db->where('p.is_active', 1);

		if ($exclude_id > 0)
		{
			$this->db->where('p.id !=', (int) $exclude_id);
		}

		return $this->db->order_by('p.created_at', 'DESC')->limit((int) $limit)->get()->result_array();
	}

	/**
	 * Other products from the same category.
	 *
	 * @param  int $category_id
	 * @param  int $exclude_id
	 * @param  int $limit
	 * @return array
	 */
	public function get_related($category_id, $exclude_id = 0, $limit = 3)
	{
		$this->_base_select();
		$this->db
			->where('p.is_active', 1)
			->where('p.category_id', (int) $category_id)
			->where('p.id !=', (int) $exclude_id)
			->order_by('p.is_featured', 'DESC')
			->order_by('p.created_at', 'DESC')
			->limit((int) $limit);

		return $this->db->get()->result_array();
	}

	/**
	 * Fetch one product (active only by default) by slug.
	 *
	 * @param  string $slug
	 * @param  bool   $only_active
	 * @return array|null
	 */
	public function get_by_slug($slug, $only_active = TRUE)
	{
		$this->_base_select();
		$this->db->where('p.slug', $slug);

		if ($only_active)
		{
			$this->db->where('p.is_active', 1);
		}

		$row = $this->db->get()->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fetch one product by id.
	 *
	 * @param  int  $id
	 * @param  bool $only_active
	 * @return array|null
	 */
	public function get($id, $only_active = FALSE)
	{
		$this->_base_select();
		$this->db->where('p.id', (int) $id);

		if ($only_active)
		{
			$this->db->where('p.is_active', 1);
		}

		$row = $this->db->get()->row_array();

		return $row ?: NULL;
	}

	/**
	 * Fetch several products by id, keyed by id.
	 *
	 * @param  array $ids
	 * @return array
	 */
	public function get_many(array $ids)
	{
		$ids = array_filter(array_map('intval', $ids));

		if (empty($ids))
		{
			return array();
		}

		$rows   = $this->db->where_in('id', $ids)->get($this->table)->result_array();
		$keyed  = array();

		foreach ($rows as $row)
		{
			$keyed[(int) $row['id']] = $row;
		}

		return $keyed;
	}

	// ---------------------------------------------------------------------
	// Read - admin
	// ---------------------------------------------------------------------

	/**
	 * Paginated admin listing (includes inactive products).
	 *
	 * @param  array $filters q, category_id, status, featured, os_support
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function get_admin_listing(array $filters = array(), $limit = 15, $offset = 0)
	{
		$this->_base_select();
		$this->_apply_filters($filters, FALSE);
		$this->_apply_sort($filters);

		return $this->db->limit((int) $limit, (int) $offset)->get()->result_array();
	}

	/**
	 * Count rows for the admin listing.
	 *
	 * @param  array $filters
	 * @return int
	 */
	public function count_admin_listing(array $filters = array())
	{
		$this->db->from($this->table.' p')->join('categories c', 'c.id = p.category_id', 'left');
		$this->_apply_filters($filters, FALSE);

		return $this->db->count_all_results();
	}

	/**
	 * Aggregate catalog counters for the admin dashboard.
	 *
	 * @return array
	 */
	public function stats()
	{
		$row = $this->db
			->select('COUNT(*) AS total, SUM(is_active = 1) AS active, SUM(is_featured = 1) AS featured')
			->get($this->table)
			->row_array();

		return array(
			'total'    => (int) ($row['total'] ?? 0),
			'active'   => (int) ($row['active'] ?? 0),
			'featured' => (int) ($row['featured'] ?? 0),
		);
	}

	/**
	 * Best selling products based on paid orders.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function best_sellers($limit = 5)
	{
		return $this->db
			->select('p.id, p.title, p.slug, p.price, COUNT(oi.id) AS sold, SUM(oi.price_at_purchase) AS revenue')
			->from($this->table.' p')
			->join('order_items oi', 'oi.product_id = p.id', 'inner')
			->join('orders o', 'o.id = oi.order_id AND o.payment_status = "paid"', 'inner')
			->group_by('p.id')
			->order_by('sold', 'DESC')
			->limit((int) $limit)
			->get()
			->result_array();
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Insert a product.
	 *
	 * @param  array $input
	 * @return int
	 */
	public function create(array $input)
	{
		$data = $this->_prepare($input);
		$data['slug']       = $this->make_slug($this->_slug_source($input));
		$data['created_at'] = date('Y-m-d H:i:s');

		$this->db->insert($this->table, $data);

		return (int) $this->db->insert_id();
	}

	/**
	 * Update a product.
	 *
	 * @param  int   $id
	 * @param  array $input
	 * @return bool
	 */
	public function update($id, array $input)
	{
		$data = $this->_prepare($input);
		$data['slug'] = $this->make_slug($this->_slug_source($input), (int) $id);

		return $this->db->where('id', (int) $id)->update($this->table, $data);
	}

	/**
	 * Delete a product. Refuses when the product appears on an order.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function delete($id)
	{
		$used = $this->db->where('product_id', (int) $id)->count_all_results('order_items');

		if ($used > 0)
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $id)->delete($this->table);
	}

	/**
	 * Toggle a boolean column by name.
	 *
	 * @param  int    $id
	 * @param  string $column is_featured|is_active
	 * @return bool
	 */
	public function toggle($id, $column)
	{
		if ( ! in_array($column, array('is_featured', 'is_active'), TRUE))
		{
			return FALSE;
		}

		$product = $this->db->select($column)->get_where($this->table, array('id' => (int) $id))->row_array();

		if ( ! $product)
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $id)->update($this->table, array(
			$column => ((int) $product[$column] === 1) ? 0 : 1,
		));
	}

	/**
	 * Slugify and guarantee uniqueness inside `products`.
	 *
	 * @param  string   $value
	 * @param  int|null $ignore_id
	 * @return string
	 */
	public function make_slug($value, $ignore_id = NULL)
	{
		$base = url_title($value, 'dash', TRUE);
		$base = $base !== '' ? $base : 'product-'.random_int(1000, 9999);
		$slug = $base;
		$i    = 2;

		while ($this->slug_taken($slug, $ignore_id))
		{
			$slug = $base.'-'.$i;
			$i++;
		}

		return $slug;
	}

	/**
	 * @param  string   $slug
	 * @param  int|null $ignore_id
	 * @return bool
	 */
	public function slug_taken($slug, $ignore_id = NULL)
	{
		$this->db->where('slug', $slug);

		if ($ignore_id !== NULL)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}

		return $this->db->count_all_results($this->table) > 0;
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Shared SELECT used by every storefront/admin read.
	 *
	 * @return void
	 */
	protected function _base_select()
	{
		$this->db
			->select('p.*, c.name AS category_name, c.slug AS category_slug, c.target_group')
			->from($this->table.' p')
			->join('categories c', 'c.id = p.category_id', 'left');
	}

	/**
	 * Apply the shared filter set.
	 *
	 * @param  array $filters
	 * @param  bool  $only_active
	 * @return void
	 */
	protected function _apply_filters(array $filters, $only_active)
	{
		if ($only_active)
		{
			$this->db->where('p.is_active', 1);
		}
		elseif (isset($filters['status']) && $filters['status'] !== '')
		{
			$this->db->where('p.is_active', (int) $filters['status']);
		}

		if ( ! empty($filters['q']))
		{
			$term = $filters['q'];

			$this->db->group_start()
				->like('p.title', $term)
				->or_like('p.short_description', $term)
				->or_like('p.description', $term)
				->or_like('p.file_format', $term)
				->group_end();
		}

		if ( ! empty($filters['category_id']))
		{
			$this->db->where('p.category_id', (int) $filters['category_id']);
		}

		if ( ! empty($filters['category_slug']))
		{
			$this->db->where('c.slug', $filters['category_slug']);
		}

		if ( ! empty($filters['target_group']))
		{
			// A 'both' category serves creators and musicians alike.
			$this->db->group_start()
				->where('c.target_group', $filters['target_group'])
				->or_where('c.target_group', 'both')
				->group_end();
		}

		if ( ! empty($filters['os_support']))
		{
			if ($filters['os_support'] === 'cross_platform')
			{
				$this->db->where('p.os_support', 'cross_platform');
			}
			else
			{
				$this->db->where_in('p.os_support', array($filters['os_support'], 'both', 'cross_platform'));
			}
		}

		if ( ! empty($filters['featured']))
		{
			$this->db->where('p.is_featured', 1);
		}
	}

	/**
	 * Apply the requested ordering.
	 *
	 * @param  array $filters
	 * @return void
	 */
	protected function _apply_sort(array $filters)
	{
		switch (isset($filters['sort']) ? $filters['sort'] : 'newest')
		{
			case 'price_asc':
				$this->db->order_by('p.price', 'ASC');
				break;

			case 'price_desc':
				$this->db->order_by('p.price', 'DESC');
				break;

			case 'name':
				$this->db->order_by('p.title', 'ASC');
				break;

			case 'featured':
				$this->db->order_by('p.is_featured', 'DESC')->order_by('p.created_at', 'DESC');
				break;

			case 'newest':
			default:
				$this->db->order_by('p.created_at', 'DESC');
				break;
		}
	}

	/**
	 * Normalise posted product data into a DB row.
	 *
	 * @param  array $input
	 * @return array
	 */
	protected function _prepare(array $input)
	{
		$data = array();

		foreach ($this->fillable as $field)
		{
			if ( ! array_key_exists($field, $input))
			{
				continue;
			}

			$value = $input[$field];

			switch ($field)
			{
				case 'category_id':
					$data[$field] = (int) $value;
					break;

				case 'price':
				case 'file_size_mb':
					$data[$field] = (float) str_replace(',', '.', (string) $value);
					break;

				case 'requires_license_key':
				case 'is_featured':
				case 'is_active':
					$data[$field] = $value ? 1 : 0;
					break;

				case 'system_requirements':
				case 'thumbnail_url':
				case 'thumbnail_credit':
				case 'preview_url':
					$data[$field] = ($value === '' || $value === NULL ? NULL : $value);
					break;

				default:
					$data[$field] = is_string($value) ? trim($value) : $value;
					break;
			}
		}

		return $data;
	}

	/**
	 * Resolve the value used to build the product slug: an explicit slug when
	 * supplied, otherwise the product title.
	 *
	 * @param  array $input
	 * @return string
	 */
	protected function _slug_source(array $input)
	{
		if (isset($input['slug']) && trim((string) $input['slug']) !== '')
		{
			return trim((string) $input['slug']);
		}

		return isset($input['title']) ? (string) $input['title'] : '';
	}
}
