<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller
 *
 * Base controller for the whole M.O.R.P.H.I.C application. It bootstraps the
 * models, resolves the authenticated user, exposes shared view data and
 * provides a single rendering entry point for every page.
 *
 * Three role aware subclasses are declared in this same file (CodeIgniter 3
 * requires this file for the extended base controller and makes the extra
 * classes available at once):
 *
 *   Frontend_Controller  - public storefront pages (guests allowed)
 *   Customer_Controller  - pages that require an authenticated customer
 *   Admin_Controller     - pages that require an administrator
 */
class MY_Controller extends CI_Controller
{
	/**
	 * Shared view data merged into every rendered page.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Currently authenticated user row, or NULL for guests.
	 *
	 * @var array|null
	 */
	protected $current_user = NULL;

	/**
	 * Layout used to wrap the rendered content view.
	 *
	 * @var string
	 */
	protected $layout = 'layouts/store';

	/**
	 * Controllers that should not be written to the traffic log (file
	 * streaming, JSON endpoints) set this to FALSE.
	 *
	 * @var bool
	 */
	protected $track_visits = TRUE;

	public function __construct()
	{
		parent::__construct();

		$this->load->model('User_model', 'user_model');
		$this->load->model('Category_model', 'category_model');
		$this->load->model('Product_model', 'product_model');
		$this->load->model('Order_model', 'order_model');
		$this->load->model('License_model', 'license_model');
		$this->load->model('Rate_limit_model', 'rate_limit_model');
		$this->load->model('Visit_model', 'visit_model');
		$this->load->model('Setting_model', 'setting_model');

		$this->_resolve_current_user();
		$this->_apply_site_settings();
		$this->_build_shared_data();
	}

	// ---------------------------------------------------------------------
	// Bootstrap helpers
	// ---------------------------------------------------------------------

	/**
	 * Resolve the session user against the database. A stale session (user
	 * deleted or deactivated) is destroyed instead of trusted.
	 */
	protected function _resolve_current_user()
	{
		$user_id = (int) $this->session->userdata('user_id');

		if ($user_id > 0)
		{
			$user = $this->user_model->get($user_id);

			if ($user && (int) $user['is_active'] === 1)
			{
				$this->current_user = $user;
			}
			else
			{
				$this->session->unset_userdata(array('user_id', 'user_role', 'user_name'));
			}
		}

		$this->data['current_user'] = $this->current_user;
		$this->data['is_logged_in'] = ($this->current_user !== NULL);
		$this->data['is_admin']     = ($this->current_user !== NULL && is_staff_role($this->current_user['role']));
	}

	/**
	 * Overlay the editable storefront content stored in site_settings on top
	 * of the code defaults from application/config/morphic.php. Because the
	 * values are pushed back into the config object, everything that already
	 * reads config items (helpers, controllers, views) picks them up.
	 *
	 * @return void
	 */
	protected function _apply_site_settings()
	{
		foreach ($this->setting_model->all() as $key => $value)
		{
			$this->config->set_item($key, $value === NULL ? '' : $value);
		}
	}

	/**
	 * Data that is needed by the header/footer of (almost) every page.
	 */
	protected function _build_shared_data()
	{
		$this->data['store_name']        = $this->config->item('store_name');
		$this->data['store_tagline']     = $this->config->item('store_tagline');
		$this->data['store_email']       = $this->config->item('store_email');
		$this->data['store_owner']       = $this->config->item('store_owner');
		$this->data['store_contacts']    = $this->config->item('store_contacts');
		$this->data['store_logo']        = $this->config->item('store_logo');
		$this->data['brand_subtitle']    = $this->config->item('brand_subtitle');
		$this->data['hero_eyebrow']      = $this->config->item('hero_eyebrow');
		$this->data['hero_title']        = $this->config->item('hero_title');
		$this->data['hero_description']  = $this->config->item('hero_description');
		$this->data['about_title']       = $this->config->item('about_title');
		$this->data['about_description'] = $this->config->item('about_description');
		$this->data['footer_note']       = $this->config->item('footer_note');
		$this->data['footer_copyright']  = $this->config->item('footer_copyright');
		$this->data['nav_categories'] = $this->category_model->get_active();
		$this->data['cart_count']    = $this->cart_count();
		$this->data['owned_ids']     = $this->owned_product_ids();
		$this->data['flash_success'] = $this->session->flashdata('success');
		$this->data['flash_error']   = $this->session->flashdata('error');
		$this->data['flash_info']    = $this->session->flashdata('info');
		$this->data['page_title']    = $this->config->item('store_name');
	}

	/**
	 * Append the current page view to the traffic log.
	 *
	 * Only lightweight GET navigations are captured: POSTs, AJAX calls and
	 * CLI runs are skipped, and the query string is never stored.
	 *
	 * @return void
	 */
	protected function track_visit()
	{
		if ( ! $this->track_visits || is_cli())
		{
			return;
		}

		if ($this->input->method(TRUE) !== 'GET' || $this->input->is_ajax_request())
		{
			return;
		}

		$agent = (string) $this->input->server('HTTP_USER_AGENT');

		$this->visit_model->record(array(
			'user_id'    => $this->current_user ? (int) $this->current_user['id'] : NULL,
			'session_id' => (string) $this->session->userdata('session_id'),
			'uri'        => trim((string) $this->uri->uri_string(), '/'),
			'referer'    => (string) $this->input->server('HTTP_REFERER'),
			'ip_address' => $this->input->ip_address(),
			'user_agent' => $agent,
			'device'     => detect_device($agent),
			'is_admin'   => ($this->current_user !== NULL && is_staff_role($this->current_user['role'])),
		));

		// Housekeeping: prune the log on roughly one request in two hundred.
		if (mt_rand(1, 200) === 1)
		{
			$this->visit_model->purge_old();
		}
	}

