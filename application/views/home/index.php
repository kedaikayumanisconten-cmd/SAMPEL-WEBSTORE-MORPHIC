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
	<span class="eyebrow"><?php echo html_escape($hero_eyebrow); ?></span>
	<h1><?php echo html_escape($hero_title); ?></h1>
	<p><?php echo nl2br(html_escape($hero_description)); ?></p>

	<div class="hero-actions">
		<a class="btn btn-primary btn-lg" href="<?php echo site_url('products'); ?>">Jelajahi Katalog</a>
		<?php if ( ! $is_logged_in): ?>
			<a class="btn btn-ghost btn-lg" href="<?php echo site_url('register'); ?>">Daftar Gratis</a>
		<?php else: ?>
			<a class="btn btn-ghost btn-lg" href="<?php echo site_url('library'); ?>">Buka Library Saya</a>
		<?php endif; ?>
		<a class="btn btn-wa btn-lg" href="<?php echo html_escape(whatsapp_link('Halo '.$store_name.', saya ingin bertanya tentang produk digital.')); ?>" target="_blank" rel="noopener">
			<span class="btn-wa-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>
			Chat Owner
		</a>
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

<?php if (trim((string) $about_description) !== ''): ?>
	<section class="section">
		<div class="section-head">
			<div>
				<h2><?php echo html_escape($about_title); ?></h2>
			</div>
		</div>

		<div class="card">
			<p class="text-muted mb-0"><?php echo nl2br(html_escape($about_description)); ?></p>
		</div>
	</section>
<?php endif; ?>
