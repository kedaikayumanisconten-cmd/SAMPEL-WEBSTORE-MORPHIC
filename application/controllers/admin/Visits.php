<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Visits
 *
 * Full, filterable traffic log behind the dashboard summary.
 */
class Visits extends Admin_Controller
{
	public function index()
	{
		$filters = array(
			'q'            => trim((string) $this->input->get('q')),
			'device'       => (string) $this->input->get('device'),
			'only_members' => (string) $this->input->get('only_members') === '1' ? 1 : 0,
		);

		$per_page = max(5, (int) $this->config->item('visits_per_page'));
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->visit_model->count_all($filters);

		$this->render('admin/visits/index', array(
			'visits'     => $this->visit_model->paginate($filters, $per_page, ($page - 1) * $per_page),
			'filters'    => $filters,
			'total'      => $total,
			'window'     => max(1, (int) $this->config->item('dashboard_window_days')),
			'stats'      => $this->visit_model->stats((int) $this->config->item('dashboard_window_days')),
			'top_pages'  => $this->visit_model->top_pages((int) $this->config->item('dashboard_window_days'), 6),
			'devices'    => $this->visit_model->devices((int) $this->config->item('dashboard_window_days')),
			'pagination' => $this->paginate($total, $per_page, $page),
		), 'Riwayat Kunjungan');
	}

	/**
	 * Empty the traffic log. Useful while the store is still being set up and
	 * demo traffic is noise.
	 *
	 * @return void
	 */
	public function clear()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/visits');
		}

		$removed = $this->visit_model->count_all(array());

		$this->visit_model->truncate();

		$this->redirect_with('admin/visits', 'success', 'Riwayat kunjungan dibersihkan ('.$removed.' baris dihapus).');
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
