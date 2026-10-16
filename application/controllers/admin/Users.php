<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin user management: search, activate/deactivate and change roles.
 */
class Users extends Admin_Controller
{
	public function index()
	{
		$filters = array(
			'q'      => trim((string) $this->input->get('q')),
			'role'   => (string) $this->input->get('role'),
			'status' => (string) $this->input->get('status'),
		);

		$per_page = (int) $this->config->item('admin_per_page');
		$page     = max(1, (int) $this->input->get('page'));
		$total    = $this->user_model->count_all($filters);

		$users = $this->user_model->paginate($filters, $per_page, ($page - 1) * $per_page);

		foreach ($users as $index => $user)
		{
			$users[$index]['order_count']   = $this->order_model->count_for_user($user['id']);
			$users[$index]['license_count'] = $this->license_model->count_library($user['id']);
		}

		$this->render('admin/users/index', array(
			'users'      => $users,
			'filters'    => $filters,
			'roles'      => all_roles(),
			'assignable' => assignable_roles($this->current_user['role']),
			'stats'      => $this->user_model->stats(),
			'total'      => $total,
			'pagination' => $this->paginate($total, $per_page, $page),
		), 'Pengguna');
	}

	/**
	 * Toggle an account's active flag.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function toggle($user_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/users');
		}

		$user = $this->load_manageable_user($user_id);

		if ((int) $user['id'] === (int) $this->current_user['id'])
		{
			$this->redirect_with('admin/users', 'error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
		}

		$active = (int) $user['is_active'] !== 1;

		$this->user_model->set_active($user['id'], $active);

		$this->redirect_with('admin/users', 'success', 'Akun '.$user['full_name'].' '.($active ? 'diaktifkan' : 'dinonaktifkan').'.');
	}

	/**
	 * Change a user's role.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function set_role($user_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/users');
		}

		$user = $this->load_manageable_user($user_id);
		$role = (string) $this->input->post('role', TRUE);

		if ( ! in_array($role, all_roles(), TRUE))
		{
			$this->redirect_with('admin/users', 'error', 'Role tidak valid.');
		}

		if ((int) $user['id'] === (int) $this->current_user['id'])
		{
			$this->redirect_with('admin/users', 'error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
		}

		// Never hand out a role above the acting account's own level.
		if ( ! can_manage_role($this->current_user['role'], $role))
		{
			$this->redirect_with('admin/users', 'error', 'Anda tidak berwenang menetapkan role '.role_label($role).'.');
		}

		$this->user_model->update($user['id'], array('role' => $role));

		$this->redirect_with('admin/users', 'success', 'Role '.$user['full_name'].' diubah menjadi '.role_label($role).'.');
	}

	// ---------------------------------------------------------------------
	// Edit account (username, email, password)
	// ---------------------------------------------------------------------

	/**
	 * Show the account editor for a single user. This is where an
	 * administrator changes somebody else's login username and resets a
	 * forgotten password (the Users list itself only toggles status/role).
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function edit($user_id)
	{
		$user = $this->load_manageable_user($user_id);

		$this->render_form($user);
	}

	/**
	 * Persist the account editor. The password field is optional: leaving it
	 * blank keeps the current password, filling it replaces the hash.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function update($user_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/users');
		}

		$user = $this->load_manageable_user($user_id);

		$this->form_validation->set_rules('full_name', 'Nama Lengkap', 'trim|required|min_length[3]|max_length[100]');
		$this->form_validation->set_rules('username', 'Username', 'trim|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9._-]+$/]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[150]');
		// Empty, non-required values are skipped by the validator, so a blank
		// password simply means "keep the current one".
		$this->form_validation->set_rules('password', 'Password Baru', 'trim|min_length[8]|max_length[72]');
		$this->form_validation->set_rules('password_confirm', 'Konfirmasi Password Baru', 'trim|matches[password]');

		// Errors are rendered in place (not through flashdata) so the message
		// and the submitted values survive on the very same response.
		if ($this->form_validation->run() === FALSE)
		{
			$this->render_form($user, validation_errors(' ', ' '));

			return;
		}

		$full_name = trim((string) $this->input->post('full_name', TRUE));
		$username  = strtolower(trim((string) $this->input->post('username', TRUE)));
		$email     = strtolower(trim((string) $this->input->post('email', TRUE)));
		$password  = (string) $this->input->post('password', FALSE);

		if ($username !== '' && $this->user_model->username_exists($username, $user['id']))
		{
			$this->render_form($user, 'Username "'.$username.'" sudah dipakai akun lain.');

			return;
		}

		if ($this->user_model->email_exists($email, $user['id']))
		{
			$this->render_form($user, 'Email tersebut sudah dipakai akun lain.');

			return;
		}

		$this->user_model->update($user['id'], array(
			'full_name' => $full_name,
			'username'  => $username,
			'email'     => $email,
		));

		$is_self = ((int) $user['id'] === (int) $this->current_user['id']);
		$changed = array();

		if ($full_name !== $user['full_name'])
		{
			$changed[] = 'nama';
		}

		if ((string) $user['username'] !== $username)
		{
			$changed[] = $username === '' ? 'username (dihapus)' : 'username';
		}

		if ((string) $user['email'] !== $email)
		{
			$changed[] = 'email';
		}

		if ($password !== '')
		{
			$this->user_model->set_password($user['id'], $password);

			if ($is_self)
			{
				// Keep the acting admin signed in on a fresh session id.
				$this->session->sess_regenerate(TRUE);
			}
			else
			{
				// Any existing session of the affected account must not survive
				// a password reset, so it is forced to sign in again.
				$this->user_model->purge_sessions_for($user['id']);
			}

			$changed[] = 'password';
		}

		if ($is_self)
		{
			$this->session->set_userdata('user_name', $full_name);
		}

		$note = empty($changed)
			? 'Tidak ada perubahan yang disimpan.'
			: 'Berhasil memperbarui '.implode(', ', $changed).' untuk '.$full_name.'.';

		$this->redirect_with('admin/users', empty($changed) ? 'info' : 'success', $note);
	}

	/**
	 * Issue a random, temporary password for a user and reveal it once.
	 *
	 * The plain value is never stored: only its hash is saved, and the
	 * plaintext travels to the next page through a single-use flash entry so
	 * the administrator can copy it and hand it to the account owner.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function generate_password($user_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/users');
		}

		$user = $this->load_manageable_user($user_id);

		$plain = $this->user_model->random_password();

		$this->user_model->set_password($user['id'], $plain);

		if ((int) $user['id'] === (int) $this->current_user['id'])
		{
			$this->session->sess_regenerate(TRUE);
		}
		else
		{
			$this->user_model->purge_sessions_for($user['id']);
		}

		// Read (and shown) exactly once on the page we redirect to.
		$this->session->set_flashdata('temp_password', $plain);

		$this->redirect_with('admin/users/edit/'.$user['id'], 'success', 'Password sementara dibuat untuk '.$user['full_name'].'. Salin sekarang — hanya ditampilkan sekali.');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Load a target account and confirm the acting staff member may manage it.
	 *
	 * A member can only touch accounts at or below their own privilege level
	 * (and always their own account), which keeps an admin from editing a
	 * super_admin or owner. Redirects and ends the request when not allowed.
	 *
	 * @param  int $user_id
	 * @return array
	 */
	protected function load_manageable_user($user_id)
	{
		$user = $this->user_model->get((int) $user_id);

		if ( ! $user)
		{
			$this->redirect_with('admin/users', 'error', 'Pengguna tidak ditemukan.');
		}

		$self = ((int) $user['id'] === (int) $this->current_user['id']);

		if ( ! $self && ! can_manage_role($this->current_user['role'], $user['role']))
		{
			$this->redirect_with('admin/users', 'error', 'Anda tidak berwenang mengelola akun '.$user['full_name'].' ('.role_label($user['role']).').');
		}

		return $user;
	}

	/**
	 * Render the shared single-user account editor.
	 *
	 * @param  array       $user
	 * @param  string|null $error Inline error to show above the form.
	 * @return void
	 */
	protected function render_form(array $user, $error = NULL)
	{
		$this->render('admin/users/form', array(
			'user'       => $user,
			'is_self'    => ((int) $user['id'] === (int) $this->current_user['id']),
			'form_error' => $error,
		), 'Ubah Akun: '.$user['full_name']);
	}

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
