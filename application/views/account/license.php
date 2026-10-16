<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * License detail page.
 *
 * @var array $license
 * @var int   $remaining
 * @var array $order
 */
$thumb   = product_image_url($license['thumbnail_url']);
$preview = preview_media_url($license['preview_url']);
$revoked = (int) $license['is_revoked'] === 1;
$usable  = $this->license_model->is_downloadable($license);
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('account'); ?>">Akun</a>
	<span>/</span>
	<a href="<?php echo site_url('library'); ?>">Library</a>
	<span>/</span>
	<span><?php echo html_escape(excerpt($license['title'], 36)); ?></span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1"><?php echo html_escape($license['title']); ?></h2>
		<p class="text-muted mb-0">
			Pesanan <a class="mono" href="<?php echo site_url('account/order/'.$license['order_id']); ?>"><?php echo html_escape($license['order_number']); ?></a>
			&middot; v<?php echo html_escape($license['version']); ?>
		</p>
	</div>
	<span class="badge <?php echo $revoked ? 'badge-danger' : 'badge-success'; ?>"><?php echo $revoked ? 'Lisensi Dicabut' : 'Lisensi Aktif'; ?></span>
</div>

<div class="detail-layout">
	<div>
		<div class="card mb-2">
			<div class="card-head">
				<h3>Serial Number</h3>
				<button type="button" class="btn btn-ghost btn-sm" data-copy="<?php echo html_escape($license['license_key']); ?>">Salin</button>
			</div>

			<?php if ((int) $license['requires_license_key'] === 1): ?>
				<div class="license-key"><?php echo html_escape($license['license_key']); ?></div>
				<p class="text-dim mt-1 mb-0"><small>Simpan serial ini. Diperlukan saat aktivasi produk di perangkat Anda.</small></p>
			<?php else: ?>
				<p class="text-muted mb-0">Produk ini tidak memerlukan serial number. Cukup unduh dan pasang.</p>
			<?php endif; ?>
		</div>

		<div class="card mb-2">
			<div class="card-head"><h3>Kuota unduhan</h3></div>

			<div class="quota"><span style="width:<?php echo progress_percent($license['download_count'], $license['max_downloads']); ?>%"></span></div>
			<div class="quota-meta mb-2">
				<span><?php echo (int) $license['download_count']; ?> dari <?php echo (int) $license['max_downloads']; ?> unduhan terpakai</span>
				<span>sisa <?php echo (int) $remaining; ?></span>
			</div>

			<?php if ($usable): ?>
				<a class="btn btn-primary btn-lg btn-block" href="<?php echo site_url('download/'.$license['download_token']); ?>">Unduh <?php echo html_escape(file_size_label($license['file_size_mb'])); ?></a>
			<?php else: ?>
				<div class="alert alert-error mb-0">
					<span class="alert-icon">&#9888;</span>
					<span>
						<?php echo $revoked
							? 'Lisensi ini telah dicabut oleh admin. Hubungi support untuk bantuan.'
							: 'Kuota unduhan sudah habis. Hubungi admin untuk menambah kuota.'; ?>
					</span>
				</div>
			<?php endif; ?>

			<p class="text-dim mt-1 mb-0"><small>Unduhan terakhir: <?php echo datetime_id($license['last_downloaded_at']); ?></small></p>
		</div>

		<div class="card">
			<div class="card-head"><h3>Informasi produk</h3></div>
			<ul class="spec-list">
				<li><span class="spec-key">Format file</span><span class="spec-val"><?php echo html_escape($license['file_format']); ?></span></li>
				<li><span class="spec-key">Ukuran</span><span class="spec-val"><?php echo html_escape(file_size_label($license['file_size_mb'])); ?></span></li>
				<li><span class="spec-key">Versi</span><span class="spec-val"><?php echo html_escape($license['version']); ?></span></li>
				<li><span class="spec-key">Pembelian</span><span class="spec-val"><?php echo datetime_id($order['paid_at'] ?? $license['created_at']); ?></span></li>
			</ul>

			<?php if ( ! empty($license['system_requirements'])): ?>
				<hr>
				<span class="eyebrow">Kebutuhan sistem</span>
				<p class="text-muted mb-0"><?php echo nl2br(html_escape($license['system_requirements'])); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<aside>
		<?php if ($thumb): ?>
			<div class="detail-media mb-2">
				<img src="<?php echo html_escape($thumb); ?>" alt="<?php echo html_escape($license['title']); ?>">
			</div>
		<?php endif; ?>

		<?php if ($preview): ?>
			<div class="card">
				<div class="card-head"><h3><?php echo html_escape(preview_type_label($license['preview_type'])); ?></h3></div>
				<?php if ($license['preview_type'] === 'audio'): ?>
					<audio controls preload="none" src="<?php echo html_escape($preview); ?>"></audio>
				<?php elseif ($license['preview_type'] === 'video'): ?>
					<video controls preload="metadata" src="<?php echo html_escape($preview); ?>"></video>
				<?php elseif ($license['preview_type'] === 'image'): ?>
					<img src="<?php echo html_escape($preview); ?>" alt="">
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="card">
			<div class="card-head"><h3>Keamanan tautan</h3></div>
			<p class="text-muted"><small>Setiap unduhan memakai token pribadi <span class="mono"><?php echo html_escape(substr($license['download_token'], 0, 14)); ?>&hellip;</span> yang hanya berlaku untuk akun Anda dan tercatat di log.</small></p>
			<a class="btn btn-ghost btn-sm btn-block" href="<?php echo site_url('product/'.$license['slug']); ?>">Lihat halaman produk</a>
		</div>
	</aside>
</div>
