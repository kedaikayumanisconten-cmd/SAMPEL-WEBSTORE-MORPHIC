<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer order detail.
 *
 * @var array $order
 * @var array $items
 * @var array $licenses
 */
$licenses_by_product = array();

foreach ($licenses as $license)
{
	$licenses_by_product[(int) $license['product_id']] = $license;
}
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('account'); ?>">Akun</a>
	<span>/</span>
	<a href="<?php echo site_url('account/orders'); ?>">Pesanan</a>
	<span>/</span>
	<span class="mono"><?php echo html_escape($order['order_number']); ?></span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1"><?php echo html_escape($order['order_number']); ?></h2>
		<p class="text-muted mb-0">Dibuat <?php echo datetime_id($order['created_at']); ?> &middot; <?php echo html_escape(payment_method_label($order['payment_method'])); ?></p>
	</div>
	<div class="row">
		<?php echo status_badge($order['payment_status']); ?>
		<?php if ($order['payment_status'] === 'pending'): ?>
			<a class="btn btn-primary btn-sm" href="<?php echo site_url('checkout/success/'.$order['id']); ?>">Unggah bukti bayar</a>
		<?php endif; ?>
	</div>
</div>

<div class="grid grid-2">
	<section class="card">
		<div class="card-head"><h3>Produk</h3></div>

		<?php foreach ($items as $item): ?>
			<?php $license = isset($licenses_by_product[(int) $item['product_id']]) ? $licenses_by_product[(int) $item['product_id']] : NULL; ?>
			<div class="cart-line">
				<div class="cart-line-body">
					<h4><?php echo html_escape($item['product_title_snapshot']); ?></h4>
					<div class="cart-line-meta"><?php echo html_escape($item['file_format']); ?></div>

					<?php if ($license): ?>
						<div class="mt-1">
							<div class="license-key"><?php echo html_escape($license['license_key']); ?></div>
						</div>
					<?php elseif ($order['payment_status'] === 'pending'): ?>
						<span class="badge badge-warning mt-1">Menunggu verifikasi pembayaran</span>
					<?php endif; ?>
				</div>

				<div class="text-right">
					<div class="cart-line-price">Rp <?php echo rupiah($item['price_at_purchase'], FALSE); ?></div>
					<?php if ($license && $this->license_model->is_downloadable($license)): ?>
						<a class="btn btn-primary btn-sm mt-1" href="<?php echo site_url('download/'.$license['download_token']); ?>">Unduh</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="summary-row total">
			<span>Total</span>
			<span>Rp <?php echo rupiah($order['total_amount'], FALSE); ?></span>
		</div>
	</section>

	<aside class="card">
		<div class="card-head"><h3>Status pesanan</h3></div>

		<ul class="timeline">
			<li>
				<div class="tl-title">Pesanan dibuat</div>
				<div class="tl-time"><?php echo datetime_id($order['created_at']); ?></div>
			</li>
			<li>
				<div class="tl-title">Bukti pembayaran diunggah</div>
				<div class="tl-time"><?php echo ! empty($order['payment_proof']) ? 'Sudah diunggah' : 'Belum diunggah'; ?></div>
			</li>
			<li>
				<div class="tl-title">Pembayaran diverifikasi</div>
				<div class="tl-time"><?php echo $order['paid_at'] ? datetime_id($order['paid_at']) : 'Menunggu admin'; ?></div>
			</li>
			<li>
				<div class="tl-title">Lisensi diterbitkan</div>
				<div class="tl-time"><?php echo ! empty($licenses) ? count($licenses).' lisensi aktif' : 'Belum ada'; ?></div>
			</li>
		</ul>

		<hr>
		<ul class="spec-list">
			<li><span class="spec-key">Metode</span><span class="spec-val"><?php echo html_escape(payment_method_label($order['payment_method'])); ?></span></li>
			<li><span class="spec-key">Total</span><span class="spec-val">Rp <?php echo rupiah($order['total_amount'], FALSE); ?></span></li>
			<li><span class="spec-key">Bukti bayar</span><span class="spec-val"><?php echo ! empty($order['payment_proof']) ? 'Tersedia' : '-'; ?></span></li>
		</ul>

		<?php if ( ! empty($order['notes'])): ?>
			<hr>
			<span class="eyebrow">Catatan</span>
			<p class="text-muted mb-0"><?php echo nl2br(html_escape($order['notes'])); ?></p>
		<?php endif; ?>
	</aside>
</div>
