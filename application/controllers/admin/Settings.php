<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Settings
 *
 * Admin screen for the editable storefront content: store identity, logo,
 * contact details, hero/about copy and the footer text. Values are persisted
 * in the site_settings table and merged over the config defaults at runtime.
 */
class Settings extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('upload');
	}

	// ---------------------------------------------------------------------
	// Screen
	// ---------------------------------------------------------------------

	public function index()
	{
		$values = array();

		foreach ($this->text_fields() as $field)
		{
			$values[$field['key']] = (string) $this->config->item($field['key']);
		}

		$this->render('admin/settings', array(
			'groups'      => $this->field_groups(),
			'values'      => $values,
			'logo_value'  => (string) $this->config->item('store_logo'),
			'logo_url'    => brand_logo_url($this->config->item('store_logo')),
			'upload_path' => $this->config->item('upload_logo_path'),
		), 'Pengaturan Situs');
	}

	// ---------------------------------------------------------------------
	// Save
	// ---------------------------------------------------------------------

	public function save()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/settings');
		}

		foreach ($this->text_fields() as $field)
		{
			$rules = 'trim';

			if ( ! empty($field['required']))
			{
				$rules .= '|required';
			}

			if ( ! empty($field['email']))
			{
				$rules .= '|valid_email';
			}

			if ( ! empty($field['digits']))
			{
				$rules .= '|numeric';
			}

			if ( ! empty($field['max']))
			{
				$rules .= '|max_length['.(int) $field['max'].']';
			}

			$this->form_validation->set_rules($field['key'], $field['label'], $rules);
		}

		if ($this->form_validation->run() === FALSE)
		{
			$this->redirect_with('admin/settings', 'error', validation_errors(' ', ' '));
		}

		$data = array();

		foreach ($this->text_fields() as $field)
		{
			$data[$field['key']] = trim((string) $this->input->post($field['key'], FALSE));
		}

		$logo = $this->resolve_logo($this->config->item('store_logo'));

		if ($logo === FALSE)
		{
			$this->redirect_with('admin/settings', 'error', strip_tags($this->upload->display_errors('', '')));
		}

		$data['store_logo'] = $logo;

		if ( ! $this->setting_model->set_many($data))
		{
			$this->redirect_with('admin/settings', 'error', 'Gagal menyimpan. Pastikan tabel site_settings tersedia pada database.');
		}

		$this->redirect_with('admin/settings', 'success', 'Pengaturan situs berhasil disimpan.');
	}

	// ---------------------------------------------------------------------
	// Field definitions (single source of truth for the form + validation)
	// ---------------------------------------------------------------------

	/**
	 * Grouped text/textarea fields rendered on the settings screen.
	 *
	 * @return array
	 */
	protected function field_groups()
	{
		return array(
			array(
				'title'  => 'Identitas Toko',
				'fields' => array(
					array('key' => 'store_name', 'label' => 'Nama Toko', 'type' => 'text', 'required' => TRUE, 'max' => 100, 'hint' => 'Tampil di header, footer, watermark WhatsApp, dan judul halaman.'),
					array('key' => 'brand_subtitle', 'label' => 'Sub-judul Merek', 'type' => 'text', 'max' => 60, 'hint' => 'Teks kecil di bawah nama toko pada header, mis. "Digital Asset Store".'),
					array('key' => 'store_tagline', 'label' => 'Tagline / Deskripsi Singkat', 'type' => 'text', 'max' => 255, 'hint' => 'Dipakai sebagai meta description serta kalimat pengantar di footer.'),
				),
			),
			array(
				'title'  => 'Kontak Owner',
				'fields' => array(
					array('key' => 'store_owner', 'label' => 'Nama Owner', 'type' => 'text', 'max' => 100, 'hint' => 'Ditampilkan pada blok kontak dan tombol WhatsApp.'),
					array('key' => 'store_whatsapp', 'label' => 'Nomor WhatsApp', 'type' => 'text', 'max' => 20, 'digits' => TRUE, 'hint' => 'Format internasional tanpa tanda "+" atau spasi, mis. 6285288855892.'),
					array('key' => 'store_email', 'label' => 'Email Toko', 'type' => 'email', 'required' => TRUE, 'email' => TRUE, 'max' => 150, 'hint' => 'Alamat email publik yang tampil di footer.'),
				),
			),
			array(
				'title'  => 'Header / Hero Beranda',
				'fields' => array(
					array('key' => 'hero_eyebrow', 'label' => 'Label Kecil (eyebrow)', 'type' => 'text', 'max' => 80, 'hint' => 'Tulisan kecil di atas judul hero, mis. "M.O.R.P.H.I.C Marketplace".'),
					array('key' => 'hero_title', 'label' => 'Judul Hero', 'type' => 'text', 'max' => 180, 'hint' => 'Judul utama pada halaman beranda.'),
					array('key' => 'hero_description', 'label' => 'Deskripsi Hero', 'type' => 'textarea', 'rows' => 3, 'hint' => 'Paragraf pengantar di bawah judul hero.'),
				),
			),
			array(
				'title'  => 'Tentang (About)',
				'fields' => array(
					array('key' => 'about_title', 'label' => 'Judul Tentang', 'type' => 'text', 'max' => 120, 'hint' => 'Kosongkan deskripsi untuk menyembunyikan bagian ini dari beranda.'),
					array('key' => 'about_description', 'label' => 'Deskripsi Tentang', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Ceritakan tentang toko Anda. Mendukung beberapa baris.'),
				),
			),
			array(
				'title'  => 'Footer',
				'fields' => array(
					array('key' => 'footer_note', 'label' => 'Catatan Footer', 'type' => 'textarea', 'rows' => 2, 'hint' => 'Kalimat kecil di bawah tagline pada bagian kiri footer.'),
					array('key' => 'footer_copyright', 'label' => 'Teks Hak Cipta', 'type' => 'text', 'max' => 150, 'hint' => 'Nama pemegang hak cipta; tahun ditambahkan otomatis.'),
				),
			),
		);
	}

	/**
	 * Flatten every text/textarea field across the groups.
	 *
	 * @return array
	 */
	protected function text_fields()
	{
		$fields = array();

		foreach ($this->field_groups() as $group)
		{
			foreach ($group['fields'] as $field)
			{
				$fields[] = $field;
			}
		}

		return $fields;
	}

	// ---------------------------------------------------------------------
	// Logo handling
	// ---------------------------------------------------------------------

	/**
	 * Work out the final logo reference from the posted form.
	 *
	 * Priority: explicit removal > new upload > URL field > keep current.
	 *
	 * @param  string|null $old
	 * @return string|false Stored reference, or FALSE when an upload fails.
	 */
	protected function resolve_logo($old)
	{
		$old = (string) $old;

		if ($this->input->post('remove_logo'))
		{
			$this->remove_local_logo($old);

			return '';
		}

		if ( ! empty($_FILES['logo_file']['name']))
		{
			$upload_path = $this->config->item('upload_logo_path');

			if ( ! is_dir(FCPATH.$upload_path))
			{
				@mkdir(FCPATH.$upload_path, 0755, TRUE);
			}

			$this->upload->initialize(array(
				'upload_path'   => FCPATH.$upload_path,
				'allowed_types' => 'jpg|jpeg|png|webp|gif|avif',
				'max_size'      => 2048,
				'file_name'     => 'logo_'.date('Ymd_His'),
				'overwrite'     => FALSE,
			));

			if ( ! $this->upload->do_upload('logo_file'))
			{
				return FALSE;
			}

			$file = $this->upload->data();

			$this->remove_local_logo($old);

			return $upload_path.$file['file_name'];
		}

		$url = trim((string) $this->input->post('logo_url', TRUE));

		if ($url !== '' && $url !== $old)
		{
			if ( ! is_remote_url($url) && ! asset_exists($url))
			{
				$this->session->set_flashdata('error', 'URL logo harus diawali http:// atau https:// dan berupa gambar publik.');

				return FALSE;
			}

			$this->remove_local_logo($old);

			return $url;
		}

		return $old;
	}

	/**
	 * Delete a previously uploaded local logo (never remote URLs).
	 *
	 * @param  string|null $value
	 * @return void
	 */
	protected function remove_local_logo($value)
	{
		if (empty($value) || is_remote_url($value))
		{
			return;
		}

		$prefix = $this->config->item('upload_logo_path');

		if (strpos($value, $prefix) === 0 && is_file(FCPATH.$value))
		{
			@unlink(FCPATH.$value);
		}
	}
}
