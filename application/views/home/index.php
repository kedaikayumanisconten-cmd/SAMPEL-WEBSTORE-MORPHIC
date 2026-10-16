<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront landing page.
 *
 * @var array $featured
 * @var array $latest
 * @var array $slider
 * @var array $categories
 * @var array $cart_ids
 * @var array $hero_stats
 */
?>
<?php if ( ! empty($slider)): ?>
	<section class="showcase" data-slider aria-label="Cuplikan produk">
		<div class="showcase-bar">
			<span class="eyebrow mb-0">Sedang tren di M.O.R.P.H.I.C</span>
			<div class="slider-controls">
				<button type="button" class="slider-nav" data-slider-prev aria-label="Produk sebelumnya">&#8249;</button>
				<button type="button" class="slider-nav" data-slider-next aria-label="Produk berikutnya">&#8250;</button>
			</div>
		</div>

		<div class="media-slider">
			<div class="slider-viewport" data-slider-track tabindex="0" aria-label="Slider cuplikan produk">
				<?php foreach ($slider as $product): ?>
					<?php $image = product_image_url($product['thumbnail_url']); ?>
					<a class="slider-slide" href="<?php echo site_url('product/'.$product['slug']); ?>">
						<div class="slider-media">
							<?php if ($image): ?>
								<img src="<?php echo html_escape($image); ?>" alt="<?php echo html_escape($product['title']); ?>" loading="lazy">
							<?php else: ?>
								<span class="thumb-fallback"><?php echo html_escape(initials_of($product['title'])); ?></span>
							<?php endif; ?>
						</div>

						<span class="slider-overlay"></span>

						<span class="badge badge-accent slider-tag"><?php echo html_escape($product['category_name']); ?></span>
						<span class="slider-price"><span class="currency">Rp</span><?php echo rupiah($product['price'], FALSE); ?></span>

						<div class="slider-caption">
							<h3 class="slider-title"><?php echo html_escape($product['title']); ?></h3>
							<span class="slider-audience"><?php echo html_escape(target_group_label($product['target_group'])); ?> &middot; v<?php echo html_escape($product['version']); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="slider-progress" aria-hidden="true">
				<span data-slider-progress></span>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="hero">
	<span class="eyebrow">M.O.R.P.H.I.C Marketplace</span>
	<h1>Semua alat produksi digital untuk kreator &amp; musisi, dalam satu lisensi.</h1>
	<p>
		VST instrument, preset synth, paket transisi video, sampai LUT color grading sinematik.
		Setiap pembelian otomatis masuk ke <strong>Digital Library</strong> dengan serial number dan kuota unduh yang terlacak.
	</p>

	<div class="hero-actions">
		<a class="btn btn-primary btn-lg" href="<?php echo site_url('products'); ?>">Jelajahi Katalog</a>
		<?php if ( ! $is_logged_in): ?>
			<a class="btn btn-ghost btn-lg" href="<?php echo site_url('register'); ?>">Daftar Gratis</a>
		<?php else: ?>
			<a class="btn btn-ghost btn-lg" href="<?php echo site_url('library'); ?>">Buka Library Saya</a>
		<?php endif; ?>
	</div>

	<div class="hero-stats">
		<div>
			<strong><?php echo (int) $hero_stats['products']; ?></strong>
			<span>Produk digital aktif</span>
		</div>
		<div>
			<strong><?php echo (int) $hero_stats['licenses']; ?></strong>
			<span>Lisensi diterbitkan</span>
		</div>
		<div>
			<strong><?php echo (int) $hero_stats['downloads']; ?></strong>
			<span>Total unduhan terverifikasi</span>
		</div>
		<div>
			<strong>100%</strong>
			<span>Pembayaran diverifikasi manual</span>
		</div>
	</div>
</section>

<?php if ( ! empty($categories)): ?>
	<section class="section">
		<div class="section-head">
			<div>
				<h2>Belanja sesuai kebutuhan Anda</h2>
				<p>Pilih jalur kreator konten atau musisi &amp; produser.</p>
			</div>
		</div>

		<div class="grid grid-3">
			<?php foreach ($categories as $category): ?>
				<?php $cover = category_image_url($category['image_url']); ?>
				<a class="card category-card" href="<?php echo site_url('category/'.$category['slug']); ?>">
					<?php if ($cover): ?>
						<div class="category-cover">
							<img src="<?php echo html_escape($cover); ?>" alt="<?php echo html_escape($category['name']); ?>" loading="lazy">
							<span class="badge badge-success thumb-audience"><?php echo (int) $category['product_count']; ?> produk</span>
						</div>
					<?php endif; ?>

					<div class="category-body">
						<div class="row-between mb-1">
							<span class="badge <?php echo $category['target_group'] === 'musician' ? 'badge-accent' : ($category['target_group'] === 'creator' ? 'badge-success' : 'badge-muted'); ?>">
								<?php echo html_escape(target_group_label($category['target_group'])); ?>
							</span>
							<?php if ( ! $cover): ?>
								<span class="badge badge-muted"><?php echo (int) $category['product_count']; ?> produk</span>
							<?php endif; ?>
						</div>
						<h3 class="card-title"><?php echo html_escape($category['name']); ?></h3>
						<p class="text-muted mb-0"><small><?php echo html_escape(excerpt($category['description'], 110)); ?></small></p>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty($featured)): ?>
	<section class="section">
		<div class="section-head">
			<div>
				<h2>Produk unggulan</h2>
				<p>Paling sering dibeli oleh kreator dan produser.</p>
			</div>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('products?sort=featured'); ?>">Lihat semua</a>
		</div>

		<div class="grid grid-4">
			<?php
			foreach ($featured as $product)
			{
				$this->load->view('partials/product_card', array('product' => $product, 'cart_ids' => $cart_ids));
			}
			?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty($latest)): ?>
	<section class="section">
		<div class="section-head">
			<div>
				<h2>Rilisan terbaru</h2>
				<p>Update plugin dan asset pack terbaru di katalog.</p>
			</div>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('products'); ?>">Semua produk</a>
		</div>

		<div class="grid grid-3">
			<?php
			foreach ($latest as $product)
			{
				$this->load->view('partials/product_card', array('product' => $product, 'cart_ids' => $cart_ids));
			}
			?>
		</div>
	</section>
<?php endif; ?>

<?php if (empty($featured) && empty($latest)): ?>
	<div class="empty-state">
		<div class="empty-icon">&#9834;</div>
		<h3>Katalog masih kosong</h3>
		<p>Tambahkan produk pertama Anda melalui panel admin untuk mulai berjualan.</p>
		<a class="btn btn-primary" href="<?php echo site_url('admin/products'); ?>">Tambah Produk</a>
	</div>
<?php endif; ?>

<section class="section">
	<div class="panel panel-accent">
		<div class="grid grid-3">
			<div>
				<h3 class="card-title">1. Pilih produk</h3>
				<p class="text-muted mb-0"><small>Masukkan plugin atau asset ke keranjang, lalu lanjutkan ke pembayaran.</small></p>
			</div>
			<div>
				<h3 class="card-title">2. Bayar &amp; unggah bukti</h3>
				<p class="text-muted mb-0"><small>Transfer bank, virtual account, atau QRIS. Unggah bukti pembayaran dari halaman pesanan.</small></p>
			</div>
			<div>
				<h3 class="card-title">3. Unduh dari Library</h3>
				<p class="text-muted mb-0"><small>Setelah diverifikasi, serial number dan tombol unduh langsung muncul di Digital Library Anda.</small></p>
			</div>
		</div>
	</div>
</section>
