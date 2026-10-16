<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile
 *
 * Lets the signed-in administrator change their own account details
 * (display name, username, e-mail) and rotate their password. Anyone with
 * panel access can edit their own record here; managing *other* accounts
 * stays in the Users controller.
 */
class Profile extends Admin_Controller
{
	public function index()
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$action = (string) $this->input->post('action', TRUE);

			if ($action === 'password')
			{
				$this->_save_password();
			}

			if ($action === 'account')
			{
				$this->_save_account();
			}

			$this->redirect_with('admin/profile', 'error', 'Aksi tidak dikenali.');
		}

		$this->render('admin/profile', array(
			'account' => $this->user_model->get($this->current_user['id']),
			'stats'   => array(
				'orders'   => $this->order_model->count_by_status('paid'),
				'pending'  => $this->order_model->count_by_status('pending'),
				'licenses' => (int) $this->license_model->stats()['total'],
			),
		), 'Akun Saya');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Update the display name, login username and e-mail address.
	 *
	 * @return void
	 */
	protected function _save_account()
	{
		$user = $this->current_user;

		$this->form_validation->set_rules('full_name', 'Nama Lengkap', 'trim|required|min_length[3]|max_length[100]');
		$this->form_validation->set_rules('username', 'Username', 'trim|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9._-]+$/]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[150]');
		$this->form_validation->set_rules('phone', 'Nomor Telepon', 'trim|max_length[25]|regex_match[/^[0-9+\-()\s]*$/]');

		if ($this->form_validation->run() === FALSE)
		{
			$this->redirect_with('admin/profile', 'error', validation_errors(' ', ' '));
		}

		$full_name = (string) $this->input->post('full_name', TRUE);
		$username  = strtolower(trim((string) $this->input->post('username', TRUE)));
		$email     = strtolower(trim((string) $this->input->post('email', TRUE)));
		$phone     = trim((string) $this->input->post('phone', TRUE));

		// The username is an optional alias, but it may never collide.
		if ($username !== '' && $this->user_model->username_exists($username, $user['id']))
		{
			$this->redirect_with('admin/profile', 'error', 'Username "'.$username.'" sudah dipakai akun lain.');
		}

		if ($this->user_model->email_exists($email, $user['id']))
		{
			$this->redirect_with('admin/profile', 'error', 'Email tersebut sudah dipakai akun lain.');
		}

		$avatar = save_user_avatar($user['avatar']);

		if ($avatar === FALSE)
		{
			$this->redirect_with('admin/profile', 'error', 'Gagal mengunggah foto profil: '.strip_tags($this->upload->display_errors('', '')));
		}

		$this->user_model->update($user['id'], array(
			'full_name' => $full_name,
			'username'  => $username,
			'email'     => $email,
			'phone'     => $phone,
			'avatar'    => $avatar,
		));

		// Keep the header/session in sync straight away.
		$this->session->set_userdata('user_name', $full_name);

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

		if ((string) $user['phone'] !== $phone)
		{
			$changed[] = 'nomor telepon';
		}

		if ((string) $user['avatar'] !== (string) $avatar)
		{
			$changed[] = 'foto profil';
		}

		$note = empty($changed) ? 'Tidak ada perubahan yang disimpan.' : 'Berhasil memperbarui '.implode(', ', $changed).'.';

		$this->redirect_with('admin/profile', empty($changed) ? 'info' : 'success', $note);
	}

	/**
	 * Rotate the password. The current password must be supplied, and the
	 * session id is regenerated afterwards so old sessions cannot be reused.
	 *
	 * @return void
	 */
	protected function _save_password()
	{
		$user = $this->current_user;

		$this->form_validation->set_rules('current_password', 'Password Saat Ini', 'required');
		$this->form_validation->set_rules('new_password', 'Password Baru', 'required|min_length[8]|max_length[72]');
		$this->form_validation->set_rules('new_password_confirm', 'Konfirmasi Password Baru', 'required|matches[new_password]');

		if ($this->form_validation->run() === FALSE)
		{
			$this->redirect_with('admin/profile', 'error', validation_errors(' ', ' '));
		}

		$current = (string) $this->input->post('current_password', FALSE);
		$new     = (string) $this->input->post('new_password', FALSE);

		if ( ! $this->user_model->verify_password($current, $user['password_hash']))
		{
			$this->redirect_with('admin/profile', 'error', 'Password saat ini tidak sesuai.');
		}

		if ($this->user_model->verify_password($new, $user['password_hash']))
		{
			$this->redirect_with('admin/profile', 'error', 'Password baru harus berbeda dari password saat ini.');
		}

		$this->user_model->set_password($user['id'], $new);

		$this->session->sess_regenerate(TRUE);

		$this->redirect_with('admin/profile', 'success', 'Password berhasil diperbarui. Gunakan password baru pada login berikutnya.');
	}
}
