<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product detail page.
 *
 * @var array $product
 * @var array $related
 * @var array $cart_ids
 * @var array $spec_rows
 * @var bool  $owned
 */
$image    = product_image_url($product['thumbnail_url']);
$preview  = preview_media_url($product['preview_url']);
$in_cart  = in_array((int) $product['id'], $cart_ids, TRUE);
$audience = isset($product['target_group']) ? $product['target_group'] : 'both';
?>

<div class="breadcrumb">
	<a href="<?php echo site_url('/'); ?>">Beranda</a>
	<span>/</span>
	<a href="<?php echo site_url('products'); ?>">Produk</a>
	<span>/</span>
	<a href="<?php echo site_url('category/'.$product['category_slug']); ?>"><?php echo html_escape($product['category_name']); ?></a>
	<span>/</span>
	<span><?php echo html_escape(excerpt($product['title'], 40)); ?></span>
</div>

<div class="detail-layout">
	<div>
		<div class="detail-media">
			<?php if ($image): ?>
				<img src="<?php echo html_escape($image); ?>" alt="<?php echo html_escape($product['title']); ?>">
			<?php else: ?>
				<span class="thumb-fallback mono" style="font-size:2.4rem"><?php echo html_escape(initials_of($product['title'])); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( ! empty($product['thumbnail_credit'])): ?>
			<p class="text-dim mt-1"><small><?php echo $product['thumbnail_credit']; ?></small></p>
		<?php endif; ?>

		<div class="row-wrap row mt-2 mb-3">
			<span class="badge badge-accent"><?php echo html_escape(target_group_label($audience)); ?></span>
			<span class="badge badge-muted">v<?php echo html_escape($product['version']); ?></span>
			<span class="badge badge-muted"><?php echo html_escape($product['file_format']); ?></span>
			<span class="badge <?php echo ((int) $product['requires_license_key'] === 1) ? 'badge-warning' : 'badge-success'; ?>">
				<?php echo ((int) $product['requires_license_key'] === 1) ? 'Butuh License Key' : 'Tanpa License Key'; ?>
			</span>
		</div>

		<section class="section">
			<h2>Deskripsi Produk</h2>
			<div class="description-body">
				<p><?php echo nl2br(html_escape($product['description'])); ?></p>
			</div>
		</section>

		<?php if ($preview): ?>
			<section class="section">
				<h2><?php echo html_escape(preview_type_label($product['preview_type'])); ?></h2>
				<div class="panel preview-box">
					<?php if ($product['preview_type'] === 'audio'): ?>
						<audio controls preload="none" src="<?php echo html_escape($preview); ?>"></audio>
					<?php elseif ($product['preview_type'] === 'video'): ?>
						<video controls preload="metadata" src="<?php echo html_escape($preview); ?>"></video>
					<?php elseif ($product['preview_type'] === 'image'): ?>
						<img src="<?php echo html_escape($preview); ?>" alt="<?php echo html_escape($product['title']); ?>">
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="section">
			<h2>Spesifikasi Teknis</h2>
			<div class="card">
				<ul class="spec-list">
					<?php foreach ($spec_rows as $label => $value): ?>
						<li>
							<span class="spec-key"><?php echo html_escape($label); ?></span>
							<span class="spec-val"><?php echo html_escape($value); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<?php if ( ! empty($product['system_requirements'])): ?>
			<section class="section">
				<h2>Kebutuhan Sistem</h2>
				<div class="panel">
					<p class="mb-0 text-muted"><?php echo nl2br(html_escape($product['system_requirements'])); ?></p>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<aside>
		<div class="card buy-box">
			<span class="product-cat"><?php echo html_escape($product['category_name']); ?></span>
			<h1 style="font-size:1.35rem"><?php echo html_escape($product['title']); ?></h1>
			<p class="text-muted"><small><?php echo html_escape($product['short_description']); ?></small></p>

			<div class="buy-price"><span class="currency">Rp</span><?php echo rupiah($product['price'], FALSE); ?></div>

			<ul class="buy-list">
				<li>Unduhan langsung dari Digital Library</li>
				<li>Kuota unduh <?php echo (int) $this->config->item('default_max_downloads'); ?>&times; per lisensi</li>
				<li>
					<?php echo ((int) $product['requires_license_key'] === 1)
						? 'Serial number eksklusif per pembeli'
						: 'Tanpa serial number, langsung pakai'; ?>
				</li>
				<li>Update versi gratis untuk pembeli</li>
			</ul>

			<?php if ($owned): ?>
				<div class="alert alert-success mb-2">
					<span class="alert-icon">&#10004;</span>
					<span>Anda sudah memiliki produk ini.</span>
				</div>
				<a class="btn btn-primary btn-block" href="<?php echo site_url('library'); ?>">Buka di Library</a>
			<?php elseif ($in_cart): ?>
				<a class="btn btn-primary btn-block" href="<?php echo site_url('cart'); ?>">Lanjut ke Keranjang</a>
				<a class="btn btn-ghost btn-block mt-1" href="<?php echo site_url('products'); ?>">Lanjut belanja</a>
			<?php else: ?>
				<?php echo form_open('cart/add/'.$product['id']); ?>
					<button type="submit" class="btn btn-primary btn-lg btn-block">Tambah ke Keranjang</button>
				<?php echo form_close(); ?>
				<a class="btn btn-ghost btn-block mt-1" href="<?php echo site_url('products'); ?>">Lanjut belanja</a>
			<?php endif; ?>

			<hr>

			<ul class="spec-list">
				<li><span class="spec-key">Ukuran</span><span class="spec-val"><?php echo html_escape(file_size_label($product['file_size_mb'])); ?></span></li>
				<li><span class="spec-key">Sistem</span><span class="spec-val"><?php echo html_escape(os_label($product['os_support'])); ?></span></li>
				<li><span class="spec-key">Format</span><span class="spec-val"><?php echo html_escape($product['file_format']); ?></span></li>
			</ul>
		</div>
	</aside>
</div>

<?php if ( ! empty($related)): ?>
	<section class="section mt-3">
		<div class="section-head">
			<div>
				<h2>Produk terkait</h2>
				<p>Lainnya dari kategori <?php echo html_escape($product['category_name']); ?>.</p>
			</div>
		</div>

		<div class="grid grid-3">
			<?php
			foreach ($related as $item)
			{
				$this->load->view('partials/product_card', array('product' => $item, 'cart_ids' => $cart_ids));
			}
			?>
		</div>
	</section>
<?php endif; ?>
