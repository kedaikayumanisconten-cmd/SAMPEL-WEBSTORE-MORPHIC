<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog listing.
 *
 * @var array  $products
 * @var array  $query_params
 * @var array  $categories
 * @var array  $cart_ids
 * @var array  $filters
 * @var array|null $active_category
 * @var int    $total_products
 * @var string $pagination
 * @var string $page_heading
 * @var string $page_subheading
 */
$build_url = function (array $overrides = array()) use ($query_params) {
	$params = array_merge($query_params, $overrides);

	foreach ($params as $key => $value)
	{
		if ($value === '' || $value === NULL || ($key === 'sort' && $value === 'newest'))
		{
			unset($params[$key]);
		}
	}

	$query = http_build_query($params);

	return site_url('products').($query !== '' ? '?'.$query : '');
};

$groups = array(
	''         => 'Semua Audiens',
	'creator'  => 'Kreator Konten',
	'musician' => 'Musisi & Produser',
);

$systems = array(
	''               => 'Semua Sistem',
	'windows'        => 'Windows',
	'macos'          => 'macOS',
	'cross_platform' => 'Cross Platform',
);

$sorts = array(
	'newest'     => 'Terbaru',
	'featured'   => 'Unggulan',
	'price_asc'  => 'Harga terendah',
	'price_desc' => 'Harga tertinggi',
	'name'       => 'Nama A-Z',
);
?>

<div class="breadcrumb">
	<a href="<?php echo site_url('/'); ?>">Beranda</a>
	<span>/</span>
	<span><?php echo html_escape($page_heading); ?></span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1"><?php echo html_escape($page_heading); ?></h2>
		<p class="text-muted mb-0"><?php echo html_escape($page_subheading); ?></p>
	</div>
</div>

<div class="shop-layout">
	<aside class="card filter-panel">
		<div class="card-head">
			<h3>Filter</h3>
			<?php if (array_filter($query_params)): ?>
				<a class="text-dim" href="<?php echo site_url('products'); ?>"><small>Reset</small></a>
			<?php endif; ?>
		</div>

		<form action="<?php echo site_url('products'); ?>" method="get" class="mb-2">
			<input type="hidden" name="category" value="<?php echo html_escape($query_params['category']); ?>">
			<input type="hidden" name="group" value="<?php echo html_escape($query_params['group']); ?>">
			<input type="hidden" name="os" value="<?php echo html_escape($query_params['os']); ?>">
			<input type="text" name="q" placeholder="Cari kata kunci&hellip;" value="<?php echo html_escape($query_params['q']); ?>">
			<button type="submit" class="btn btn-primary btn-sm btn-block mt-1">Cari</button>
		</form>

		<span class="eyebrow">Kategori</span>
		<ul class="filter-list mb-2">
			<li>
				<a href="<?php echo $build_url(array('category' => '')); ?>" class="<?php echo $query_params['category'] === '' ? 'is-active' : ''; ?>">
					<span>Semua Kategori</span>
				</a>
			</li>
			<?php foreach ($categories as $category): ?>
				<li>
					<a href="<?php echo $build_url(array('category' => $category['slug'])); ?>" class="<?php echo $query_params['category'] === $category['slug'] ? 'is-active' : ''; ?>">
						<span><?php echo html_escape($category['name']); ?></span>
						<span class="count"><?php echo (int) $category['product_count']; ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<span class="eyebrow">Audiens</span>
		<div class="chip-row mb-2">
			<?php foreach ($groups as $value => $label): ?>
				<a class="chip <?php echo $query_params['group'] === $value ? 'is-active' : ''; ?>" href="<?php echo $build_url(array('group' => $value)); ?>"><?php echo html_escape($label); ?></a>
			<?php endforeach; ?>
		</div>

		<span class="eyebrow">Dukungan OS</span>
		<div class="chip-row">
			<?php foreach ($systems as $value => $label): ?>
				<a class="chip <?php echo $query_params['os'] === $value ? 'is-active' : ''; ?>" href="<?php echo $build_url(array('os' => $value)); ?>"><?php echo html_escape($label); ?></a>
			<?php endforeach; ?>
		</div>
	</aside>

	<div>
		<div class="toolbar">
			<span class="result-count">
				<strong><?php echo (int) $total_products; ?></strong> produk ditemukan
				<?php if ($query_params['q'] !== ''): ?>
					untuk “<?php echo html_escape($query_params['q']); ?>”
				<?php endif; ?>
			</span>

			<form action="<?php echo site_url('products'); ?>" method="get">
				<input type="hidden" name="q" value="<?php echo html_escape($query_params['q']); ?>">
				<input type="hidden" name="category" value="<?php echo html_escape($query_params['category']); ?>">
				<input type="hidden" name="group" value="<?php echo html_escape($query_params['group']); ?>">
				<input type="hidden" name="os" value="<?php echo html_escape($query_params['os']); ?>">
				<label for="sort" class="mb-0 nowrap">Urutkan</label>
				<select name="sort" id="sort">
					<?php foreach ($sorts as $value => $label): ?>
						<option value="<?php echo $value; ?>" <?php echo $query_params['sort'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn btn-ghost btn-sm">Terapkan</button>
			</form>
		</div>

		<?php if (empty($products)): ?>
			<div class="empty-state">
				<div class="empty-icon">&#9906;</div>
				<h3>Tidak ada produk yang cocok</h3>
				<p>Coba ubah kata kunci pencarian atau reset filter yang aktif.</p>
				<a class="btn btn-primary" href="<?php echo site_url('products'); ?>">Lihat semua produk</a>
			</div>
		<?php else: ?>
			<div class="grid grid-3">
				<?php
				foreach ($products as $product)
				{
					$this->load->view('partials/product_card', array('product' => $product, 'cart_ids' => $cart_ids));
				}
				?>
			</div>

			<?php echo $pagination; ?>
		<?php endif; ?>
	</div>
</div>
