<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin product management: catalog CRUD, thumbnail sourcing (upload, URL or
 * Unsplash) and the protected installer path.
 */
class Products extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('upload');
		$this->load->library('unsplash');
	}

	// ---------------------------------------------------------------------
	// Listing
	// ---------------------------------------------------------------------

	public function index()
	{
		$filters = array(
			'q'           => trim((string) $this->input->get('q')),
			'category_id' => (int) $this->input->get('category_id'),
			'status'      => $this->input->get('status') === NULL ? '' : (string) $this->input->get('status'),
			'featured'    => (int) $this->input->get('featured'),
			'sort'        => 'newest',
		);

		$per_page = (int) $this->config->item('admin_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->product_model->count_admin_listing($filters);

		$this->render('admin/products/index', array(
			'products'   => $this->product_model->get_admin_listing($filters, $per_page, ($page - 1) * $per_page),
			'filters'    => $filters,
			'categories' => $this->category_model->get_all(),
			'total'      => $total,
			'pagination' => $this->paginate($total, $per_page, $page),
		), 'Produk');
	}

	// ---------------------------------------------------------------------
	// Create
	// ---------------------------------------------------------------------

	public function create()
	{
		$this->render_form(NULL);
	}

	public function store()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/products');
		}

		$this->validate();

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			$this->render_form(NULL);

			return;
		}

		$input = $this->collect_input($this->input->post(NULL, TRUE));
		$thumb = $this->resolve_thumbnail($input['thumbnail_url'], $input['thumbnail_credit'], NULL);

		if ($thumb === FALSE)
		{
			$this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
			$this->render_form(NULL);

			return;
		}

		$input = array_merge($input, $thumb);
		$input['file_path'] = $this->resolve_installer($input['file_path'], 'create');

		$product_id = $this->product_model->create($input);

		$this->redirect_with('admin/products/edit/'.$product_id, 'success', 'Produk berhasil ditambahkan.');
	}

	// ---------------------------------------------------------------------
	// Update
	// ---------------------------------------------------------------------

	public function edit($product_id)
	{
		$product = $this->product_model->get((int) $product_id);

		if ( ! $product)
		{
			$this->session->set_flashdata('error', 'Produk tidak ditemukan.');
			redirect('admin/products');
		}

		$this->render_form($product);
	}

	public function update($product_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/products');
		}

		$product = $this->product_model->get((int) $product_id);

		if ( ! $product)
		{
			$this->session->set_flashdata('error', 'Produk tidak ditemukan.');
			redirect('admin/products');
		}

		$this->validate();

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			$this->render_form($product);

			return;
		}

		$input = $this->collect_input($this->input->post(NULL, TRUE));
		$thumb = $this->resolve_thumbnail($input['thumbnail_url'], $input['thumbnail_credit'], $product);

		if ($thumb === FALSE)
		{
			$this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
			$this->render_form($product);

			return;
		}

		$input = array_merge($input, $thumb);
		$input['file_path'] = $this->resolve_installer($input['file_path'], 'update');

		$this->product_model->update($product['id'], $input);

		$this->redirect_with('admin/products/edit/'.$product['id'], 'success', 'Produk berhasil diperbarui.');
	}

	// ---------------------------------------------------------------------
	// Actions
	// ---------------------------------------------------------------------

	public function toggle($product_id, $column)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/products');
		}

		if ( ! in_array($column, array('is_featured', 'is_active'), TRUE))
		{
			$this->redirect_with('admin/products', 'error', 'Aksi tidak dikenal.');
		}

		if ($this->product_model->toggle((int) $product_id, $column))
		{
			$this->redirect_with('admin/products', 'success', 'Status produk diperbarui.');
		}

		$this->redirect_with('admin/products', 'error', 'Gagal memperbarui produk.');
	}

	public function delete($product_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/products');
		}

		$product = $this->product_model->get((int) $product_id);

		if ( ! $product)
		{
			$this->redirect_with('admin/products', 'error', 'Produk tidak ditemukan.');
		}

		if ( ! $this->product_model->delete($product['id']))
		{
			$this->redirect_with('admin/products', 'error', 'Produk tidak dapat dihapus karena sudah pernah masuk pesanan. Nonaktifkan saja agar tidak tampil di toko.');
		}

		$this->remove_local_thumbnail($product['thumbnail_url']);

		$this->redirect_with('admin/products', 'success', 'Produk dihapus.');
	}

	// ---------------------------------------------------------------------
	// Unsplash proxy (AJAX)
	// ---------------------------------------------------------------------

	/**
	 * JSON endpoint used by the Unsplash tab of the product form. The access
	 * key never leaves the server.
	 *
	 * Returns the refreshed CSRF token, because csrf_regenerate is enabled.
	 *
	 * @return void
	 */
	public function unsplash_search()
	{
		$query = trim((string) $this->input->get('q', TRUE));

		if ($query === '')
		{
			$query = trim((string) $this->input->post('q', TRUE));
		}

		$page = max(1, (int) ($this->input->get('page') ?: $this->input->post('page')));

		$result = $this->unsplash->is_configured()
			? $this->unsplash->search($query, $page)
			: FALSE;

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'configured' => $this->unsplash->is_configured(),
				'query'      => $query,
				'error'      => $this->unsplash->last_error(),
				'photos'     => $result ? $result['photos'] : array(),
				'page'       => $result ? $result['page'] : 1,
				'total_pages' => $result ? $result['total_pages'] : 0,
				'quick_picks' => $this->config->item('unsplash_quick_picks'),
				'csrf'       => $this->security->get_csrf_hash(),
			)));
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Render the shared create/edit form.
	 *
	 * @param  array|null $product
	 * @return void
	 */
	protected function render_form($product)
	{
		$keywords = $this->config->item('unsplash_keywords');

		$this->render('admin/products/form', array(
			'product'      => $product,
			'categories'   => $this->category_model->options(),
			'is_edit'      => $product !== NULL,
			'unsplash_ready' => $this->unsplash->is_configured(),
			'unsplash_keywords' => $keywords,
			'quick_picks'  => $this->config->item('unsplash_quick_picks'),
			'max_upload'   => ini_get('upload_max_filesize'),
		), $product ? 'Edit: '.$product['title'] : 'Tambah Produk');
	}

	/**
	 * Shared validation rules.
	 *
	 * @return void
	 */
	protected function validate()
	{
		$this->form_validation->set_rules('title', 'Judul Produk', 'trim|required|min_length[4]|max_length[150]');
		$this->form_validation->set_rules('category_id', 'Kategori', 'required|integer');
		$this->form_validation->set_rules('short_description', 'Deskripsi Singkat', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('description', 'Deskripsi', 'trim|required');
		$this->form_validation->set_rules('price', 'Harga', 'trim|required|numeric|greater_than_equal_to[0]');
		$this->form_validation->set_rules('version', 'Versi', 'trim|required|max_length[30]');
		$this->form_validation->set_rules('os_support', 'Dukungan OS', 'required|in_list[windows,macos,both,cross_platform]');
		$this->form_validation->set_rules('file_format', 'Format File', 'trim|required|max_length[120]');
		$this->form_validation->set_rules('file_size_mb', 'Ukuran File (MB)', 'trim|required|numeric|greater_than_equal_to[0]');
		$this->form_validation->set_rules('preview_type', 'Tipe Preview', 'required|in_list[audio,video,image,none]');
		$this->form_validation->set_rules('file_path', 'Path File Produk', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('thumbnail_url', 'URL Gambar', 'trim|max_length[255]');
		$this->form_validation->set_rules('thumbnail_credit', 'Kredit Gambar', 'trim|max_length[255]');
	}

	/**
	 * Normalise the posted form into the shape the model expects.
	 *
	 * @param  array $post
	 * @return array
	 */
	protected function collect_input(array $post)
	{
		$pick = function ($key, $default = '') use ($post) {
			return isset($post[$key]) ? trim((string) $post[$key]) : $default;
		};

		return array(
			'category_id'          => (int) $pick('category_id'),
			'title'                => $pick('title'),
			'slug'                 => $pick('slug'),
			'short_description'    => $pick('short_description'),
			'description'          => $pick('description'),
			'price'                => $pick('price', '0'),
			'version'              => $pick('version', '1.0.0'),
			'os_support'           => $pick('os_support', 'both'),
			'file_format'          => $pick('file_format'),
			'file_size_mb'         => $pick('file_size_mb', '0'),
			'system_requirements'  => $pick('system_requirements'),
			'thumbnail_url'        => $pick('thumbnail_url'),
			'thumbnail_credit'     => $pick('thumbnail_credit'),
			'preview_type'         => $pick('preview_type', 'none'),
			'preview_url'          => $pick('preview_url'),
			'file_path'            => $pick('file_path'),
			'requires_license_key' => isset($post['requires_license_key']) ? 1 : 0,
			'is_featured'          => isset($post['is_featured']) ? 1 : 0,
			'is_active'            => isset($post['is_active']) ? 1 : 0,
			'thumbnail_source'     => $pick('thumbnail_source', 'keep'),
		);
	}

	/**
	 * Decide the final thumbnail reference for the product.
	 *
	 * @param  string     $url    Current value from the form.
	 * @param  string     $credit Current credit text.
	 * @param  array|null $old    Existing product row (on update).
	 * @return array|false [thumbnail_url, thumbnail_credit] or FALSE on upload error
	 */
	protected function resolve_thumbnail($url, $credit, $old)
	{
		$source = (string) $this->input->post('thumbnail_source', TRUE);
		$old_url = $old ? $old['thumbnail_url'] : NULL;

		if ($source === 'none')
		{
			$this->remove_local_thumbnail($old_url);

			return array('thumbnail_url' => NULL, 'thumbnail_credit' => NULL);
		}

		// 'unsplash' is the picker tab, but the chosen photo is stored as a
		// plain remote URL, so both sources share this branch.
		if ($source === 'url' || $source === 'unsplash')
		{
			if ($url === '')
			{
				// Nothing usable submitted: keep whatever was there before.
				return array('thumbnail_url' => $old_url, 'thumbnail_credit' => $old ? $old['thumbnail_credit'] : NULL);
			}

			if ( ! is_remote_url($url) && ! asset_exists($url))
			{
				$this->session->set_flashdata('error', 'URL gambar harus diawali http:// atau https:// dan berupa gambar publik.');

				return FALSE;
			}

			if ($old_url && $old_url !== $url)
			{
				$this->remove_local_thumbnail($old_url);
			}

			return array(
				'thumbnail_url'    => $url,
				'thumbnail_credit' => $credit !== '' ? $credit : NULL,
			);
		}

		// $source === 'upload'
		if (empty($_FILES['thumbnail_file']['name']))
		{
			return array('thumbnail_url' => $old_url, 'thumbnail_credit' => $old ? $old['thumbnail_credit'] : NULL);
		}

		$upload_path = $this->config->item('upload_thumbnail_path');

		if ( ! is_dir(FCPATH.$upload_path))
		{
			@mkdir(FCPATH.$upload_path, 0755, TRUE);
		}

		$this->upload->initialize(array(
			'upload_path'   => FCPATH.$upload_path,
			'allowed_types' => 'jpg|jpeg|png|webp|gif|avif',
			'max_size'      => 4096,
			'file_name'     => 'product_'.date('Ymd_His'),
			'overwrite'     => FALSE,
		));

		if ( ! $this->upload->do_upload('thumbnail_file'))
		{
			return FALSE;
		}

		$file = $this->upload->data();

		if ($old_url)
		{
			$this->remove_local_thumbnail($old_url);
		}

		return array(
			'thumbnail_url'    => $upload_path.$file['file_name'],
			// A locally hosted file needs no external attribution.
			'thumbnail_credit' => NULL,
		);
	}

	/**
	 * Resolve the protected installer path, optionally from an upload.
	 *
	 * @param  string $current
	 * @param  string $mode create|update
	 * @return string
	 */
	protected function resolve_installer($current, $mode)
	{
		if (empty($_FILES['installer_file']['name']))
		{
			return $current;
		}

		$upload_path = $this->config->item('protected_path');

		if ( ! is_dir(FCPATH.$upload_path))
		{
			@mkdir(FCPATH.$upload_path, 0755, TRUE);
		}

		$this->upload->initialize(array(
			'upload_path'   => FCPATH.$upload_path,
			'allowed_types' => 'zip|rar|7z|dmg|pkg|exe|pdf|txt',
			'max_size'      => 204800, // 200 MB, limited further by php.ini
			'overwrite'     => FALSE,
		));

		if ($this->upload->do_upload('installer_file'))
		{
			$file = $this->upload->data();

			return $upload_path.$file['file_name'];
		}

		// Keep the manually entered path when the upload fails.
		return $current;
	}

	/**
	 * Delete a previously uploaded local thumbnail (never remote URLs).
	 *
	 * @param  string|null $url
	 * @return void
	 */
	protected function remove_local_thumbnail($url)
	{
		if (empty($url) || is_remote_url($url))
		{
			return;
		}

		$prefix = $this->config->item('upload_thumbnail_path');

		if (strpos($url, $prefix) === 0 && is_file(FCPATH.$url))
		{
			@unlink(FCPATH.$url);
		}
	}

	/**
	 * Shared admin pagination renderer.
	 *
	 * @param  int $total
	 * @param  int $per_page
	 * @param  int $page
	 * @return string
	 */
	protected function paginate($total, $per_page, $page)
	{
		$this->load->library('pagination');

		$this->pagination->initialize(array(
			'base_url'             => current_url(),
			'page_query_string'    => TRUE,
			'query_string_segment' => 'page',
			'reuse_query_string'   => TRUE,
			'total_rows'           => $total,
			'per_page'             => $per_page,
			'cur_page'             => $page,
			'use_page_numbers'     => TRUE,
			'num_links'            => 3,
			'full_tag_open'        => '<nav class="pagination">',
			'full_tag_close'       => '</nav>',
			'first_link'           => '&laquo;',
			'last_link'            => '&raquo;',
			'next_link'            => 'Berikutnya',
			'prev_link'            => 'Sebelumnya',
			'cur_tag_open'         => '<strong>',
			'cur_tag_close'        => '</strong>',
		));

		return $this->pagination->create_links();
	}
}
