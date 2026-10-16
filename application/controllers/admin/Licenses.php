<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin license management: audit issued serials, revoke/restore downloads
 * and adjust the download quota.
 */
class Licenses extends Admin_Controller
{
	public function index()
	{
		$filters = array(
			'q'      => trim((string) $this->input->get('q')),
			'status' => (string) $this->input->get('status'),
		);

		$per_page = (int) $this->config->item('admin_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->license_model->count_admin_listing($filters);

		$this->render('admin/licenses/index', array(
			'licenses'   => $this->license_model->get_admin_listing($filters, $per_page, ($page - 1) * $per_page),
			'filters'    => $filters,
			'stats'      => $this->license_model->stats(),
			'total'      => $total,
			'pagination' => $this->paginate($total, $per_page, $page),
		), 'Lisensi');
	}

	public function update($license_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/licenses');
		}

		$license = $this->license_model->get((int) $license_id);

		if ( ! $license)
		{
			$this->redirect_with('admin/licenses', 'error', 'Lisensi tidak ditemukan.');
		}

		$revoked = (string) $this->input->post('is_revoked', TRUE) === '1';
		$max     = (int) $this->input->post('max_downloads');

		$this->license_model->set_revoked($license['id'], $revoked);
		$this->license_model->set_max_downloads($license['id'], $max);

		$this->redirect_with('admin/licenses', 'success', 'Lisensi diperbarui.');
	}

	/**
	 * Issue a brand new download token (invalidates shared links).
	 *
	 * @param  int $license_id
	 * @return void
	 */
	public function regenerate($license_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/licenses');
		}

		$license = $this->license_model->get((int) $license_id);

		if ( ! $license)
		{
			$this->redirect_with('admin/licenses', 'error', 'Lisensi tidak ditemukan.');
		}

		if ($this->license_model->regenerate_token($license['id']) === FALSE)
		{
			$this->redirect_with('admin/licenses', 'error', 'Gagal membuat token baru.');
		}

		$this->redirect_with('admin/licenses', 'success', 'Token unduhan baru dibuat. Tautan lama tidak berlaku lagi.');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

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
