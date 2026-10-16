<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin category listing.
 *
 * @var array $categories
 */
?>
<div class="card">
	<div class="row-between">
		<div>
			<h3 class="mb-1">Kategori Katalog</h3>
			<p class="text-muted mb-0"><small>Kategori menentukan pengelompokan produk dan target audiens di storefront.</small></p>
		</div>
		<a class="btn btn-primary" href="<?php echo site_url('admin/categories/create'); ?>">+ Kategori Baru</a>
	</div>
</div>

<div class="card">
	<?php if (empty($categories)): ?>
		<div class="empty-state">
			<div class="empty-icon">&#9776;</div>
			<h3>Belum ada kategori</h3>
			<p>Buat kategori pertama untuk mulai mengelompokkan produk.</p>
			<a class="btn btn-primary" href="<?php echo site_url('admin/categories/create'); ?>">+ Kategori Baru</a>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th></th>
						<th>Nama</th>
						<th>Slug</th>
						<th>Target Audiens</th>
						<th>Produk</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($categories as $category): ?>
						<?php $image = category_image_url($category['image_url']); ?>
						<tr>
							<td>
								<div class="cart-line-thumb" style="width:64px;height:44px">
									<?php if ($image): ?>
										<img src="<?php echo html_escape($image); ?>" alt="">
									<?php else: ?>
										<?php echo html_escape(initials_of($category['name'])); ?>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<div class="cell-title"><?php echo html_escape($category['name']); ?></div>
								<div class="cell-sub"><?php echo html_escape(excerpt($category['description'], 70)); ?></div>
							</td>
							<td class="mono"><?php echo html_escape($category['slug']); ?></td>
							<td><span class="badge badge-accent"><?php echo html_escape(target_group_label($category['target_group'])); ?></span></td>
							<td><?php echo (int) $category['product_count']; ?></td>
							<td>
								<?php echo (int) $category['is_active'] === 1
									? '<span class="badge badge-success">Aktif</span>'
									: '<span class="badge badge-muted">Nonaktif</span>'; ?>
							</td>
							<td class="actions">
								<a class="btn btn-ghost btn-sm" href="<?php echo site_url('category/'.$category['slug']); ?>" target="_blank" rel="noopener">Lihat &#8599;</a>
								<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/categories/edit/'.$category['id']); ?>">Edit</a>
								<?php echo form_open('admin/categories/delete/'.$category['id'], array('class' => 'inline-form')); ?>
									<button type="submit" class="btn btn-danger btn-sm" data-confirm="Hapus kategori ini?">Hapus</button>
								<?php echo form_close(); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
