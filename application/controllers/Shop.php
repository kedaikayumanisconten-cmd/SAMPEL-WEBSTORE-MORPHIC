<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shop
 *
 * Public catalog: filtering, searching, sorting and pagination, plus the
 * product detail page.
 */
class Shop extends Frontend_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('pagination');
	}

	/**
	 * Catalog listing.
	 *
	 * URL shapes supported:
	 *   products?q=&category=&group=&os=&sort=&page=
	 *   category/{slug}
	 *
	 * @param  int         $offset         Unused (query string paging).
	 * @param  string|null $category_slug  Passed by the /category/{slug} route.
	 * @return void
	 */
	public function index($offset = 0, $category_slug = NULL)
	{
		$filters = $this->filters();

		if ($category_slug !== NULL && $category_slug !== '')
		{
			$filters['category_slug'] = $category_slug;
		}

		$per_page = (int) $this->config->item('products_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->product_model->count_storefront($filters);

		$products = $this->product_model->get_storefront(
			$filters,
			$per_page,
			($page - 1) * $per_page
		);

		$active_category = NULL;

		if ( ! empty($filters['category_slug']))
		{
			$active_category = $this->category_model->get_by_slug($filters['category_slug']);
		}

		$this->render('shop/index', array(
			'products'        => $products,
			'filters'         => $filters,
			'query_params'    => array(
				'q'        => $filters['q'],
				'category' => $filters['category_slug'],
				'group'    => $filters['target_group'],
				'os'       => $filters['os_support'],
				'sort'     => $filters['sort'],
			),
			'categories'      => $this->category_model->get_with_counts(),
			'active_category' => $active_category,
			'total_products'  => $total,
			'cart_ids'        => $this->cart_product_ids(),
			'pagination'      => $this->build_pagination($total, $per_page, $page),
			'page_heading'    => $active_category
				? $active_category['name']
				: ($filters['q'] !== '' ? 'Hasil pencarian: “'.$filters['q'].'”' : 'Semua Produk Digital'),
			'page_subheading' => $active_category && ! empty($active_category['description'])
				? $active_category['description']
				: 'Plugin, preset, LUT, dan utilitas audio untuk kreator konten serta musisi.',
		), $active_category ? $active_category['name'] : 'Katalog');
	}

	/**
	 * Product detail page.
	 *
	 * @param  string $slug
	 * @return void
	 */
	public function detail($slug)
	{
		$product = $this->product_model->get_by_slug($slug);

		if ( ! $product)
		{
			show_404();
		}

		$owned = $this->current_user
			? $this->license_model->user_owns($this->current_user['id'], $product['id'])
			: FALSE;

		$this->render('shop/detail', array(
			'product'         => $product,
			'related'         => $this->product_model->get_related($product['category_id'], $product['id'], 3),
			'cart_ids'        => $this->cart_product_ids(),
			'owned'           => $owned,
			'spec_rows'       => $this->spec_rows($product),
		), $product['title']);
	}

	/**
	 * Normalised filter set read from the query string.
	 *
	 * @return array
	 */
	protected function filters()
	{
		return array(
			'q'             => trim((string) $this->input->get('q')),
			'category_slug' => trim((string) $this->input->get('category')),
			'target_group'  => $this->allow($this->input->get('group'), array('creator', 'musician'), ''),
			'os_support'    => $this->allow($this->input->get('os'), array('windows', 'macos', 'cross_platform'), ''),
			'sort'          => $this->allow($this->input->get('sort'), array('newest', 'price_asc', 'price_desc', 'name', 'featured'), 'newest'),
		);
	}

	/**
	 * Whitelist a query string value.
	 *
	 * @param  string|null $value
	 * @param  array       $allowed
	 * @param  string      $default
	 * @return string
	 */
	protected function allow($value, array $allowed, $default)
	{
		$value = is_string($value) ? trim($value) : '';

		return in_array($value, $allowed, TRUE) ? $value : $default;
	}

	/**
	 * Configure and render the shared pagination component.
	 *
	 * @param  int $total
	 * @param  int $per_page
	 * @param  int $page
	 * @return string
	 */
	protected function build_pagination($total, $per_page, $page)
	{
		$this->pagination->initialize(array(
			'base_url'            => current_url(),
			'page_query_string'   => TRUE,
			'query_string_segment' => 'page',
			'reuse_query_string'  => TRUE,
			'total_rows'          => $total,
			'per_page'            => $per_page,
			'cur_page'            => $page,
			'use_page_numbers'    => TRUE,
			'num_links'           => 3,
			'full_tag_open'       => '<nav class="pagination">',
			'full_tag_close'      => '</nav>',
			'first_link'          => '&laquo;',
			'last_link'           => '&raquo;',
			'next_link'           => 'Berikutnya',
			'prev_link'           => 'Sebelumnya',
			'cur_tag_open'        => '<strong>',
			'cur_tag_close'       => '</strong>',
		));

		return $this->pagination->create_links();
	}

	/**
	 * Product ids currently in the session cart.
	 *
	 * @return array
	 */
	protected function cart_product_ids()
	{
		return array_map('intval', array_keys($this->cart()));
	}

	/**
	 * Technical specification rows shown on the detail page.
	 *
	 * @param  array $product
	 * @return array
	 */
	protected function spec_rows(array $product)
	{
		return array(
			'Versi'             => $product['version'],
			'Dukungan Sistem'   => os_label($product['os_support']),
			'Format File'       => $product['file_format'],
			'Ukuran Unduhan'    => file_size_label($product['file_size_mb']),
			'Serial Number'     => ((int) $product['requires_license_key'] === 1) ? 'Diperlukan' : 'Tidak diperlukan',
			'Kategori'          => $product['category_name'],
			'Diperbarui'        => datetime_id($product['updated_at'], FALSE),
		);
	}
}
