<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin category management.
 */
class Categories extends Admin_Controller
{
	public function index()
	{
		$categories = $this->category_model->get_all();

		foreach ($categories as $index => $category)
		{
			$categories[$index]['product_count'] = $this->category_model->product_count($category['id']);
		}

		$this->render('admin/categories/index', array(
			'categories' => $categories,
		), 'Kategori');
	}

	public function create()
	{
		$this->render_form(NULL);
	}

	public function store()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/categories');
		}

		$this->validate();

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			$this->render_form(NULL);

			return;
		}

		$this->category_model->create($this->collect_input());

		$this->redirect_with('admin/categories', 'success', 'Kategori berhasil ditambahkan.');
	}

	public function edit($category_id)
	{
		$category = $this->category_model->get((int) $category_id);

		if ( ! $category)
		{
			$this->redirect_with('admin/categories', 'error', 'Kategori tidak ditemukan.');
		}

		$this->render_form($category);
	}

	public function update($category_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/categories');
		}

		$category = $this->category_model->get((int) $category_id);

		if ( ! $category)
		{
			$this->redirect_with('admin/categories', 'error', 'Kategori tidak ditemukan.');
		}

		$this->validate();

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			$this->render_form($category);

			return;
		}

		$this->category_model->update($category['id'], $this->collect_input());

		$this->redirect_with('admin/categories', 'success', 'Kategori berhasil diperbarui.');
	}

	public function delete($category_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/categories');
		}

		$category = $this->category_model->get((int) $category_id);

		if ( ! $category)
		{
			$this->redirect_with('admin/categories', 'error', 'Kategori tidak ditemukan.');
		}

		if ( ! $this->category_model->delete($category['id']))
		{
			$this->redirect_with('admin/categories', 'error', 'Kategori masih dipakai oleh produk. Pindahkan produknya terlebih dahulu.');
		}

		$this->redirect_with('admin/categories', 'success', 'Kategori dihapus.');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	protected function validate()
	{
		$this->form_validation->set_rules('name', 'Nama Kategori', 'trim|required|min_length[3]|max_length[100]');
		$this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[120]');
		$this->form_validation->set_rules('target_group', 'Target Audiens', 'required|in_list[creator,musician,both]');
		$this->form_validation->set_rules('description', 'Deskripsi', 'trim|max_length[1000]');
		$this->form_validation->set_rules('image_url', 'URL Gambar', 'trim|max_length[255]');
		$this->form_validation->set_rules('image_credit', 'Kredit Gambar', 'trim|max_length[255]');
	}

	protected function collect_input()
	{
		return array(
			'name'         => (string) $this->input->post('name', TRUE),
			'slug'         => (string) $this->input->post('slug', TRUE),
			'target_group' => (string) $this->input->post('target_group', TRUE),
			'description'  => (string) $this->input->post('description', TRUE),
			'image_url'    => (string) $this->input->post('image_url', TRUE),
			'image_credit' => (string) $this->input->post('image_credit', TRUE),
			'is_active'    => (bool) $this->input->post('is_active'),
		);
	}

	protected function render_form($category)
	{
		$keywords = $this->config->item('unsplash_keywords');

		$this->render('admin/categories/form', array(
			'category'          => $category,
			'is_edit'           => $category !== NULL,
			'quick_picks'       => $this->config->item('unsplash_quick_picks'),
			'unsplash_keywords' => is_array($keywords) ? $keywords : array(),
		), $category ? 'Edit: '.$category['name'] : 'Tambah Kategori');
	}
}
