<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Digital library: every purchased license with its download button.
 *
 * @var array  $licenses
 * @var string $pagination
 * @var int    $total
 */
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('account'); ?>">Akun</a>
	<span>/</span>
	<span>Digital Library</span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1">Digital Library</h2>
		<p class="text-muted mb-0"><?php echo (int) $total; ?> produk terlisensi. Serial number dan kuota unduh Anda tercatat di sini.</p>
	</div>
	<a class="btn btn-ghost" href="<?php echo site_url('account/orders'); ?>">Riwayat Pesanan</a>
</div>

<?php if (empty($licenses)): ?>
	<div class="empty-state">
		<div class="empty-icon">&#128230;</div>
		<h3>Library Anda masih kosong</h3>
		<p>Setiap produk yang pembayarannya sudah diverifikasi akan muncul di sini lengkap dengan tombol unduh.</p>
		<a class="btn btn-primary" href="<?php echo site_url('products'); ?>">Mulai belanja</a>
	</div>
<?php else: ?>
	<div class="grid grid-3">
		<?php foreach ($licenses as $license): ?>
			<?php
			$thumb     = product_image_url($license['thumbnail_url']);
			$remaining = $this->license_model->remaining_downloads($license);
			$revoked   = (int) $license['is_revoked'] === 1;
			$usable    = $this->license_model->is_downloadable($license);
			?>
			<article class="card library-card">
				<div class="row-between">
					<span class="badge <?php echo $revoked ? 'badge-danger' : 'badge-success'; ?>">
						<?php echo $revoked ? 'Dicabut' : 'Aktif'; ?>
					</span>
					<span class="badge badge-muted"><?php echo html_escape($license['category_name']); ?></span>
				</div>

				<div class="cart-line-thumb" style="width:100%;height:96px">
					<?php if ($thumb): ?>
						<img src="<?php echo html_escape($thumb); ?>" alt="">
					<?php else: ?>
						<?php echo html_escape(initials_of($license['title'])); ?>
					<?php endif; ?>
				</div>

				<div>
					<h3 class="card-title"><a href="<?php echo site_url('account/license/'.$license['id']); ?>"><?php echo html_escape($license['title']); ?></a></h3>
					<div class="cart-line-meta text-dim">
						v<?php echo html_escape($license['version']); ?> &middot; <?php echo html_escape($license['file_format']); ?> &middot; <?php echo html_escape(file_size_label($license['file_size_mb'])); ?>
					</div>
				</div>

				<div>
					<div class="license-key"><?php echo html_escape($license['license_key']); ?></div>
				</div>

				<div>
					<div class="quota"><span style="width:<?php echo progress_percent($license['download_count'], $license['max_downloads']); ?>%"></span></div>
					<div class="quota-meta">
						<span><?php echo (int) $license['download_count']; ?> dari <?php echo (int) $license['max_downloads']; ?> unduhan</span>
						<span>sisa <?php echo (int) $remaining; ?></span>
					</div>
				</div>

				<div class="product-actions">
					<?php if ($usable): ?>
						<a class="btn btn-primary btn-sm" href="<?php echo site_url('download/'.$license['download_token']); ?>">Unduh Sekarang</a>
					<?php else: ?>
						<span class="btn btn-ghost btn-sm is-disabled">Tidak Tersedia</span>
					<?php endif; ?>
					<a class="btn btn-ghost btn-sm" href="<?php echo site_url('account/license/'.$license['id']); ?>">Detail</a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php echo $pagination; ?>
<?php endif; ?>
