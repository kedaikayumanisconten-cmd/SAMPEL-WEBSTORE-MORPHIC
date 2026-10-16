<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($page_title); ?></title>
	<meta name="description" content="<?php echo html_escape($store_tagline); ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
</head>
<body>
	<header class="site-header">
		<div class="container">
			<div class="header-bar">
				<a class="brand" href="<?php echo site_url('/'); ?>">
					<span class="brand-mark">M</span>
					<span>
						<?php echo html_escape($store_name); ?>
						<small>Digital Asset Store</small>
					</span>
				</a>

				<button class="nav-toggle" type="button" data-nav-toggle aria-label="Buka menu" aria-expanded="false">&#9776;</button>

				<nav class="main-nav" id="main-nav" aria-label="Navigasi utama">
					<a href="<?php echo site_url('products'); ?>" class="<?php echo $this->uri->segment(1) === 'products' || $this->uri->segment(1) === 'product' ? 'is-active' : ''; ?>">Semua Produk</a>
					<?php foreach (array_slice($nav_categories, 0, 3) as $nav_category): ?>
						<a href="<?php echo site_url('category/'.$nav_category['slug']); ?>"><?php echo html_escape($nav_category['name']); ?></a>
					<?php endforeach; ?>
					<?php if ($is_admin): ?>
						<a href="<?php echo site_url('admin'); ?>">Admin</a>
					<?php endif; ?>
				</nav>

				<div class="header-actions">
					<form class="header-search" action="<?php echo site_url('search'); ?>" method="get" role="search">
						<input type="search" name="q" placeholder="Cari plugin, LUT, preset&hellip;" value="<?php echo html_escape($this->input->get('q', TRUE)); ?>" aria-label="Cari produk">
					</form>

					<a class="btn btn-ghost btn-sm" href="<?php echo site_url('cart'); ?>">
						Keranjang
						<?php if ((int) $cart_count > 0): ?>
							<span class="badge badge-accent"><?php echo (int) $cart_count; ?></span>
						<?php endif; ?>
					</a>

					<?php if ($is_logged_in): ?>
						<a class="btn btn-primary btn-sm" href="<?php echo site_url('library'); ?>">Library Saya</a>
						<a class="btn btn-ghost btn-sm" href="<?php echo site_url('logout'); ?>" data-confirm="Keluar dari akun Anda?">Keluar</a>
					<?php else: ?>
						<a class="btn btn-ghost btn-sm" href="<?php echo site_url('login'); ?>">Masuk</a>
						<a class="btn btn-primary btn-sm" href="<?php echo site_url('register'); ?>">Daftar</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</header>

	<main class="site-main">
		<div class="container">
			<?php if ( ! empty($flash_success)): ?>
				<div class="alert alert-success" data-autohide>
					<span class="alert-icon">&#10004;</span>
					<span><?php echo html_escape($flash_success); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty($flash_error)): ?>
				<div class="alert alert-error" data-autohide>
					<span class="alert-icon">&#9888;</span>
					<span><?php echo html_escape($flash_error); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty($flash_info)): ?>
				<div class="alert alert-info" data-autohide>
					<span class="alert-icon">&#8505;</span>
					<span><?php echo html_escape($flash_info); ?></span>
				</div>
			<?php endif; ?>
