<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin order management: verify manual payments (which issues licenses),
 * inspect proofs, and change order status.
 */
class Orders extends Admin_Controller
{
	public function index()
	{
		$filters = array(
			'q'      => trim((string) $this->input->get('q')),
			'status' => (string) $this->input->get('status'),
			'method' => (string) $this->input->get('method'),
		);

		$per_page = (int) $this->config->item('admin_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->order_model->count_admin_listing($filters);

		$this->render('admin/orders/index', array(
			'orders'          => $this->order_model->get_admin_listing($filters, $per_page, ($page - 1) * $per_page),
			'filters'         => $filters,
			'payment_methods' => $this->config->item('payment_methods'),
			'stats'           => $this->order_model->stats(),
			'total'           => $total,
			'pagination'      => $this->paginate($total, $per_page, $page),
		), 'Pesanan');
	}

	public function detail($order_id)
	{
		$order = $this->order_model->get((int) $order_id);

		if ( ! $order)
		{
			$this->redirect_with('admin/orders', 'error', 'Pesanan tidak ditemukan.');
		}

		$this->render('admin/orders/detail', array(
			'order'          => $order,
			'items'          => $this->order_model->get_items($order['id']),
			'licenses'       => $this->license_model->get_by_order($order['id']),
			'has_proof'      => ! empty($order['payment_proof']) && is_file(FCPATH.$order['payment_proof']),
			'proof_is_image' => ! empty($order['payment_proof']) && (bool) preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $order['payment_proof']),
		), 'Pesanan '.$order['order_number']);
	}

	/**
	 * Mark as paid and issue the licenses.
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function mark_paid($order_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders');
		}

		$order = $this->order_model->get((int) $order_id);

		if ( ! $order)
		{
			$this->redirect_with('admin/orders', 'error', 'Pesanan tidak ditemukan.');
		}

		if ( ! $this->order_model->mark_paid($order['id']))
		{
			$this->redirect_with('admin/orders/detail/'.$order['id'], 'info', 'Pesanan ini sudah berstatus lunas.');
		}

		$issued = $this->license_model->issue_for_order($order['id']);

		$this->redirect_with(
			'admin/orders/detail/'.$order['id'],
			'success',
			'Pesanan ditandai lunas. '.$issued.' lisensi diterbitkan untuk pelanggan.'
		);
	}

	/**
	 * Change the payment status.
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function set_status($order_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders');
		}

		$order  = $this->order_model->get((int) $order_id);
		$status = (string) $this->input->post('status', TRUE);

		if ( ! $order)
		{
			$this->redirect_with('admin/orders', 'error', 'Pesanan tidak ditemukan.');
		}

		if ( ! $this->order_model->set_status($order['id'], $status))
		{
			$this->redirect_with('admin/orders/detail/'.$order['id'], 'error', 'Status pembayaran tidak valid.');
		}

		$this->redirect_with('admin/orders/detail/'.$order['id'], 'success', 'Status pesanan diperbarui menjadi '.$status.'.');
	}

	/**
	 * Server-side re-issue of licenses for an already paid order (repair).
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function issue_licenses($order_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders');
		}

		$order = $this->order_model->get((int) $order_id);

		if ( ! $order)
		{
			$this->redirect_with('admin/orders', 'error', 'Pesanan tidak ditemukan.');
		}

		if ($order['payment_status'] !== 'paid')
		{
			$this->redirect_with('admin/orders/detail/'.$order['id'], 'error', 'Tandai pesanan sebagai lunas terlebih dahulu.');
		}

		$issued = $this->license_model->issue_for_order($order['id']);

		$this->redirect_with(
			'admin/orders/detail/'.$order['id'],
			$issued > 0 ? 'success' : 'info',
			$issued > 0 ? $issued.' lisensi baru diterbitkan.' : 'Semua item pada pesanan ini sudah memiliki lisensi.'
		);
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
