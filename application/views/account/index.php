<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer dashboard.
 *
 * @var array $licenses
 * @var array $recent_orders
 * @var array $summary
 */
?>
<div class="row-between mb-3">
	<div>
		<span class="eyebrow">Akun Saya</span>
		<h2 class="mb-1">Halo, <?php echo html_escape($current_user['full_name']); ?></h2>
		<p class="text-muted mb-0"><?php echo html_escape($current_user['email']); ?> &middot; bergabung <?php echo datetime_id($current_user['created_at'], FALSE); ?></p>
	</div>
	<a class="btn btn-primary" href="<?php echo site_url('library'); ?>">Buka Digital Library</a>
</div>

<div class="stat-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Total pesanan</div>
		<div class="stat-value"><?php echo (int) $summary['orders']; ?></div>
		<div class="stat-foot"><a href="<?php echo site_url('account/orders'); ?>">Lihat riwayat</a></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Lisensi aktif</div>
		<div class="stat-value"><?php echo (int) $summary['licenses']; ?></div>
		<div class="stat-foot"><a href="<?php echo site_url('library'); ?>">Buka library</a></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Menunggu pembayaran</div>
		<div class="stat-value"><?php echo (int) $summary['pending']; ?></div>
		<div class="stat-foot">Selesaikan untuk membuka unduhan</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Unduhan tercatat</div>
		<div class="stat-value"><?php echo (int) $summary['downloads']; ?></div>
		<div class="stat-foot">Dari 4 lisensi terakhir</div>
	</div>
</div>

<div class="grid grid-2">
	<section class="card">
		<div class="card-head">
			<h3>Lisensi terbaru</h3>
			<a class="text-dim" href="<?php echo site_url('library'); ?>"><small>Semua lisensi</small></a>
		</div>

		<?php if (empty($licenses)): ?>
			<p class="text-muted mb-0">Belum ada lisensi. Produk yang Anda beli akan otomatis muncul di sini setelah pembayaran diverifikasi.</p>
		<?php else: ?>
			<?php foreach ($licenses as $license): ?>
				<div class="cart-line">
					<div class="cart-line-body">
						<h4><a href="<?php echo site_url('account/license/'.$license['id']); ?>"><?php echo html_escape($license['title']); ?></a></h4>
						<div class="cart-line-meta">
							<?php echo html_escape($license['order_number']); ?> &middot;
							<?php echo (int) $license['download_count']; ?>/<?php echo (int) $license['max_downloads']; ?> unduhan
						</div>
					</div>
					<?php if ($this->license_model->is_downloadable($license)): ?>
						<a class="btn btn-primary btn-sm" href="<?php echo site_url('download/'.$license['download_token']); ?>">Unduh</a>
					<?php else: ?>
						<span class="badge badge-muted">Tidak tersedia</span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Pesanan terbaru</h3>
			<a class="text-dim" href="<?php echo site_url('account/orders'); ?>"><small>Semua pesanan</small></a>
		</div>

		<?php if (empty($recent_orders)): ?>
			<p class="text-muted mb-0">Belum ada pesanan. <a href="<?php echo site_url('products'); ?>">Mulai belanja</a> sekarang.</p>
		<?php else: ?>
			<?php foreach ($recent_orders as $order): ?>
				<div class="cart-line">
					<div class="cart-line-body">
						<h4><a href="<?php echo site_url('account/order/'.$order['id']); ?>"><?php echo html_escape($order['order_number']); ?></a></h4>
						<div class="cart-line-meta"><?php echo datetime_id($order['created_at']); ?></div>
					</div>
					<div class="text-right">
						<div class="cart-line-price">Rp <?php echo rupiah($order['total_amount'], FALSE); ?></div>
						<?php echo status_badge($order['payment_status']); ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</section>
</div>
