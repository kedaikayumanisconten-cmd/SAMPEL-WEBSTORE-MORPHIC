<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Checkout: choose a payment method and confirm the order.
 *
 * @var array $lines
 * @var array $totals
 * @var array $payment_methods
 * @var array $bank_accounts
 */
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('/'); ?>">Beranda</a>
	<span>/</span>
	<a href="<?php echo site_url('cart'); ?>">Keranjang</a>
	<span>/</span>
	<span>Pembayaran</span>
</div>

<h2 class="mb-3">Konfirmasi Pesanan</h2>

<?php echo form_open('checkout/place'); ?>
	<div class="cart-layout">
		<div>
			<div class="card mb-2">
				<div class="card-head">
					<h3>1. Produk yang dibeli</h3>
					<a class="text-dim" href="<?php echo site_url('cart'); ?>"><small>Ubah keranjang</small></a>
				</div>

				<?php foreach ($lines as $line): ?>
					<div class="cart-line">
						<div class="cart-line-body">
							<h4><?php echo html_escape($line['title']); ?></h4>
							<div class="cart-line-meta">v<?php echo html_escape($line['version']); ?> &middot; <?php echo html_escape($line['file_format']); ?></div>
						</div>
						<div class="cart-line-price">Rp <?php echo rupiah($line['price'], FALSE); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="card mb-2">
				<div class="card-head">
					<h3>2. Metode pembayaran</h3>
				</div>

				<?php foreach ($payment_methods as $value => $label): ?>
					<label class="payment-method">
						<input type="radio" name="payment_method" value="<?php echo html_escape($value); ?>" <?php echo $value === 'bank_transfer' ? 'checked' : ''; ?> required>
						<span>
							<span class="pm-title"><?php echo html_escape($label); ?></span><br>
							<span class="pm-desc">
								<?php
								if ($value === 'bank_transfer')
								{
									echo 'Transfer ke rekening perusahaan, lalu unggah bukti transfer.';
								}
								elseif ($value === 'virtual_account')
								{
									echo 'Nomor virtual account dibuat pada halaman pesanan setelah checkout.';
								}
								else
								{
									echo 'Pindai kode QRIS dari aplikasi bank atau e-wallet Anda.';
								}
								?>
							</span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="card">
				<div class="card-head">
					<h3>3. Catatan tambahan <span class="text-dim"><small>(opsional)</small></span></h3>
				</div>
				<textarea name="notes" maxlength="500" placeholder="Contoh: mohon faktur atas nama PT ..."><?php echo html_escape(set_value('notes', '', FALSE)); ?></textarea>
			</div>
		</div>

		<aside class="card">
			<div class="card-head">
				<h3>Ringkasan pembayaran</h3>
			</div>

			<div class="summary-row">
				<span>Jumlah item</span>
				<span><?php echo (int) $totals['count']; ?></span>
			</div>
			<div class="summary-row">
				<span>Subtotal</span>
				<span>Rp <?php echo rupiah($totals['subtotal'], FALSE); ?></span>
			</div>
			<div class="summary-row">
				<span>Biaya layanan</span>
				<span>Gratis</span>
			</div>
			<div class="summary-row total">
				<span>Total bayar</span>
				<span>Rp <?php echo rupiah($totals['total'], FALSE); ?></span>
			</div>

			<button type="submit" class="btn btn-primary btn-lg btn-block mt-2">Buat Pesanan</button>
			<p class="text-dim mt-1 mb-0"><small>Pesanan dibuat dengan status <strong>menunggu pembayaran</strong>. Akses unduh akan aktif setelah admin memverifikasi bukti pembayaran Anda.</small></p>

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
		</aside>
	</div>
<?php echo form_close(); ?>
