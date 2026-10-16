<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin product listing.
 *
 * @var array  $products
 * @var array  $filters
 * @var array  $categories
 * @var int    $total
 * @var string $pagination
 */
?>
<div class="card">
	<div class="row-between">
		<form action="<?php echo site_url('admin/products'); ?>" method="get" class="row row-wrap">
			<input type="text" name="q" placeholder="Cari judul produk&hellip;" value="<?php echo html_escape($filters['q']); ?>" style="width:240px">
			<select name="category_id" style="width:auto">
				<option value="0">Semua kategori</option>
				<?php foreach ($categories as $category): ?>
					<option value="<?php echo (int) $category['id']; ?>" <?php echo (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>><?php echo html_escape($category['name']); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="status" style="width:auto">
				<option value="">Semua status</option>
				<option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Aktif</option>
				<option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Nonaktif</option>
			</select>
			<select name="featured" style="width:auto">
				<option value="0">Semua</option>
				<option value="1" <?php echo (int) $filters['featured'] === 1 ? 'selected' : ''; ?>>Hanya unggulan</option>
			</select>
			<button type="submit" class="btn btn-ghost btn-sm">Filter</button>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/products'); ?>">Reset</a>
		</form>

		<a class="btn btn-primary" href="<?php echo site_url('admin/products/create'); ?>">+ Produk Baru</a>
	</div>
</div>

<div class="card">
	<div class="card-head">
		<h3><?php echo (int) $total; ?> produk</h3>
		<span class="badge badge-muted">Halaman ini: <?php echo count($products); ?></span>
	</div>

	<?php if (empty($products)): ?>
		<div class="empty-state">
			<div class="empty-icon">&#9834;</div>
			<h3>Tidak ada produk</h3>
			<p>Ubah filter di atas, atau tambahkan produk baru ke katalog.</p>
			<a class="btn btn-primary" href="<?php echo site_url('admin/products/create'); ?>">+ Produk Baru</a>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th>Gambar</th>
						<th>Produk</th>
						<th>Kategori</th>
						<th>Harga</th>
						<th>Status</th>
						<th>Unggulan</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($products as $product): ?>
						<?php $image = product_image_url($product['thumbnail_url']); ?>
						<tr>
							<td>
								<div class="cart-line-thumb" style="width:64px;height:44px">
									<?php if ($image): ?>
										<img src="<?php echo html_escape($image); ?>" alt="">
									<?php else: ?>
										<?php echo html_escape(initials_of($product['title'])); ?>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<div class="cell-title"><a href="<?php echo site_url('admin/products/edit/'.$product['id']); ?>"><?php echo html_escape(excerpt($product['title'], 46)); ?></a></div>
								<div class="cell-sub mono"><?php echo html_escape($product['slug']); ?></div>
							</td>
							<td><?php echo html_escape($product['category_name']); ?></td>
							<td class="nowrap">Rp <?php echo rupiah($product['price'], FALSE); ?></td>
							<td>
								<?php echo (int) $product['is_active'] === 1
									? '<span class="badge badge-success">Aktif</span>'
									: '<span class="badge badge-muted">Nonaktif</span>'; ?>
							</td>
							<td>
								<?php echo (int) $product['is_featured'] === 1
									? '<span class="badge badge-warning">Ya</span>'
									: '<span class="badge badge-muted">Tidak</span>'; ?>
							</td>
							<td class="actions">
								<?php echo form_open('admin/products/toggle/'.$product['id'].'/is_active', array('class' => 'inline-form')); ?>
									<button type="submit" class="btn btn-ghost btn-sm" title="Aktif / nonaktif">
										<?php echo (int) $product['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?>
									</button>
								<?php echo form_close(); ?>

								<?php echo form_open('admin/products/toggle/'.$product['id'].'/is_featured', array('class' => 'inline-form')); ?>
									<button type="submit" class="btn btn-ghost btn-sm" title="Tandai unggulan">
										<?php echo (int) $product['is_featured'] === 1 ? 'Batal unggulan' : 'Unggulan'; ?>
									</button>
								<?php echo form_close(); ?>

								<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/products/edit/'.$product['id']); ?>">Edit</a>

								<?php echo form_open('admin/products/delete/'.$product['id'], array('class' => 'inline-form')); ?>
									<button type="submit" class="btn btn-danger btn-sm" data-confirm="Hapus produk ini secara permanen?">Hapus</button>
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
