<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Checkout success / payment instruction page.
 *
 * @var array $order
 * @var array $items
 * @var array $bank_accounts
 */
$status_notes = array(
	'pending'   => 'Pesanan Anda tercatat. Silakan selesaikan pembayaran lalu unggah bukti transfer pada formulir di bawah.',
	'paid'      => 'Pembayaran sudah diverifikasi. Produk Anda dapat diunduh dari Digital Library.',
	'failed'    => 'Pembayaran ditandai gagal. Silakan hubungi admin atau buat pesanan baru.',
	'cancelled' => 'Pesanan ini telah dibatalkan.',
);
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('/'); ?>">Beranda</a>
	<span>/</span>
	<a href="<?php echo site_url('account/orders'); ?>">Pesanan Saya</a>
	<span>/</span>
	<span><?php echo html_escape($order['order_number']); ?></span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1">Pesanan <?php echo html_escape($order['order_number']); ?></h2>
		<p class="text-muted mb-0">Dibuat <?php echo datetime_id($order['created_at']); ?></p>
	</div>
	<div><?php echo status_badge($order['payment_status']); ?></div>
</div>

<div class="alert alert-info">
	<span class="alert-icon">&#8505;</span>
	<span><?php echo html_escape($status_notes[$order['payment_status']]); ?></span>
</div>

<div class="cart-layout">
	<div>
		<div class="card mb-2">
			<div class="card-head">
				<h3>Rincian pesanan</h3>
				<span class="badge badge-muted"><?php echo html_escape(payment_method_label($order['payment_method'])); ?></span>
			</div>

			<?php foreach ($items as $item): ?>
				<div class="cart-line">
					<div class="cart-line-body">
						<h4><?php echo html_escape($item['product_title_snapshot']); ?></h4>
						<div class="cart-line-meta"><?php echo html_escape($item['file_format']); ?></div>
					</div>
					<div class="cart-line-price">Rp <?php echo rupiah($item['price_at_purchase'], FALSE); ?></div>
				</div>
			<?php endforeach; ?>

			<div class="summary-row total">
				<span>Total</span>
				<span>Rp <?php echo rupiah($order['total_amount'], FALSE); ?></span>
			</div>

			<?php if ( ! empty($order['notes'])): ?>
				<hr>
				<span class="eyebrow">Catatan Anda</span>
				<p class="text-muted mb-0"><?php echo nl2br(html_escape($order['notes'])); ?></p>
			<?php endif; ?>
		</div>

		<?php if ($order['payment_status'] === 'pending'): ?>
			<div class="card">
				<div class="card-head">
					<h3>Unggah bukti pembayaran</h3>
				</div>

				<?php echo form_open_multipart('checkout/proof/'.$order['id']); ?>
					<div class="form-group">
						<label for="payment_proof">File bukti transfer <span class="req">*</span></label>
						<input type="file" id="payment_proof" name="payment_proof" accept="image/png,image/jpeg,image/webp,application/pdf" required data-filename-target="#proof-name">
						<div class="form-hint">Format: JPG, PNG, WEBP, atau PDF. Maksimal 4 MB.</div>
						<div class="form-hint mono" id="proof-name"></div>
					</div>

					<button type="submit" class="btn btn-primary">Kirim Bukti Pembayaran</button>
				<?php echo form_close(); ?>
			</div>
		<?php elseif ( ! empty($order['payment_proof'])): ?>
			<div class="card">
				<div class="card-head"><h3>Bukti pembayaran</h3></div>
				<div class="bank-card">
					<div>
						<div class="bank-name">File terunggah</div>
						<div class="bank-number" style="font-size:.95rem"><?php echo html_escape(basename($order['payment_proof'])); ?></div>
					</div>
				</div>
				<?php if (strpos((string) $order['payment_proof'], 'payment_proofs/') !== FALSE): ?>
					<a class="btn btn-ghost btn-sm" href="<?php echo base_url($order['payment_proof']); ?>" target="_blank" rel="noopener">Lihat file</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<aside class="card">
		<div class="card-head"><h3>Informasi pengiriman</h3></div>

		<ul class="spec-list">
			<li><span class="spec-key">Nomor pesanan</span><span class="spec-val mono"><?php echo html_escape($order['order_number']); ?></span></li>
			<li><span class="spec-key">Metode</span><span class="spec-val"><?php echo html_escape(payment_method_label($order['payment_method'])); ?></span></li>
			<li><span class="spec-key">Status</span><span class="spec-val"><?php echo status_badge($order['payment_status']); ?></span></li>
			<li><span class="spec-key">Dibayar pada</span><span class="spec-val"><?php echo $order['paid_at'] ? datetime_id($order['paid_at']) : '-'; ?></span></li>
		</ul>

		<hr>
		<span class="eyebrow">Rekening tujuan</span>
		<?php foreach ($bank_accounts as $account): ?>
			<div class="bank-card">
				<div>
					<div class="bank-name"><?php echo html_escape($account['bank']); ?></div>
					<div class="bank-number"><?php echo html_escape($account['number']); ?></div>
					<div class="bank-holder"><?php echo html_escape($account['holder']); ?></div>
				</div>
				<button type="button" class="btn-icon" data-copy="<?php echo html_escape($account['number']); ?>" title="Salin nomor rekening">&#10697;</button>
			</div>
		<?php endforeach; ?>

		<a class="btn btn-ghost btn-block mt-2" href="<?php echo site_url('account/orders'); ?>">Buka Riwayat Pesanan</a>
	</aside>
</div>
