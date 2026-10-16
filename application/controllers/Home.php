<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Home
 *
 * Public landing page for the M.O.R.P.H.I.C storefront.
 */
class Home extends Frontend_Controller
{
	public function index()
	{
		$product_stats = $this->product_model->stats();
		$license_stats = $this->license_model->stats();

		$this->render('home/index', array(
			'featured'    => $this->product_model->get_featured(4),
			'latest'      => $this->product_model->get_latest(6),
			'slider'      => $this->product_model->get_latest(8),
			'categories'  => $this->category_model->get_with_counts(),
			'cart_ids'    => $this->cart_product_ids(),
			'hero_stats'  => array(
				'products'  => $product_stats['active'],
				'downloads' => $license_stats['downloads'],
				'licenses'  => $license_stats['total'],
			),
		), 'Digital Software & Asset Store untuk Kreator & Musisi');
	}

	/**
	 * Product ids currently in the session cart, used by the product card
	 * partial to flip the "Add to cart" button into "In cart".
	 *
	 * @return array
	 */
	protected function cart_product_ids()
	{
		return array_map('intval', array_keys($this->cart()));
	}
}
