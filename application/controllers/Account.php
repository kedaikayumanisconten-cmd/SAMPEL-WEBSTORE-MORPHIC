<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Account
 *
 * Customer area: profile, order history, and the digital library that hosts
 * license keys and protected downloads.
 */
class Account extends Customer_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('pagination');
	}

	// ---------------------------------------------------------------------
	// Dashboard
	// ---------------------------------------------------------------------

	public function index()
	{
		$licenses = $this->license_model->get_library($this->current_user['id'], 4, 0);
		$orders   = $this->order_model->get_for_user_list($this->current_user['id'], 4, 0);

		$downloads = 0;

		foreach ($licenses as $license)
		{
			$downloads += (int) $license['download_count'];
		}

		$this->render('account/index', array(
			'licenses'      => $licenses,
			'recent_orders' => $orders,
			'summary'       => array(
				'orders'    => $this->order_model->count_for_user($this->current_user['id']),
				'licenses'  => $this->license_model->count_library($this->current_user['id']),
				'pending'   => $this->count_pending_orders(),
				'downloads' => $downloads,
			),
		), 'Akun Saya');
	}

	// ---------------------------------------------------------------------
	// Profile
	// ---------------------------------------------------------------------

	public function profile()
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->save_profile();
		}

		$this->render('account/profile', array(), 'Profil Saya');
	}

	/**
	 * Persist profile / password changes.
	 *
	 * @return void
	 */
	protected function save_profile()
	{
		$action = (string) $this->input->post('action', TRUE);
		$user   = $this->current_user;

		if ($action === 'password')
		{
			$this->form_validation->set_rules('current_password', 'Password Saat Ini', 'required');
			$this->form_validation->set_rules('new_password', 'Password Baru', 'required|min_length[8]|max_length[72]');
			$this->form_validation->set_rules('new_password_confirm', 'Konfirmasi Password Baru', 'required|matches[new_password]');

			if ($this->form_validation->run() === FALSE)
			{
				$this->redirect_with('account/profile', 'error', validation_errors(' ', ' '));
			}

			if ( ! $this->user_model->verify_password((string) $this->input->post('current_password', FALSE), $user['password_hash']))
			{
				$this->redirect_with('account/profile', 'error', 'Password saat ini tidak sesuai.');
			}

			$this->user_model->set_password($user['id'], (string) $this->input->post('new_password', FALSE));

			$this->redirect_with('account/profile', 'success', 'Password berhasil diperbarui.');
		}

		// Default action: update the profile fields.
		$this->form_validation->set_rules('full_name', 'Nama Lengkap', 'trim|required|min_length[3]|max_length[100]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[150]');
		$this->form_validation->set_rules('phone', 'Nomor Telepon', 'trim|max_length[25]|regex_match[/^[0-9+\-()\s]*$/]');

		if ($this->form_validation->run() === FALSE)
		{
			$this->redirect_with('account/profile', 'error', validation_errors(' ', ' '));
		}

		$email = strtolower(trim((string) $this->input->post('email', TRUE)));

		if ($this->user_model->email_exists($email, $user['id']))
		{
			$this->redirect_with('account/profile', 'error', 'Email tersebut sudah dipakai akun lain.');
		}

		$avatar = save_user_avatar($user['avatar']);

		if ($avatar === FALSE)
		{
			$this->redirect_with('account/profile', 'error', 'Gagal mengunggah foto profil: '.strip_tags($this->upload->display_errors('', '')));
		}

		$this->user_model->update($user['id'], array(
			'full_name' => (string) $this->input->post('full_name', TRUE),
			'email'     => $email,
			'phone'     => (string) $this->input->post('phone', TRUE),
			'avatar'    => $avatar,
		));

		$this->session->set_userdata('user_name', (string) $this->input->post('full_name', TRUE));

		$this->redirect_with('account/profile', 'success', 'Profil berhasil diperbarui.');
	}

	// ---------------------------------------------------------------------
	// Orders
	// ---------------------------------------------------------------------

	public function orders()
	{
		$per_page = (int) $this->config->item('account_orders_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->order_model->count_for_user($this->current_user['id']);

		$orders = $this->order_model->get_for_user_list(
			$this->current_user['id'],
			$per_page,
			($page - 1) * $per_page
		);

		$this->render('account/orders', array(
			'orders'     => $orders,
			'pagination' => $this->paginate($total, $per_page, $page),
		), 'Riwayat Pesanan');
	}

	/**
	 * Single order detail (items + issued licenses).
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function order($order_id)
	{
		$order = $this->order_model->get_for_user((int) $order_id, $this->current_user['id']);

		if ( ! $order)
		{
			show_404();
		}

		$this->render('account/order_detail', array(
			'order'     => $order,
			'items'     => $this->order_model->get_items($order['id']),
			'licenses'  => $this->license_model->get_by_order($order['id']),
		), 'Pesanan '.$order['order_number']);
	}

	// ---------------------------------------------------------------------
	// Digital library
	// ---------------------------------------------------------------------

	public function library()
	{
		$per_page = (int) $this->config->item('account_orders_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->license_model->count_library($this->current_user['id']);

		$licenses = $this->license_model->get_library(
			$this->current_user['id'],
			$per_page,
			($page - 1) * $per_page
		);

		$this->render('account/library', array(
			'licenses'   => $licenses,
			'pagination' => $this->paginate($total, $per_page, $page),
			'total'      => $total,
		), 'Digital Library');
	}

	/**
	 * License detail: serial number, quota, and the download button.
	 *
	 * @param  int $license_id
	 * @return void
	 */
	public function license($license_id)
	{
		$license = $this->license_model->get_for_user((int) $license_id, $this->current_user['id']);

		if ( ! $license)
		{
			show_404();
		}

		$this->render('account/license', array(
			'license'   => $license,
			'remaining' => $this->license_model->remaining_downloads($license),
			'order'     => $this->order_model->get($license['order_id']),
		), 'Lisensi '.$license['title']);
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Number of pending orders for the current customer.
	 *
	 * @return int
	 */
	protected function count_pending_orders()
	{
		return (int) $this->db
			->where('user_id', (int) $this->current_user['id'])
			->where('payment_status', 'pending')
			->count_all_results('orders');
	}

	/**
	 * Shared pagination renderer for the customer area.
	 *
	 * @param  int $total
	 * @param  int $per_page
	 * @param  int $page
	 * @return string
	 */
	protected function paginate($total, $per_page, $page)
	{
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
