<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shopping cart.
 *
 * @var array $lines
 * @var array $totals
 */
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('/'); ?>">Beranda</a>
	<span>/</span>
	<span>Keranjang</span>
</div>

<h2 class="mb-3">Keranjang Belanja</h2>

<?php if (empty($lines)): ?>
	<div class="empty-state">
		<div class="empty-icon">&#128722;</div>
		<h3>Keranjang masih kosong</h3>
		<p>Jelajahi katalog plugin, preset, dan asset pack untuk menemukan yang Anda butuhkan.</p>
		<a class="btn btn-primary" href="<?php echo site_url('products'); ?>">Mulai belanja</a>
	</div>
<?php else: ?>
	<div class="cart-layout">
		<div class="card">
			<div class="card-head">
				<h3><?php echo (int) $totals['count']; ?> produk dalam keranjang</h3>
				<?php echo form_open('cart/clear', array('class' => 'inline-form')); ?>
					<button type="submit" class="btn btn-danger btn-sm" data-confirm="Kosongkan seluruh keranjang?">Kosongkan</button>
				<?php echo form_close(); ?>
			</div>

			<?php foreach ($lines as $line): ?>
				<?php $thumb = product_image_url($line['thumbnail_url']); ?>
				<div class="cart-line">
					<div class="cart-line-thumb">
						<?php if ($thumb): ?>
							<img src="<?php echo html_escape($thumb); ?>" alt="">
						<?php else: ?>
							<?php echo html_escape(initials_of($line['title'])); ?>
						<?php endif; ?>
					</div>

					<div class="cart-line-body">
						<h4><a href="<?php echo site_url('product/'.$line['slug']); ?>"><?php echo html_escape($line['title']); ?></a></h4>
						<div class="cart-line-meta">
							v<?php echo html_escape($line['version']); ?> &middot; <?php echo html_escape($line['file_format']); ?>
						</div>
						<?php if ( ! empty($line['owned'])): ?>
							<span class="badge badge-warning mt-1">Sudah Anda miliki</span>
						<?php endif; ?>
					</div>

					<div class="cart-line-price">Rp <?php echo rupiah($line['price'], FALSE); ?></div>

					<?php echo form_open('cart/remove/'.$line['product_id'], array('class' => 'inline-form')); ?>
						<button type="submit" class="btn btn-ghost btn-sm" title="Hapus dari keranjang">&times;</button>
					<?php echo form_close(); ?>
				</div>
			<?php endforeach; ?>

			<div class="mt-2">
				<a class="btn btn-ghost btn-sm" href="<?php echo site_url('products'); ?>">&larr; Lanjut belanja</a>
			</div>
		</div>

		<aside class="card">
			<div class="card-head">
				<h3>Ringkasan</h3>
			</div>

			<div class="summary-row">
				<span>Jumlah item</span>
				<span><?php echo (int) $totals['count']; ?></span>
			</div>
			<div class="summary-row">
				<span>Subtotal</span>
				<span>Rp <?php echo rupiah($totals['subtotal'], FALSE); ?></span>
			</div>
			<div class="summary-row total">
				<span>Total</span>
				<span>Rp <?php echo rupiah($totals['total'], FALSE); ?></span>
			</div>

			<?php if ($is_logged_in): ?>
				<a class="btn btn-primary btn-lg btn-block mt-2" href="<?php echo site_url('checkout'); ?>">Lanjut ke Pembayaran</a>
			<?php else: ?>
				<a class="btn btn-primary btn-lg btn-block mt-2" href="<?php echo site_url('login'); ?>">Masuk untuk Checkout</a>
				<p class="text-dim mt-1 mb-0"><small>Keranjang Anda tersimpan otomatis setelah masuk.</small></p>
			<?php endif; ?>

			<hr>
			<p class="text-dim mb-0"><small>Semua produk bersifat digital. Setelah pembayaran diverifikasi admin, produk langsung tersedia di Digital Library Anda tanpa biaya kirim.</small></p>
		</aside>
	</div>
<?php endif; ?>
