<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin order detail: verification, license audit and status control.
 *
 * @var array $order
 * @var array $items
 * @var array $licenses
 * @var bool  $has_proof
 * @var bool  $proof_is_image
 */
$licenses_by_product = array();

foreach ($licenses as $license)
{
	$licenses_by_product[(int) $license['product_id']] = $license;
}
?>
<div class="card">
	<div class="row-between">
		<div>
			<h3 class="mb-1 mono"><?php echo html_escape($order['order_number']); ?></h3>
			<p class="text-muted mb-0">
				Dibuat <?php echo datetime_id($order['created_at']); ?> &middot;
				<?php echo html_escape(payment_method_label($order['payment_method'])); ?> &middot;
				<strong>Rp <?php echo rupiah($order['total_amount'], FALSE); ?></strong>
			</p>
		</div>
		<div class="row row-wrap">
			<?php echo status_badge($order['payment_status']); ?>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/orders'); ?>">Kembali</a>
		</div>
	</div>
</div>

<div class="grid grid-2">
	<div>
		<div class="card">
			<div class="card-head"><h3>Item pesanan &amp; lisensi</h3></div>

			<?php foreach ($items as $item): ?>
				<?php $license = isset($licenses_by_product[(int) $item['product_id']]) ? $licenses_by_product[(int) $item['product_id']] : NULL; ?>
				<div class="cart-line">
					<div class="cart-line-body">
						<h4><?php echo html_escape($item['product_title_snapshot']); ?></h4>
						<div class="cart-line-meta"><?php echo html_escape($item['file_format']); ?></div>

						<?php if ($license): ?>
							<div class="license-key mt-1"><?php echo html_escape($license['license_key']); ?></div>
							<div class="cell-sub">
								<?php echo (int) $license['download_count']; ?>/<?php echo (int) $license['max_downloads']; ?> unduhan &middot;
								terakhir <?php echo datetime_id($license['last_downloaded_at']); ?>
							</div>
						<?php else: ?>
							<span class="badge badge-warning mt-1">Lisensi belum diterbitkan</span>
						<?php endif; ?>
					</div>
					<div class="text-right nowrap">
						<div class="cart-line-price">Rp <?php echo rupiah($item['price_at_purchase'], FALSE); ?></div>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="summary-row total">
				<span>Total</span>
				<span>Rp <?php echo rupiah($order['total_amount'], FALSE); ?></span>
			</div>
		</div>

		<div class="card">
			<div class="card-head"><h3>Bukti pembayaran</h3></div>

			<?php if ( ! $has_proof): ?>
				<p class="text-muted mb-0">Pelanggan belum mengunggah bukti pembayaran.</p>
			<?php else: ?>
				<?php if ($proof_is_image): ?>
					<a href="<?php echo base_url($order['payment_proof']); ?>" target="_blank" rel="noopener">
						<img src="<?php echo base_url($order['payment_proof']); ?>" alt="Bukti pembayaran" style="border-radius:10px;border:1px solid var(--border)">
					</a>
				<?php endif; ?>
				<div class="row mt-2">
					<a class="btn btn-ghost btn-sm" href="<?php echo base_url($order['payment_proof']); ?>" target="_blank" rel="noopener">Buka file asli &#8599;</a>
					<span class="text-dim mono"><small><?php echo html_escape(basename($order['payment_proof'])); ?></small></span>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div>
		<div class="card">
			<div class="card-head"><h3>Data pelanggan</h3></div>
			<ul class="spec-list">
				<li><span class="spec-key">Nama</span><span class="spec-val"><?php echo html_escape($order['full_name']); ?></span></li>
				<li><span class="spec-key">Email</span><span class="spec-val"><?php echo html_escape($order['email']); ?></span></li>
				<li><span class="spec-key">User ID</span><span class="spec-val">#<?php echo (int) $order['user_id']; ?></span></li>
				<li><span class="spec-key">Dibayar pada</span><span class="spec-val"><?php echo $order['paid_at'] ? datetime_id($order['paid_at']) : '-'; ?></span></li>
			</ul>
			<?php if ( ! empty($order['notes'])): ?>
				<hr>
				<span class="eyebrow">Catatan pelanggan</span>
				<p class="text-muted mb-0"><?php echo nl2br(html_escape($order['notes'])); ?></p>
			<?php endif; ?>
		</div>

		<div class="card">
			<div class="card-head"><h3>Tindakan admin</h3></div>

			<?php if ($order['payment_status'] !== 'paid'): ?>
				<?php echo form_open('admin/orders/mark_paid/'.$order['id']); ?>
					<button type="submit" class="btn btn-success btn-block" data-confirm="Tandai lunas dan terbitkan lisensi untuk pelanggan?">
						Tandai Lunas &amp; Terbitkan Lisensi
					</button>
				<?php echo form_close(); ?>
			<?php else: ?>
				<?php echo form_open('admin/orders/issue_licenses/'.$order['id']); ?>
					<button type="submit" class="btn btn-ghost btn-block">Terbitkan ulang lisensi yang belum ada</button>
				<?php echo form_close(); ?>
			<?php endif; ?>

			<hr>

			<?php echo form_open('admin/orders/set_status/'.$order['id']); ?>
				<div class="form-group">
					<label for="status">Ubah status pembayaran</label>
					<select id="status" name="status">
						<?php foreach (array('pending', 'paid', 'failed', 'cancelled') as $status): ?>
							<option value="<?php echo $status; ?>" <?php echo $order['payment_status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button type="submit" class="btn btn-ghost btn-block">Simpan Status</button>
			<?php echo form_close(); ?>

			<p class="text-dim mt-2 mb-0"><small>Menandai lunas akan otomatis membuat baris <span class="mono">user_licenses</span> berisi serial number dan token unduhan.</small></p>
		</div>
	</div>
</div>
