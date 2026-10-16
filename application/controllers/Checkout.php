<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Checkout
 *
 * Turns the session cart into a pending order, then handles the manual
 * payment proof upload that the admin verifies.
 */
class Checkout extends Customer_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Review page: payment method + order summary.
	 *
	 * @return void
	 */
	public function index()
	{
		$cart = $this->refresh_cart();

		if (empty($cart))
		{
			$this->redirect_with('cart', 'error', 'Keranjang Anda kosong. Tambahkan produk terlebih dahulu.');
		}

		$this->render('checkout/index', array(
			'lines'           => $cart,
			'totals'          => $this->cart_totals($cart),
			'payment_methods' => $this->config->item('payment_methods'),
			'bank_accounts'   => $this->config->item('bank_accounts'),
		), 'Pembayaran');
	}

	/**
	 * Create the order (POST only).
	 *
	 * @return void
	 */
	public function place()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('checkout');
		}

		$limit = $this->rate_limit_model->check('checkout');

		if ( ! $limit['allowed'])
		{
			$this->redirect_with('cart', 'error', 'Terlalu banyak permintaan checkout. Coba lagi dalam '.ceil($limit['retry_after'] / 60).' menit.');
		}

		$cart = $this->refresh_cart();

		if (empty($cart))
		{
			$this->redirect_with('cart', 'error', 'Keranjang Anda kosong.');
		}

		$methods = array_keys($this->config->item('payment_methods'));
		$method  = (string) $this->input->post('payment_method', TRUE);

		if ( ! in_array($method, $methods, TRUE))
		{
			$this->redirect_with('checkout', 'error', 'Metode pembayaran tidak valid.');
		}

		$items = array();

		foreach ($cart as $line)
		{
			$items[] = array(
				'product_id' => (int) $line['product_id'],
				'title'      => $line['title'],
				'price'      => (float) $line['price'],
			);
		}

		$notes = trim((string) $this->input->post('notes', TRUE));

		if (mb_strlen($notes) > 500)
		{
			$notes = mb_substr($notes, 0, 500);
		}

		$order = $this->order_model->create_order($this->current_user['id'], $items, $method, $notes);

		if ( ! $order)
		{
			$this->redirect_with('checkout', 'error', 'Pesanan gagal dibuat. Silakan coba lagi.');
		}

		// Cart has become an order; keep the ownership check honest.
		$this->cart_save(array());

		redirect('checkout/success/'.$order['id']);
	}

	/**
	 * Payment instruction + proof upload page.
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function success($order_id)
	{
		$order = $this->owned_order($order_id);

		$this->render('checkout/success', array(
			'order'         => $order,
			'items'         => $this->order_model->get_items($order['id']),
			'bank_accounts' => $this->config->item('bank_accounts'),
		), 'Pesanan '.$order['order_number']);
	}

	/**
	 * Store the uploaded payment proof (POST only).
	 *
	 * @param  int $order_id
	 * @return void
	 */
	public function proof($order_id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('checkout/success/'.(int) $order_id);
		}

		$order = $this->owned_order($order_id);

		if ($order['payment_status'] === 'paid')
		{
			$this->redirect_with('checkout/success/'.$order['id'], 'info', 'Pesanan ini sudah lunas.');
		}

		$upload_path = $this->config->item('upload_payment_proof_path');

		if ( ! is_dir(FCPATH.$upload_path))
		{
			@mkdir(FCPATH.$upload_path, 0755, TRUE);
		}

		$config = array(
			'upload_path'   => FCPATH.$upload_path,
			'allowed_types' => 'jpg|jpeg|png|webp|pdf',
			'max_size'      => 4096, // KB
			'file_name'     => 'proof_'.$order['order_number'].'_'.time(),
			'overwrite'     => FALSE,
		);

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload('payment_proof'))
		{
			$this->redirect_with('checkout/success/'.$order['id'], 'error', strip_tags($this->upload->display_errors('', '')));
		}

		$file = $this->upload->data();
		$relative = $upload_path.$file['file_name'];

		// Remove the previously uploaded proof, if any.
		if ( ! empty($order['payment_proof'])
			&& strpos($order['payment_proof'], $upload_path) === 0
			&& is_file(FCPATH.$order['payment_proof']))
		{
			@unlink(FCPATH.$order['payment_proof']);
		}

		$this->order_model->attach_proof($order['id'], $relative);

		$this->redirect_with('checkout/success/'.$order['id'], 'success', 'Bukti pembayaran terkirim. Admin akan memverifikasi pesanan Anda.');
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Load an order and assert it belongs to the signed-in customer.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	protected function owned_order($order_id)
	{
		$order = $this->order_model->get_for_user((int) $order_id, $this->current_user['id']);

		if ( ! $order)
		{
			show_404();
		}

		return $order;
	}

	/**
	 * Re-validate cart lines against the live catalog before charging.
	 *
	 * @return array
	 */
	protected function refresh_cart()
	{
		$cart = $this->cart();

		if (empty($cart))
		{
			return array();
		}

		$products = $this->product_model->get_many(array_keys($cart));
		$clean    = array();

		foreach ($cart as $id => $line)
		{
			if ( ! isset($products[$id]) || (int) $products[$id]['is_active'] !== 1)
			{
				continue;
			}

			// Never charge twice for a license the customer already owns.
			if ($this->license_model->user_owns($this->current_user['id'], $id))
			{
				continue;
			}

			$product = $products[$id];

			$clean[$id] = array(
				'product_id'  => (int) $product['id'],
				'title'       => $product['title'],
				'slug'        => $product['slug'],
				'price'       => (float) $product['price'],
				'version'     => $product['version'],
				'file_format' => $product['file_format'],
			);
		}

		$this->cart_save($clean);

		return $clean;
	}
}
