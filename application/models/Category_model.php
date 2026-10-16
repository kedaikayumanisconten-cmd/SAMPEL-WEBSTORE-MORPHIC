<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Category_model
 *
 * Backed by the `categories` table. Provides the storefront navigation and
 * the admin CRUD operations.
 */
class Category_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'categories';

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------

	/**
	 * All active categories ordered for display.
	 *
	 * @return array
	 */
	public function get_active()
	{
		return $this->db
			->where('is_active', 1)
			->order_by('name', 'ASC')
			->get($this->table)
			->result_array();
	}

	/**
	 * Every category, active or not.
	 *
	 * @return array
	 */
	public function get_all()
	{
		return $this->db->order_by('name', 'ASC')->get($this->table)->result_array();
	}

	/**
	 * Active categories decorated with their product counters.
	 *
	 * @param  bool $only_active_products
	 * @return array
	 */
	public function get_with_counts($only_active_products = TRUE)
	{
		$this->db
			->select('c.*, COUNT(p.id) AS product_count')
			->from($this->table.' c')
			->join('products p', 'p.category_id = c.id'.($only_active_products ? ' AND p.is_active = 1' : ''), 'left')
			->where('c.is_active', 1)
			->group_by('c.id')
			->order_by('c.name', 'ASC');

		return $this->db->get()->result_array();
	}

	/**
	 * Find a category by id.
	 *
	 * @param  int $id
	 * @return array|null
	 */
	public function get($id)
	{
		$row = $this->db->get_where($this->table, array('id' => (int) $id))->row_array();

		return $row ?: NULL;
	}

	/**
	 * Find an active category by slug.
	 *
	 * @param  string $slug
	 * @return array|null
	 */
	public function get_by_slug($slug)
	{
		$row = $this->db->get_where($this->table, array(
			'slug'      => $slug,
			'is_active' => 1,
		))->row_array();

		return $row ?: NULL;
	}

	/**
	 * Options array (id => name) for admin select inputs.
	 *
	 * @return array
	 */
	public function options()
	{
		$options = array();

		foreach ($this->get_all() as $row)
		{
			$options[$row['id']] = $row['name'];
		}

		return $options;
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Insert a category.
	 *
	 * @param  array $input
	 * @return int
	 */
	public function create(array $input)
	{
		$data = array(
			'name'         => trim($input['name']),
			'slug'         => $this->make_slug($this->_slug_source($input)),
			'target_group' => $input['target_group'],
			'description'  => $input['description'] ?: NULL,
			'image_url'    => isset($input['image_url']) && trim((string) $input['image_url']) !== '' ? trim((string) $input['image_url']) : NULL,
			'image_credit' => isset($input['image_credit']) && trim((string) $input['image_credit']) !== '' ? trim((string) $input['image_credit']) : NULL,
			'is_active'    => isset($input['is_active']) ? 1 : 0,
			'created_at'   => date('Y-m-d H:i:s'),
		);

		$this->db->insert($this->table, $data);

		return (int) $this->db->insert_id();
	}

	/**
	 * Update a category.
	 *
	 * @param  int   $id
	 * @param  array $input
	 * @return bool
	 */
	public function update($id, array $input)
	{
		$data = array(
			'name'         => trim($input['name']),
			'slug'         => $this->make_slug($this->_slug_source($input), (int) $id),
			'target_group' => $input['target_group'],
			'description'  => $input['description'] ?: NULL,
			'image_url'    => isset($input['image_url']) && trim((string) $input['image_url']) !== '' ? trim((string) $input['image_url']) : NULL,
			'image_credit' => isset($input['image_credit']) && trim((string) $input['image_credit']) !== '' ? trim((string) $input['image_credit']) : NULL,
			'is_active'    => isset($input['is_active']) ? 1 : 0,
		);

		return $this->db->where('id', (int) $id)->update($this->table, $data);
	}

	/**
	 * Delete a category. Refuses when products still reference it.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function delete($id)
	{
		if ($this->product_count($id) > 0)
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $id)->delete($this->table);
	}

	/**
	 * Number of products inside a category.
	 *
	 * @param  int $id
	 * @return int
	 */
	public function product_count($id)
	{
		return (int) $this->db
			->where('category_id', (int) $id)
			->count_all_results('products');
	}

	/**
	 * Slugify a value and guarantee uniqueness.
	 *
	 * @param  string   $value
	 * @param  int|null $ignore_id
	 * @return string
	 */
	public function make_slug($value, $ignore_id = NULL)
	{
		$base = url_title($value, 'dash', TRUE);
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

	/**
	 * Resolve the slug source: explicit slug when given, else the name.
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

		return isset($input['name']) ? (string) $input['name'] : '';
	}
}