	// ---------------------------------------------------------------------
	// Cart helpers (session based, pre-login friendly)
	// ---------------------------------------------------------------------

	/**
	 * Return the session cart array.
	 *
	 * @return array
	 */
	protected function cart()
	{
		$cart = $this->session->userdata('cart');

		return is_array($cart) ? $cart : array();
	}

	/**
	 * Persist the session cart array.
	 *
	 * @param  array $cart
	 * @return void
	 */
	protected function cart_save(array $cart)
	{
		$this->session->set_userdata('cart', $cart);
	}

	/**
	 * Recalculate the cart totals from the stored lines.
	 *
	 * @param  array|null $cart
	 * @return array
	 */
	protected function cart_totals($cart = NULL)
	{
		$cart   = ($cart === NULL) ? $this->cart() : $cart;
		$total  = 0.0;

		foreach ($cart as $line)
		{
			$total += (float) $line['price'];
		}

		return array(
			'count' => count($cart),
			'subtotal' => $total,
			'total' => $total,
		);
	}

	/**
	 * Number of distinct products currently in the cart.
	 *
	 * @return int
	 */
	protected function cart_count()
	{
		return count($this->cart());
	}

	/**
	 * Product ids the signed-in visitor already owns an active license for.
	 *
	 * Digital goods must never be sold twice to the same account, so this is
	 * used both to hide the buy buttons and to reject duplicate purchases.
	 *
	 * @return array
	 */
	protected function owned_product_ids()
	{
		if ($this->current_user === NULL)
		{
			return array();
		}

		return $this->license_model->owned_product_ids($this->current_user['id']);
	}

	// ---------------------------------------------------------------------
	// Rendering
	// ---------------------------------------------------------------------

	/**
	 * Render a content view wrapped in the configured layout.
	 *
	 * @param  string      $view
	 * @param  array       $data
	 * @param  string|null $title
	 * @return void
	 */
	protected function render($view, array $data = array(), $title = NULL)
	{
		$data = array_merge($this->data, $data);

		if ($title !== NULL)
		{
			$data['page_title'] = $title.' | '.$this->config->item('store_name');
		}

		$data['content_view'] = $view;

		$this->load->view($this->layout, $data);
	}

	/**
	 * Render a view without any layout (used for standalone pages).
	 *
	 * @param  string $view
	 * @param  array  $data
	 * @return void
	 */
	protected function render_plain($view, array $data = array())
	{
		$this->load->view($view, array_merge($this->data, $data));
	}

	// ---------------------------------------------------------------------
	// Convenience
	// ---------------------------------------------------------------------

	/**
	 * Redirect with a flash message.
	 *
	 * @param  string $uri
	 * @param  string $type    success|error|info
	 * @param  string $message
	 * @return void
	 */
	protected function redirect_with($uri, $type, $message)
	{
		$this->session->set_flashdata($type, $message);
		redirect($uri);
	}

	/**
	 * Verify the CSRF token for the current POST request.
	 *
	 * CodeIgniter validates POSTed forms automatically, but the cart links are
	 * GET based, so they instead rely on a token in the query string that is
	 * checked with this helper.
	 *
	 * @param  string $token
	 * @return bool
	 */
	protected function valid_csrf_token($token)
	{
		return is_string($token)
			&& $token !== ''
			&& hash_equals((string) $this->security->get_csrf_hash(), $token);
	}
}

/**
 * Frontend_Controller
 *
 * Public storefront. Guests are allowed everywhere.
 */
class Frontend_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->track_visit();
	}
}

/**
 * Customer_Controller
 *
 * Any page that requires an authenticated (active) customer or admin.
 */
class Customer_Controller extends Frontend_Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->is_logged_in())
		{
			$this->session->set_userdata('redirect_after_login', current_url());
			$this->redirect_with('login', 'error', 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.');
		}
	}

	/**
	 * @return bool
	 */
	protected function is_logged_in()
	{
		return $this->current_user !== NULL;
	}
}

/**
 * Admin_Controller
 *
 * Guards every controller inside application/controllers/admin/. Also swaps
 * the layout to the admin shell.
 */
class Admin_Controller extends MY_Controller
{
	protected $layout = 'layouts/admin';

	public function __construct()
	{
		parent::__construct();

		if ($this->current_user === NULL)
		{
			$this->session->set_userdata('redirect_after_login', current_url());
			$this->redirect_with('login', 'error', 'Halaman admin hanya untuk pengguna yang sudah masuk.');
		}

		if ( ! is_staff_role($this->current_user['role']))
		{
			$this->redirect_with('/', 'error', 'Anda tidak memiliki hak akses ke halaman administrator.');
		}

		$this->data['admin_user'] = $this->current_user;
		$this->data['pending_orders'] = $this->order_model->count_by_status('pending');
	}
}
