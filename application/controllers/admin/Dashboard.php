<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard
 *
 * Analytics home of the admin panel: sales counters, traffic log summaries,
 * chart data and the latest activity feeds.
 */
class Dashboard extends Admin_Controller
{
	/**
	 * Days shown on the time series charts. Kept shorter than the KPI window
	 * so the bars stay readable.
	 */
	const CHART_DAYS = 14;

	public function index()
	{
		$window = max(1, (int) $this->config->item('dashboard_window_days'));

		$this->render('admin/dashboard', array(
			'window'        => $window,

			// Sales
			'order_stats'   => $this->order_model->stats(),
			'status_split'  => $this->order_model->status_breakdown(),
			'revenue_series' => $this->order_model->revenue_by_day(self::CHART_DAYS),
			'orders_series' => $this->order_model->orders_by_day(self::CHART_DAYS),
			'recent_orders' => $this->order_model->get_recent(5),
			'recent_paid'   => $this->order_model->get_recent_paid(5),
			'best_sellers'  => $this->product_model->best_sellers(5),

			// Traffic
			'visit_stats'   => $this->visit_model->stats($window),
			'visit_series'  => $this->visit_model->daily(self::CHART_DAYS),
			'hour_series'   => $this->visit_model->by_hour($window),
			'recent_visits' => $this->visit_model->recent(8),
			'top_pages'     => $this->visit_model->top_pages($window, 6),
			'devices'       => $this->visit_model->devices($window),
			'audience'      => $this->visit_model->audience_split($window),
			'referrers'     => $this->visit_model->referrers($window, 5),

			// Catalyst
			'product_stats' => $this->product_model->stats(),
			'user_stats'    => $this->user_model->stats(),
			'new_customers' => $this->user_model->count_new($window),
			'recent_users'  => $this->user_model->get_recent(4),
			'license_stats' => $this->license_model->stats(),
			'registration_series' => $this->user_model->registrations_by_day(self::CHART_DAYS),
			'low_stock'     => $this->product_model->get_admin_listing(array('status' => 0), 5, 0),
		), 'Dashboard');
	}
}
