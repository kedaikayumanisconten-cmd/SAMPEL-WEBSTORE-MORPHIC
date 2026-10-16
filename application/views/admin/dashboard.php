<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin dashboard: sales + traffic analytics.
 *
 * Charts are rendered as inline SVG by the analytics_helper, so the panel has
 * no charting dependency at all. Every point carries a native <title> tooltip.
 *
 * @var int   $window
 * @var array $order_stats
 * @var array $status_split
 * @var array $revenue_series
 * @var array $orders_series
 * @var array $recent_orders
 * @var array $recent_paid
 * @var array $best_sellers
 * @var array $visit_stats
 * @var array $visit_series
 * @var array $hour_series
 * @var array $recent_visits
 * @var array $top_pages
 * @var array $devices
 * @var array $audience
 * @var array $referrers
 * @var array $product_stats
 * @var array $user_stats
 * @var int   $new_customers
 * @var array $recent_users
 * @var array $license_stats
 * @var array $registration_series
 * @var array $low_stock
 */
// --- chart inputs -----------------------------------------------------------
$revenue_chart = array();
$orders_chart  = array();
$reg_chart     = array();

foreach ($revenue_series as $day)
{
	$revenue_chart[] = array(
		'label' => $day['label'],
		'value' => (float) $day['revenue'],
		'note'  => (int) $day['orders'].' pesanan lunas',
	);
}

foreach ($orders_series as $day)
{
	$orders_chart[] = array(
		'label' => $day['label'],
		'value' => (int) $day['value'],
		'note'  => $day['note'],
	);
}

foreach ($registration_series as $day)
{
	$reg_chart[] = array(
		'label' => $day['label'],
		'value' => (int) $day['value'],
	);
}

$traffic_chart = array();
$unique_chart  = array();

foreach ($visit_series as $day)
{
	$traffic_chart[] = array(
		'label' => $day['label'],
		'value' => (int) $day['hits'],
		'note'  => 'dari '.$day['visitors'].' pengunjung unik',
	);

	$unique_chart[] = array(
		'label' => $day['label'],
		'value' => (int) $day['visitors'],
		'note'  => 'pengunjung unik',
	);
}

$trend       = (int) $visit_stats['trend'];
$trend_class = $trend > 0 ? 'is-up' : ($trend < 0 ? 'is-down' : 'is-flat');
$trend_arrow = $trend > 0 ? '&#9650;' : ($trend < 0 ? '&#9660;' : '&#9679;');

$top_pages_rows = array();

foreach ($top_pages as $page)
{
	$top_pages_rows[] = array(
		'label' => $page['label'],
		'value' => $page['value'],
		'sub'   => $page['sub'],
	);
}

$referrer_rows = array();

foreach ($referrers as $referrer)
{
	$referrer_rows[] = array('label' => $referrer['label'], 'value' => $referrer['value']);
}
?>
<div class="kpi-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Pendapatan lunas</div>
		<div class="stat-value">Rp <?php echo rupiah($order_stats['revenue'], FALSE); ?></div>
		<div class="stat-foot"><?php echo (int) $order_stats['paid']; ?> pesanan lunas &middot; <?php echo (int) $order_stats['total']; ?> total pesanan</div>
	</div>

	<div class="stat-card">
		<div class="stat-label">Kunjungan hari ini</div>
		<div class="stat-value"><?php echo number_format((int) $visit_stats['today'], 0, ',', '.'); ?></div>
		<div class="stat-foot">
			<span class="stat-trend <?php echo $trend_class; ?>"><?php echo $trend_arrow; ?> <?php echo $trend > 0 ? '+' : ''; ?><?php echo $trend; ?>%</span>
			dibanding kemarin (<?php echo (int) $visit_stats['yesterday']; ?>)
		</div>
	</div>

	<div class="stat-card">
		<div class="stat-label">Pengunjung unik (<?php echo (int) $window; ?> hari)</div>
		<div class="stat-value"><?php echo number_format((int) $visit_stats['unique'], 0, ',', '.'); ?></div>
		<div class="stat-foot"><?php echo (int) $visit_stats['hits']; ?> page view &middot; <?php echo (int) $visit_stats['per_day']; ?>/hari</div>
	</div>

	<div class="stat-card">
		<div class="stat-label">Menunggu verifikasi</div>
		<div class="stat-value"><?php echo (int) $order_stats['pending']; ?></div>
		<div class="stat-foot"><a href="<?php echo site_url('admin/orders?status=pending'); ?>">Periksa sekarang</a></div>
	</div>
</div>

<div class="grid grid-wide mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Pendapatan <?php echo (int) count($revenue_series); ?> hari terakhir</h3>
			<span class="badge badge-accent">Rp <?php echo rupiah($order_stats['revenue'], FALSE); ?> total</span>
		</div>
		<span class="chart-caption">Garis utama: nilai pesanan lunas per hari &middot; arahkan kursor untuk detail</span>
		<?php echo svg_area_chart($revenue_chart, array(
			'width'  => 540,
			'height' => 250,
			'format' => 'rupiah',
			'color'  => '#7c5cff',
			'empty'  => 'Belum ada pesanan lunas pada periode ini.',
		)); ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Status pesanan</h3>
			<span class="badge badge-muted"><?php echo (int) $order_stats['total']; ?> pesanan</span>
		</div>
		<?php echo svg_donut_chart($status_split, array(
			'caption' => 'Pesanan',
			'empty'   => 'Belum ada pesanan.',
		)); ?>
	</section>
