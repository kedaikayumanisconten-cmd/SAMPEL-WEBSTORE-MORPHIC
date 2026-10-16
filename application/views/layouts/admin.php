<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin shell: sidebar + topbar + content.
 *
 * @var string $content_view
 * @var array  $admin_user
 */
$is_active = function ($needle) {
	$target  = rtrim(site_url($needle), '/');
	$current = rtrim(current_url(), '/');

	return ($current === $target || strpos($current, $target.'/') === 0) ? 'is-active' : '';
};
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($page_title); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
	<script>
		(function () {
			try {
				var theme = localStorage.getItem('morphic-theme');
				if (theme && theme !== 'morphic') {
					document.documentElement.setAttribute('data-theme', theme);
				}
			} catch (error) {}
		})();
	</script>
</head>
<body class="admin-body">
	<div class="admin-shell">
		<aside class="admin-sidebar" id="admin-sidebar">
			<a class="brand" href="<?php echo site_url('admin'); ?>">
				<?php $admin_logo = brand_logo_url($store_logo); ?>
				<?php if ($admin_logo): ?>
					<span class="brand-logo"><img src="<?php echo html_escape($admin_logo); ?>" alt="<?php echo html_escape($store_name); ?>"></span>
				<?php else: ?>
					<span class="brand-mark">M</span>
				<?php endif; ?>
				<span>
					<?php echo html_escape($store_name); ?>
					<small>Control Panel</small>
				</span>
			</a>

			<ul class="admin-nav">
				<li><a href="<?php echo site_url('admin'); ?>" class="<?php echo $is_active('admin'); ?>">&#9635; Dashboard</a></li>

				<li class="nav-group">Katalog</li>
				<li><a href="<?php echo site_url('admin/products'); ?>" class="<?php echo $is_active('admin/products'); ?>">&#9834; Produk</a></li>
				<li><a href="<?php echo site_url('admin/categories'); ?>" class="<?php echo $is_active('admin/categories'); ?>">&#9776; Kategori</a></li>

				<li class="nav-group">Penjualan</li>
				<li>
					<a href="<?php echo site_url('admin/orders'); ?>" class="<?php echo $is_active('admin/orders'); ?>">
						&#128179; Pesanan
						<?php if ((int) $pending_orders > 0): ?>
							<span class="nav-count"><?php echo (int) $pending_orders; ?></span>
						<?php endif; ?>
					</a>
				</li>
				<li><a href="<?php echo site_url('admin/licenses'); ?>" class="<?php echo $is_active('admin/licenses'); ?>">&#128273; Lisensi</a></li>

				<li class="nav-group">Pengguna</li>
				<li><a href="<?php echo site_url('admin/users'); ?>" class="<?php echo $is_active('admin/users'); ?>">&#128101; Users</a></li>
				<li><a href="<?php echo site_url('admin/visits'); ?>" class="<?php echo $is_active('admin/visits'); ?>">&#128200; Riwayat Kunjungan</a></li>

				<li class="nav-group">Akun</li>
				<li><a href="<?php echo site_url('admin/profile'); ?>" class="<?php echo $is_active('admin/profile'); ?>">&#9881; Username &amp; Password</a></li>

				<li class="nav-group">Toko</li>
				<li><a href="<?php echo site_url('admin/settings'); ?>" class="<?php echo $is_active('admin/settings'); ?>">&#9881; Pengaturan Situs</a></li>
				<li><a href="<?php echo site_url('/'); ?>" target="_blank" rel="noopener">&#8599; Lihat Storefront</a></li>
				<li><a href="<?php echo site_url('logout'); ?>">&#9099; Keluar</a></li>
			</ul>
		</aside>

		<div class="admin-main">
			<header class="admin-topbar">
				<button class="btn-icon admin-sidebar-toggle" type="button" data-sidebar-toggle aria-label="Buka menu admin">&#9776;</button>
				<h1><?php echo html_escape(isset($content_title) ? $content_title : $page_title); ?></h1>
				<span class="spacer"></span>
				<?php $this->load->view('layouts/partials/theme_picker'); ?>
				<a class="badge badge-accent" href="<?php echo site_url('admin/profile'); ?>" title="Pengaturan akun"><?php echo html_escape($admin_user['full_name']); ?></a>
			</header>

			<div class="admin-content">
				<?php if ( ! empty($flash_success)): ?>
					<div class="alert alert-success" data-autohide><span class="alert-icon">&#10004;</span><span><?php echo html_escape($flash_success); ?></span></div>
				<?php endif; ?>

				<?php if ( ! empty($flash_error)): ?>
					<div class="alert alert-error" data-autohide><span class="alert-icon">&#9888;</span><span><?php echo html_escape($flash_error); ?></span></div>
				<?php endif; ?>

				<?php if ( ! empty($flash_info)): ?>
					<div class="alert alert-info" data-autohide><span class="alert-icon">&#8505;</span><span><?php echo html_escape($flash_info); ?></span></div>
				<?php endif; ?>

				<?php $this->load->view($content_view); ?>

				<footer class="admin-footer">
					<span>Copyright &copy; <?php echo date('Y'); ?> <?php echo html_escape($footer_copyright); ?></span>
					<span>M.O.R.P.H.I.C Control Panel</span>
				</footer>
			</div>
		</div>
	</div>

	<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
</body>
</html>
