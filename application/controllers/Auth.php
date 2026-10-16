<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth
 *
 * Login, registration and logout with brute-force protection through the
 * rate_limits table.
 */
class Auth extends Frontend_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Login
	// ---------------------------------------------------------------------

	public function login()
	{
		if ($this->current_user)
		{
			redirect(is_staff_role($this->current_user['role']) ? 'admin' : 'account');
		}

		if ($this->input->method(TRUE) !== 'POST')
		{
			$this->render('auth/login', array(
				'page_heading' => 'Masuk ke akun Anda',
			), 'Masuk');

			return;
		}

		$limit = $this->rate_limit_model->check('auth_login');

		if ( ! $limit['allowed'])
		{
			$this->redirect_with('login', 'error', 'Terlalu banyak percobaan masuk. Coba lagi dalam '.ceil($limit['retry_after'] / 60).' menit.');
		}

		$this->form_validation->set_rules('email', 'Email atau Username', 'trim|required|max_length[150]');
		$this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', '<br>'));
			$this->render('auth/login', array('page_heading' => 'Masuk ke akun Anda'), 'Masuk');

			return;
		}

		$login    = (string) $this->input->post('email', TRUE);
		$password = (string) $this->input->post('password', FALSE);
		$result   = $this->user_model->authenticate($login, $password);

		if ($result === 'invalid')
		{
			$state = $this->rate_limit_model->hit('auth_login');
			$extra = $state['allowed'] && $state['attempts'] >= 3
				? ' ('.(max(0, (int) $this->config->item('rate_limit_max_attempts')['auth_login']) - $state['attempts']).' percobaan tersisa)'
				: '';

			$this->redirect_with('login', 'error', 'Email, username, atau password salah.'.$extra);
		}

		if ($result === 'inactive')
		{
			$this->redirect_with('login', 'error', 'Akun Anda sedang dinonaktifkan. Hubungi administrator.');
		}

		$this->rate_limit_model->clear('auth_login');
		$this->_start_session($result);

		$target = (string) $this->session->userdata('redirect_after_login');
		$this->session->unset_userdata('redirect_after_login');

		if ($target === '')
		{
			$target = is_staff_role($result['role']) ? 'admin' : 'account';
		}

		redirect($target);
	}

	// ---------------------------------------------------------------------
	// Register
	// ---------------------------------------------------------------------

	public function register()
	{
		if ($this->current_user)
		{
			redirect('account');
		}

		if ($this->input->method(TRUE) !== 'POST')
		{
			$this->render('auth/register', array(
				'page_heading' => 'Buat akun M.O.R.P.H.I.C',
			), 'Daftar');

			return;
		}

		$limit = $this->rate_limit_model->check('auth_register');

		if ( ! $limit['allowed'])
		{
			$this->redirect_with('register', 'error', 'Terlalu banyak percobaan pendaftaran. Coba lagi beberapa saat lagi.');
		}

		$this->form_validation->set_rules('full_name', 'Nama Lengkap', 'trim|required|min_length[3]|max_length[100]');
		$this->form_validation->set_rules('username', 'Username', 'trim|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9._-]+$/]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[150]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[72]');
		$this->form_validation->set_rules('password_confirm', 'Konfirmasi Password', 'required|matches[password]');
		$this->form_validation->set_rules('terms', 'Syarat & Ketentuan', 'required');

		$email    = strtolower(trim((string) $this->input->post('email', TRUE)));
		$username = strtolower(trim((string) $this->input->post('username', TRUE)));

		if ($this->form_validation->run() === FALSE)
		{
			$this->session->set_flashdata('error', validation_errors(' ', '<br>'));
			$this->render('auth/register', array('page_heading' => 'Buat akun M.O.R.P.H.I.C'), 'Daftar');

			return;
		}

		if ($this->user_model->email_exists($email))
		{
			$this->rate_limit_model->hit('auth_register');
			$this->redirect_with('register', 'error', 'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.');
		}

		if ($username !== '' && $this->user_model->username_exists($username))
		{
			$this->rate_limit_model->hit('auth_register');
			$this->redirect_with('register', 'error', 'Username tersebut sudah dipakai. Silakan pilih yang lain.');
		}

		$user_id = $this->user_model->create(array(
			'full_name' => (string) $this->input->post('full_name', TRUE),
			'username'  => $username,
			'email'     => $email,
			'password'  => (string) $this->input->post('password', FALSE),
			'role'      => 'customer',
		));

		$this->rate_limit_model->clear('auth_register');

		$user = $this->user_model->get($user_id);
		$this->_start_session($user);

		$this->redirect_with('account', 'success', 'Akun berhasil dibuat. Selamat datang, '.$user['full_name'].'!');
	}

	// ---------------------------------------------------------------------
	// Logout
	// ---------------------------------------------------------------------

	public function logout()
	{
		$this->session->sess_destroy();
		redirect('/');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Persist the authenticated identity in the session and refresh the
	 * session id to prevent fixation.
	 *
	 * @param  array $user
	 * @return void
	 */
	protected function _start_session(array $user)
	{
		$this->user_model->touch_last_login($user['id']);

		$this->session->sess_regenerate(TRUE);
		$this->session->set_userdata(array(
			'user_id'   => (int) $user['id'],
			'user_role' => $user['role'],
			'user_name' => $user['full_name'],
		));
	}
}
