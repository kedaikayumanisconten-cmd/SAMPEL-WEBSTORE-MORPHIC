<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unsplash
 *
 * Thin, dependency free wrapper around the Unsplash Photo Search API. Used by
 * the admin product form so an editor can search a category appropriate photo
 * without leaving the panel.
 *
 * The access key stays server side: the browser only ever talks to
 * admin/products/unsplash_search, which proxies this library.
 *
 * Docs: https://unsplash.com/documentation#search-photos
 */
class Unsplash
{
	/**
	 * CodeIgniter instance.
	 *
	 * @var CI_Controller
	 */
	protected $CI;

	/**
	 * Last error message, if any.
	 *
	 * @var string|null
	 */
	protected $error = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	// ---------------------------------------------------------------------
	// Capability checks
	// ---------------------------------------------------------------------

	/**
	 * Is an access key configured?
	 *
	 * @return bool
	 */
	public function is_configured()
	{
		return trim((string) $this->CI->config->item('unsplash_access_key')) !== '';
	}

	/**
	 * @return string|null
	 */
	public function last_error()
	{
		return $this->error;
	}

	// ---------------------------------------------------------------------
	// API
	// ---------------------------------------------------------------------

	/**
	 * Search photos by keyword.
	 *
	 * @param  string $query
	 * @param  int    $page
	 * @param  int    $per_page
	 * @return array|false Normalised photo array, or FALSE on failure.
	 */
	public function search($query, $page = 1, $per_page = NULL)
	{
		$query = trim((string) $query);

		if ($query === '')
		{
			$this->error = 'Kata kunci pencarian wajib diisi.';

			return FALSE;
		}

		return $this->request($query, $page, $per_page);
	}

	/**
	 * Perform the HTTP call and normalise the response.
	 *
	 * @param  string $query
	 * @param  int    $page
	 * @param  int    $per_page
	 * @return array|false
	 */
	protected function request($query, $page, $per_page)
	{
		if ( ! $this->is_configured())
		{
			$this->error = 'Unsplash Access Key belum diisi pada application/config/unsplash.php.';

			return FALSE;
		}

		$per_page = (int) ($per_page ?: $this->CI->config->item('unsplash_per_page'));

		$url = $this->CI->config->item('unsplash_endpoint').'?'.http_build_query(array(
			'query'    => $query,
			'page'     => max(1, (int) $page),
			'per_page' => max(1, min(30, $per_page)),
			'client_id' => $this->CI->config->item('unsplash_access_key'),
		));

		$body = $this->http_get($url);

		if ($body === FALSE)
		{
			return FALSE;
		}

		$payload = json_decode($body, TRUE);

		if ( ! is_array($payload) || ! isset($payload['results']))
		{
			$this->error = 'Respons Unsplash tidak dapat dibaca.';

			return FALSE;
		}

		$photos = array();

		foreach ($payload['results'] as $photo)
		{
			$normalised = $this->normalise($photo);

			if ($normalised !== NULL)
			{
				$photos[] = $normalised;
			}
		}

		return array(
			'query'      => $query,
			'page'       => isset($payload['page']) ? (int) $payload['page'] : max(1, (int) $page),
			'total'      => isset($payload['total']) ? (int) $payload['total'] : count($photos),
			'total_pages' => isset($payload['total_pages']) ? (int) $payload['total_pages'] : 1,
			'photos'     => $photos,
		);
	}

	/**
	 * Flatten an Unsplash photo object into the fields the admin UI needs.
	 *
	 * @param  array $photo
	 * @return array|null
	 */
	public function normalise(array $photo)
	{
		if (empty($photo['urls']['regular']))
		{
			return NULL;
		}

		$photographer      = isset($photo['user']['name']) ? $photo['user']['name'] : 'Unsplash contributor';
		$photographer_link = isset($photo['user']['links']['html'])
			? $this->with_utm($photo['user']['links']['html'])
			: $this->with_utm('https://unsplash.com');

		$photo_link = isset($photo['links']['html'])
			? $this->with_utm($photo['links']['html'])
			: $this->with_utm('https://unsplash.com');

		return array(
			'id'                => isset($photo['id']) ? $photo['id'] : '',
			'alt'               => isset($photo['alt_description']) ? (string) $photo['alt_description'] : '',
			'thumb'             => isset($photo['urls']['small']) ? $photo['urls']['small'] : $photo['urls']['regular'],
			'regular'           => $photo['urls']['regular'],
			'full'              => isset($photo['urls']['full']) ? $photo['urls']['full'] : $photo['urls']['regular'],
			'photographer'      => $photographer,
			'photographer_link' => $photographer_link,
			'photo_link'        => $photo_link,
			'credit'            => 'Photo by '.$photographer.' on Unsplash',
			'credit_html'       => 'Photo by <a href="'.html_escape($photographer_link).'" target="_blank" rel="noopener noreferrer">'.html_escape($photographer).'</a> on <a href="'.html_escape($photo_link).'" target="_blank" rel="noopener noreferrer">Unsplash</a>',
		);
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Append the required UTM parameters to an Unsplash link.
	 *
	 * @param  string $url
	 * @return string
	 */
	protected function with_utm($url)
	{
		$source = (string) $this->CI->config->item('unsplash_utm_source');

		if ($source === '')
		{
			return $url;
		}

		return $url.(strpos($url, '?') === FALSE ? '?' : '&').http_build_query(array(
			'utm_source' => $source,
			'utm_medium' => 'referral',
		));
	}

	/**
	 * GET a URL, preferring cURL so we can control timeouts properly.
	 *
	 * @param  string $url
	 * @return string|false
	 */
	protected function http_get($url)
	{
		$timeout = (int) $this->CI->config->item('unsplash_timeout');

		if (function_exists('curl_init'))
		{
			$ch = curl_init($url);

			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_TIMEOUT        => $timeout > 0 ? $timeout : 8,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_MAXREDIRS      => 3,
				CURLOPT_USERAGENT      => 'MORPHIC-Store/1.0 (CodeIgniter)',
				CURLOPT_HTTPHEADER     => array('Accept: application/json'),
			));

			$body   = curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$errno  = curl_errno($ch);

			curl_close($ch);

			if ($errno !== 0 || $body === FALSE)
			{
				$this->error = 'Tidak dapat menghubungi Unsplash. Periksa koneksi internet server.';

				return FALSE;
			}

			if ($status === 401 || $status === 403)
			{
				$this->error = 'Access Key Unsplash tidak valid atau kuota habis.';

				return FALSE;
			}

			if ($status === 429)
			{
				$this->error = 'Kuota permintaan Unsplash (rate limit) tercapai. Coba lagi nanti.';

				return FALSE;
			}

			if ($status < 200 || $status >= 300)
			{
				$this->error = 'Unsplash mengembalikan status HTTP '.$status.'.';

				return FALSE;
			}

			return $body;
		}

		// Fallback without cURL.
		$context = stream_context_create(array(
			'http' => array(
				'method'  => 'GET',
				'timeout' => $timeout > 0 ? $timeout : 8,
				'header'  => "Accept: application/json\r\nUser-Agent: MORPHIC-Store/1.0\r\n",
			),
		));

		$body = @file_get_contents($url, FALSE, $context);

		if ($body === FALSE)
		{
			$this->error = 'Tidak dapat menghubungi Unsplash (fallback stream).';

			return FALSE;
		}

		return $body;
	}
}
