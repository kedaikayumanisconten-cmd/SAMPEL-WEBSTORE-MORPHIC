<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Download
 *
 * Streams a purchased product from the protected storage folder. The only
 * public entry point is a per-license download token, and every request is
 * counted against the license quota.
 */
class Download extends Customer_Controller
{
	/**
	 * A file transfer is not a page view.
	 *
	 * @var bool
	 */
	protected $track_visits = FALSE;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Serve the file behind a license token.
	 *
	 * @param  string $token
	 * @return void
	 */
	public function index($token = '')
	{
		$limit = $this->rate_limit_model->check('download');

		if ( ! $limit['allowed'])
		{
			$this->redirect_with('library', 'error', 'Terlalu banyak permintaan unduhan. Coba lagi sebentar lagi.');
		}

		$license = $this->license_model->get_by_token($token);

		if ( ! $license)
		{
			$this->redirect_with('library', 'error', 'Tautan unduhan tidak ditemukan atau sudah tidak berlaku.');
		}

		// Only the owner (or a staff member) may use the token.
		if ((int) $license['user_id'] !== (int) $this->current_user['id'] && ! is_staff_role($this->current_user['role']))
		{
			$this->redirect_with('library', 'error', 'Tautan unduhan ini bukan milik akun Anda.');
		}

		if ((int) $license['is_revoked'] === 1)
		{
			$this->redirect_with('library', 'error', 'Lisensi produk ini telah dicabut. Hubungi admin untuk bantuan.');
		}

		if ($this->license_model->remaining_downloads($license) <= 0)
		{
			$this->redirect_with('library', 'error', 'Kuota unduhan produk ini sudah habis. Hubungi admin untuk penambahan kuota.');
		}

		$path = $this->resolve_path($license['file_path']);

		if ($path === NULL)
		{
			$this->redirect_with('account/license/'.$license['id'], 'error', 'File produk belum tersedia di server. Silakan hubungi admin.');
		}

		$this->license_model->register_download($license['id']);

		$this->stream($path, $license['slug'] ?? '');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Stream a file to the browser without loading it fully into memory.
	 *
	 * @param  string $path Absolute path to an existing file.
	 * @param  string $slug Used to build a friendly download file name.
	 * @return void
	 */
	protected function stream($path, $slug = '')
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$name      = ($slug !== '' ? $slug : pathinfo($path, PATHINFO_FILENAME));
		$name      = ($extension !== '' ? $name.'.'.$extension : $name);

		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($name).'"');
		header('Content-Length: '.filesize($path));
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');

		readfile($path);
		exit;
	}

	/**
	 * Resolve the stored product path inside the protected storage folder.
	 *
	 * Guards against path traversal: the resolved real path must stay inside
	 * FCPATH/storage/private_products/.
	 *
	 * @param  string $relative
	 * @return string|null
	 */
	protected function resolve_path($relative)
	{
		$relative = ltrim((string) $relative, '/\\');

		if ($relative === '')
		{
			return NULL;
		}

		$base = realpath(FCPATH.'storage');

		if ($base === FALSE)
		{
			return NULL;
		}

		$target = realpath(FCPATH.$relative);

		if ($target === FALSE || ! is_file($target))
		{
			return NULL;
		}

		// The file has to live inside the protected storage directory.
		if (stripos($target, $base.DIRECTORY_SEPARATOR) !== 0)
		{
			return NULL;
		}

		return $target;
	}
}
