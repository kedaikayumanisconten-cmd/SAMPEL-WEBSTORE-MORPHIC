<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cart
 *
 * Session based shopping cart. Every mutation is a POST so CodeIgniter's CSRF
 * protection applies automatically.
 */
class Cart extends Frontend_Controller
{
	public function index()
	{
		$cart  = $this->refresh_cart();
		$lines = $this->lines($cart);

		$this->render('cart/index', array(
			'lines'   => $lines,
			'totals'  => $this->cart_totals($cart),
		), 'Keranjang Belanja');
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param  int $product_id
	 * @return void
	 */
	public function add($product_id)
	{
		$this->require_post('cart');

		$product = $this->product_model->get((int) $product_id, TRUE);

		if ( ! $product)
		{
			$this->redirect_with('products', 'error', 'Produk tidak ditemukan atau sudah tidak dijual.');
		}

		$cart = $this->cart();
		$id   = (int) $product['id'];

		// Digital goods are sold once per account; never allow a duplicate buy.
		if ($this->current_user && $this->license_model->user_owns($this->current_user['id'], $id))
		{
			$this->redirect_with('library', 'info', 'Anda sudah memiliki lisensi produk ini. Produk tersedia di Digital Library Anda.');
		}

		if (isset($cart[$id]))
		{
			$this->redirect_with($this->back_target(), 'info', 'Produk sudah ada di keranjang Anda.');
		}

		// Digital goods: always priced from the live catalog, quantity is 1.
		$cart[$id] = array(
			'product_id' => $id,
			'title'      => $product['title'],
			'slug'       => $product['slug'],
			'price'      => (float) $product['price'],
			'version'    => $product['version'],
			'file_format' => $product['file_format'],
			'thumbnail_url' => $product['thumbnail_url'],
		);

		$this->cart_save($cart);

		$this->redirect_with($this->back_target(), 'success', '“'.$product['title'].'” ditambahkan ke keranjang.');
	}

	/**
	 * Remove a single line.
	 *
	 * @param  int $product_id
	 * @return void
	 */
	public function remove($product_id)
	{
		$this->require_post('cart');

		$cart = $this->cart();
		$id   = (int) $product_id;

		if (isset($cart[$id]))
		{
			unset($cart[$id]);
			$this->cart_save($cart);
			$this->redirect_with('cart', 'success', 'Produk dihapus dari keranjang.');
		}

		$this->redirect_with('cart', 'info', 'Produk tersebut tidak ada di keranjang.');
	}

	/**
	 * Empty the cart.
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->require_post('cart');

		$this->cart_save(array());
		$this->redirect_with('cart', 'success', 'Keranjang dikosongkan.');
	}

	/**
	 * Validate the cart against the live catalog: prices are refreshed from
	 * the database and products that are no longer on sale are dropped.
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
		$removed  = 0;

		foreach ($cart as $id => $line)
		{
			if ( ! isset($products[$id]) || (int) $products[$id]['is_active'] !== 1)
			{
				$removed++;

				continue;
			}

			// Already owned (or re-added from another tab): never re-sell it.
			if ($this->current_user && $this->license_model->user_owns($this->current_user['id'], $id))
			{
				$removed++;

				continue;
			}

			$product = $products[$id];

			$clean[$id] = array(
				'product_id'    => (int) $product['id'],
				'title'         => $product['title'],
				'slug'          => $product['slug'],
				'price'         => (float) $product['price'],
				'version'       => $product['version'],
				'file_format'   => $product['file_format'],
				'thumbnail_url' => $product['thumbnail_url'],
			);
		}

		if (count($clean) !== count($cart))
		{
			$this->session->set_flashdata('info', 'Beberapa produk tidak lagi tersedia atau sudah Anda miliki, sehingga dihapus dari keranjang.');
		}

		$this->cart_save($clean);

		return $clean;
	}

	/**
	 * Decorate cart lines with ownership / thumbnail information.
	 *
	 * @param  array $cart
	 * @return array
	 */
	protected function lines(array $cart)
	{
		$lines = array();

		foreach ($cart as $line)
		{
			$line['owned'] = $this->current_user
				? $this->license_model->user_owns($this->current_user['id'], $line['product_id'])
				: FALSE;

			$lines[] = $line;
		}

		return $lines;
	}

	/**
	 * Cart mutations are POST only.
	 *
	 * @param  string $fallback
	 * @return void
	 */
	protected function require_post($fallback)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			$this->redirect_with($fallback, 'error', 'Permintaan tidak valid.');
		}
	}

	/**
	 * Where to send the visitor back to after a cart action.
	 *
	 * @return string
	 */
	protected function back_target()
	{
		$referer = (string) $this->input->server('HTTP_REFERER');

		if ($referer !== '' && strpos($referer, base_url()) === 0)
		{
			return $referer;
		}

		return 'cart';
	}
}