</div>

<div class="grid grid-wide mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Kunjungan <?php echo (int) count($visit_series); ?> hari terakhir</h3>
			<span class="badge badge-muted"><?php echo number_format((int) $visit_stats['hits'], 0, ',', '.'); ?> page view</span>
		</div>
		<span class="chart-legend-row">
			<span><span class="legend-dot" style="background:#7c5cff"></span> Page view</span>
			<span><span class="legend-dot" style="background:#22d3ee"></span> Pengunjung unik</span>
			<?php if ((int) $visit_stats['bots'] > 0): ?>
				<span class="text-dim"><?php echo (int) $visit_stats['bots']; ?> hit bot diabaikan</span>
			<?php endif; ?>
		</span>
		<?php echo svg_area_chart($traffic_chart, array(
			'width'    => 540,
			'height'   => 250,
			'series2'  => $unique_chart,
			'color'    => '#7c5cff',
			'color2'   => '#22d3ee',
			'empty'    => 'Belum ada kunjungan tercatat.',
		)); ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Perangkat pengunjung</h3>
			<span class="badge badge-muted"><?php echo (int) $window; ?> hari</span>
		</div>
		<?php echo svg_donut_chart($devices, array(
			'size'    => 156,
			'caption' => 'Kunjungan',
			'empty'   => 'Belum ada data perangkat.',
		)); ?>
	</section>
</div>

<div class="grid grid-2 mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Jam sibuk pengunjung</h3>
			<span class="badge badge-muted">pukul 00 - 23</span>
		</div>
		<?php echo svg_bar_chart($hour_series, array(
			'width'  => 460,
			'height' => 220,
			'color'  => '#22d3ee',
			'empty'  => 'Belum ada kunjungan.',
		)); ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Halaman terpopuler</h3>
			<a class="text-dim" href="<?php echo site_url('admin/visits'); ?>"><small>Riwayat lengkap</small></a>
		</div>
		<?php echo html_hbar_list($top_pages_rows, array(
			'empty' => 'Belum ada halaman yang dikunjungi.',
		)); ?>
	</section>
</div>

<div class="grid grid-2 mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Riwayat kunjungan terbaru</h3>
			<a class="text-dim" href="<?php echo site_url('admin/visits'); ?>"><small>Semua kunjungan</small></a>
		</div>

		<?php if (empty($recent_visits)): ?>
			<p class="text-muted mb-0">Belum ada kunjungan yang tercatat.</p>
		<?php else: ?>
			<ul class="feed-list">
				<?php foreach ($recent_visits as $visit): ?>
					<li class="feed-item">
						<span class="feed-dot <?php echo $visit['device'] === 'bot' ? 'is-bot' : (($visit['user_id'] !== NULL) ? 'is-member' : ''); ?>"></span>
						<div class="feed-body">
							<div class="feed-title"><?php echo html_escape(visit_page_label($visit['uri'])); ?></div>
							<div class="feed-meta">
								<?php echo $visit['full_name'] !== NULL ? html_escape($visit['full_name']) : 'Tamu'; ?>
								&middot; <?php echo html_escape($visit['ip_address']); ?>
								&middot; <?php echo html_escape(time_ago($visit['visited_at'])); ?>
							</div>
						</div>
						<span class="badge badge-muted"><?php echo device_name($visit['device']); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Asal kunjungan &amp; audiens</h3>
		</div>

		<span class="eyebrow">Referer eksternal</span>
		<?php echo html_hbar_list($referrer_rows, array(
			'empty' => 'Belum ada referer eksternal. Pengunjung datang langsung atau dari dalam situs.',
		)); ?>

		<hr>

		<span class="eyebrow">Profil pengunjung</span>
		<?php echo svg_donut_chart($audience, array(
			'size'    => 140,
			'caption' => 'Kunjungan',
			'empty'   => 'Belum ada data audiens.',
		)); ?>
	</section>
</div>

