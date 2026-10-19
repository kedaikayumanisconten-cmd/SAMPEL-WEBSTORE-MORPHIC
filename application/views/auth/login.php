<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="auth-shell">
	<div class="card">
		<div class="auth-head">
			<?php $auth_logo = brand_logo_url($store_logo); ?>
			<?php if ($auth_logo): ?>
				<span class="brand-logo brand-logo-lg"><img src="<?php echo html_escape($auth_logo); ?>" alt="<?php echo html_escape($store_name); ?>"></span>
			<?php else: ?>
				<span class="brand-mark">M</span>
			<?php endif; ?>
			<h2 class="mb-1">Masuk ke akun Anda</h2>
			<p class="text-muted mb-0"><small>Akses Digital Library, riwayat pesanan, dan serial number produk Anda.</small></p>
		</div>

		<?php echo form_open('login'); ?>
			<div class="form-group">
				<label for="email">Email atau Username <span class="req">*</span></label>
				<input type="text" id="email" name="email" value="<?php echo html_escape(set_value('email', '', FALSE)); ?>" placeholder="nama@email.com atau username" required autofocus>
			</div>

			<div class="form-group">
				<label for="password">Password <span class="req">*</span></label>
				<?php $this->load->view('partials/password_field', array('password_placeholder' => '••••••••')); ?>
			</div>

			<button type="submit" class="btn btn-primary btn-lg btn-block">Masuk</button>
		<?php echo form_close(); ?>

		<div class="auth-foot">
			Belum punya akun? <a href="<?php echo site_url('register'); ?>">Daftar sekarang</a>
		</div>
	</div>
</div>
