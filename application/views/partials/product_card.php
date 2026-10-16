<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product card partial.
 *
 * @var array $product
 * @var array $cart_ids
 * @var array $owned_ids
 */
$image    = product_image_url($product['thumbnail_url']);
$in_cart  = in_array((int) $product['id'], $cart_ids, TRUE);
$owned    = isset($owned_ids) && in_array((int) $product['id'], $owned_ids, TRUE);
$audience = isset($product['target_group']) ? $product['target_group'] : 'both';
?>
<article class="product-card">
	<div class="product-thumb">
		<?php if ($image): ?>
			<img src="<?php echo html_escape($image); ?>" alt="<?php echo html_escape($product['title']); ?>" loading="lazy">
		<?php else: ?>
			<span class="thumb-fallback"><?php echo html_escape(initials_of($product['title'])); ?></span>
		<?php endif; ?>

		<div class="thumb-badges">
			<?php if ((int) $product['is_featured'] === 1): ?>
				<span class="badge badge-warning">Unggulan</span>
			<?php endif; ?>
			<span class="badge badge-muted"><?php echo ((int) $product['requires_license_key'] === 1) ? 'License Key' : 'No Key'; ?></span>
		</div>

		<span class="badge badge-accent thumb-audience"><?php echo html_escape(target_group_label($audience)); ?></span>
	</div>

	<div class="product-body">
		<span class="product-cat"><?php echo html_escape($product['category_name']); ?></span>

		<h3 class="product-title">
			<a href="<?php echo site_url('product/'.$product['slug']); ?>"><?php echo html_escape($product['title']); ?></a>
		</h3>

		<p class="product-desc"><?php echo html_escape(excerpt($product['short_description'], 90)); ?></p>

		<div class="product-meta">
			<span><?php echo html_escape(os_label($product['os_support'])); ?></span>
			<span>v<?php echo html_escape($product['version']); ?></span>
		</div>

		<div class="product-meta">
			<span class="product-price"><span class="currency">Rp</span><?php echo rupiah($product['price'], FALSE); ?></span>
			<span><?php echo html_escape(file_size_label($product['file_size_mb'])); ?></span>
		</div>

		<div class="product-actions">
			<?php if ($owned): ?>
				<a class="btn btn-success btn-sm" href="<?php echo site_url('library'); ?>">Sudah Dimiliki</a>
			<?php elseif ($in_cart): ?>
				<a class="btn btn-ghost btn-sm" href="<?php echo site_url('cart'); ?>">Di Keranjang</a>
			<?php else: ?>
				<?php echo form_open('cart/add/'.$product['id'], array('class' => 'inline-form')); ?>
					<button type="submit" class="btn btn-primary btn-sm btn-block">+ Keranjang</button>
				<?php echo form_close(); ?>
			<?php endif; ?>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('product/'.$product['slug']); ?>">Detail</a>
		</div>
	</div>
</article>
