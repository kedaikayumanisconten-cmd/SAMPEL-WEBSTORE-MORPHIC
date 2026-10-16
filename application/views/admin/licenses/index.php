<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin license listing.
 *
 * @var array  $licenses
 * @var array  $filters
 * @var array  $stats
 * @var int    $total
 * @var string $pagination
 */
?>
<div class="stat-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Total lisensi</div>
		<div class="stat-value"><?php echo (int) $stats['total']; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Lisensi aktif</div>
		<div class="stat-value"><?php echo (int) $stats['active']; ?></div>
		<div class="stat-foot"><?php echo (int) $stats['total'] - (int) $stats['active']; ?> dicabut</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Total unduhan</div>
		<div class="stat-value"><?php echo (int) $stats['downloads']; ?></div>
		<div class="stat-foot">Tercatat melalui token unduhan</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Halaman</div>
		<div class="stat-value"><?php echo count($licenses); ?></div>
		<div class="stat-foot">dari <?php echo (int) $total; ?> lisensi</div>
	</div>
</div>

<div class="card">
	<form action="<?php echo site_url('admin/licenses'); ?>" method="get" class="row row-wrap">
		<input type="text" name="q" placeholder="Cari serial / produk / email / nomor pesanan&hellip;" value="<?php echo html_escape($filters['q']); ?>" style="width:320px">
		<select name="status" style="width:auto">
			<option value="">Semua status</option>
			<option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Aktif</option>
			<option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Dicabut</option>
		</select>
		<button type="submit" class="btn btn-ghost btn-sm">Filter</button>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/licenses'); ?>">Reset</a>
	</form>
</div>

<div class="card">
	<div class="card-head">
		<h3><?php echo (int) $total; ?> lisensi</h3>
		<span class="badge badge-muted">Halaman ini: <?php echo count($licenses); ?></span>
	</div>

	<?php if (empty($licenses)): ?>
		<div class="empty-state">
			<div class="empty-icon">&#128273;</div>
			<h3>Tidak ada lisensi</h3>
			<p>Lisensi dibuat otomatis saat admin menandai sebuah pesanan sebagai lunas.</p>
			<a class="btn btn-ghost" href="<?php echo site_url('admin/orders?status=pending'); ?>">Lihat pesanan menunggu</a>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th>Serial Number</th>
						<th>Produk</th>
						<th>Pembeli</th>
						<th>Pesanan</th>
						<th>Unduhan</th>
						<th>Terakhir</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($licenses as $license): ?>
						<?php
						$revoked   = (int) $license['is_revoked'] === 1;
						$remaining = max(0, (int) $license['max_downloads'] - (int) $license['download_count']);
						?>
						<tr>
							<td>
								<div class="cell-title mono"><?php echo html_escape($license['license_key']); ?></div>
								<div class="cell-sub mono">&#8226;&#8226;&#8226;&#8226;<?php echo html_escape(substr($license['download_token'], -8)); ?></div>
							</td>
							<td>
								<div class="cell-title"><?php echo html_escape(excerpt($license['title'], 38)); ?></div>
								<div class="cell-sub">#<?php echo (int) $license['product_id']; ?> &middot; <?php echo html_escape($license['file_format']); ?></div>
							</td>
							<td>
								<div class="cell-title"><?php echo html_escape($license['full_name']); ?></div>
								<div class="cell-sub"><?php echo html_escape($license['email']); ?></div>
							</td>
							<td class="mono">
								<a href="<?php echo site_url('admin/orders/detail/'.$license['order_id']); ?>"><?php echo html_escape($license['order_number']); ?></a>
							</td>
							<td>
								<div class="quota"><span style="width:<?php echo progress_percent($license['download_count'], $license['max_downloads']); ?>%"></span></div>
								<div class="quota-meta">
									<span><?php echo (int) $license['download_count']; ?>/<?php echo (int) $license['max_downloads']; ?></span>
									<span>sisa <?php echo (int) $remaining; ?></span>
								</div>
							</td>
							<td class="nowrap">
								<?php echo $license['last_downloaded_at'] ? datetime_id($license['last_downloaded_at'], FALSE) : '<span class="text-dim">Belum pernah</span>'; ?>
							</td>
							<td>
								<?php echo $revoked
									? '<span class="badge badge-danger">Dicabut</span>'
									: '<span class="badge badge-success">Aktif</span>'; ?>
							</td>
							<td class="actions">
								<?php echo form_open('admin/licenses/update/'.$license['id'], array('class' => 'inline-form row')); ?>
									<input type="number" name="max_downloads" value="<?php echo (int) $license['max_downloads']; ?>" min="0" max="999" style="width:72px" title="Kuota unduhan">
									<input type="hidden" name="is_revoked" value="<?php echo $revoked ? '1' : '0'; ?>">
									<button type="submit" class="btn btn-ghost btn-sm">Simpan</button>
								<?php echo form_close(); ?>

								<?php echo form_open('admin/licenses/update/'.$license['id'], array('class' => 'inline-form')); ?>
									<input type="hidden" name="max_downloads" value="<?php echo (int) $license['max_downloads']; ?>">
									<input type="hidden" name="is_revoked" value="<?php echo $revoked ? '0' : '1'; ?>">
									<button type="submit" class="btn <?php echo $revoked ? 'btn-success' : 'btn-danger'; ?> btn-sm" data-confirm="<?php echo $revoked ? 'Aktifkan kembali lisensi ini?' : 'Cabut lisensi ini? Pelanggan tidak akan bisa mengunduh lagi.'; ?>">
										<?php echo $revoked ? 'Pulihkan' : 'Cabut'; ?>
									</button>
								<?php echo form_close(); ?>

								<?php echo form_open('admin/licenses/regenerate/'.$license['id'], array('class' => 'inline-form')); ?>
									<button type="submit" class="btn btn-ghost btn-sm" title="Buat token unduhan baru" data-confirm="Buat token unduhan baru? Tautan unduhan lama tidak berlaku lagi.">Token Baru</button>
								<?php echo form_close(); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php echo $pagination; ?>
	<?php endif; ?>
</div>