<div class="grid grid-3 mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Produk terlaris</h3>
			<span class="badge badge-muted">Semua waktu</span>
		</div>

		<?php if (empty($best_sellers)): ?>
			<p class="text-muted mb-0">Belum ada penjualan tercatat.</p>
		<?php else: ?>
			<ul class="spec-list">
				<?php foreach ($best_sellers as $item): ?>
					<li>
						<span class="spec-key"><a href="<?php echo site_url('admin/products/edit/'.$item['id']); ?>"><?php echo html_escape(excerpt($item['title'], 30)); ?></a></span>
						<span class="spec-val"><?php echo (int) $item['sold']; ?> terjual</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Pembelian terbaru</h3>
			<a class="text-dim" href="<?php echo site_url('admin/orders?status=paid'); ?>"><small>Semua</small></a>
		</div>

		<?php if (empty($recent_paid)): ?>
			<p class="text-muted mb-0">Belum ada pembayaran lunas.</p>
		<?php else: ?>
			<ul class="feed-list">
				<?php foreach ($recent_paid as $order): ?>
					<li class="feed-item">
						<span class="feed-dot is-paid"></span>
						<div class="feed-body">
							<div class="feed-title">
								<a href="<?php echo site_url('admin/orders/detail/'.$order['id']); ?>"><?php echo html_escape($order['order_number']); ?></a>
								&middot; Rp <?php echo rupiah($order['total_amount'], FALSE); ?>
							</div>
							<div class="feed-meta"><?php echo html_escape($order['full_name']); ?> &middot; <?php echo html_escape(time_ago($order['paid_at'])); ?></div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Pelanggan baru</h3>
			<span class="badge badge-accent"><?php echo (int) $new_customers; ?> / <?php echo (int) $window; ?> hari</span>
		</div>

		<?php if (empty($recent_users)): ?>
			<p class="text-muted mb-0">Belum ada pendaftar.</p>
		<?php else: ?>
			<ul class="feed-list">
				<?php foreach ($recent_users as $user): ?>
					<li class="feed-item">
						<span class="avatar"><?php echo html_escape(initials_of($user['full_name'])); ?></span>
						<div class="feed-body">
							<div class="feed-title"><?php echo html_escape($user['full_name']); ?></div>
							<div class="feed-meta">
								<?php echo html_escape($user['email']); ?>
								&middot; <?php echo html_escape(time_ago($user['created_at'])); ?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
</div>

<div class="grid grid-wide">
	<section class="card">
		<div class="card-head">
			<h3>Pesanan terbaru</h3>
			<a class="text-dim" href="<?php echo site_url('admin/orders'); ?>"><small>Semua pesanan</small></a>
		</div>

		<?php if (empty($recent_orders)): ?>
			<p class="text-muted mb-0">Belum ada pesanan masuk.</p>
		<?php else: ?>
			<div class="table-wrap">
				<table class="data">
					<thead>
						<tr>
							<th>Nomor</th>
							<th>Pelanggan</th>
							<th>Total</th>
							<th>Status</th>
							<th>Waktu</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($recent_orders as $order): ?>
							<tr>
								<td class="mono"><a href="<?php echo site_url('admin/orders/detail/'.$order['id']); ?>"><?php echo html_escape($order['order_number']); ?></a></td>
								<td>
									<div class="cell-title"><?php echo html_escape($order['full_name']); ?></div>
									<div class="cell-sub"><?php echo html_escape($order['email']); ?></div>
								</td>
								<td class="nowrap">Rp <?php echo rupiah($order['total_amount'], FALSE); ?></td>
								<td><?php echo status_badge($order['payment_status']); ?></td>
								<td class="nowrap cell-sub"><?php echo html_escape(time_ago($order['created_at'])); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Ringkasan operasional</h3>
			<span class="badge badge-muted"><?php echo (int) $window; ?> hari</span>
		</div>

		<ul class="spec-list">
			<li><span class="spec-key">Page view</span><span class="spec-val"><?php echo number_format((int) $visit_stats['hits'], 0, ',', '.'); ?></span></li>
			<li><span class="spec-key">Pengunjung unik</span><span class="spec-val"><?php echo number_format((int) $visit_stats['unique'], 0, ',', '.'); ?></span></li>
			<li><span class="spec-key">Member vs tamu</span><span class="spec-val"><?php echo (int) $visit_stats['member_hits']; ?> / <?php echo (int) $visit_stats['guest_hits']; ?></span></li>
			<li><span class="spec-key">Hit bot</span><span class="spec-val"><?php echo number_format((int) $visit_stats['bots'], 0, ',', '.'); ?></span></li>
			<li><span class="spec-key">Pelanggan baru</span><span class="spec-val"><?php echo (int) $new_customers; ?></span></li>
			<li><span class="spec-key">Produk aktif</span><span class="spec-val"><?php echo (int) $product_stats['active']; ?> dari <?php echo (int) $product_stats['total']; ?></span></li>
			<li><span class="spec-key">Lisensi terbit</span><span class="spec-val"><?php echo (int) $license_stats['total']; ?> (<?php echo (int) $license_stats['downloads']; ?> unduhan)</span></li>
			<li><span class="spec-key">Total pengguna</span><span class="spec-val"><?php echo (int) $user_stats['total']; ?> (<?php echo (int) $user_stats['admins']; ?> staf)</span></li>
		</ul>

		<?php if ( ! empty($low_stock)): ?>
			<hr>
			<span class="eyebrow">Produk nonaktif</span>
			<ul class="spec-list">
				<?php foreach ($low_stock as $item): ?>
					<li>
						<span class="spec-key"><a href="<?php echo site_url('admin/products/edit/'.$item['id']); ?>"><?php echo html_escape(excerpt($item['title'], 30)); ?></a></span>
						<span class="spec-val"><span class="badge badge-muted">Nonaktif</span></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
</div>
