<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer profile & password.
 */
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('account'); ?>">Akun</a>
	<span>/</span>
	<span>Profil</span>
</div>

<h2 class="mb-3">Profil &amp; Keamanan</h2>

<div class="grid grid-2">
	<section class="card">
		<div class="card-head">
			<h3>Data pribadi</h3>
			<?php echo role_badge($current_user['role']); ?>
		</div>

		<?php echo form_open('account/profile'); ?>
			<input type="hidden" name="action" value="profile">

			<div class="form-group">
				<label for="full_name">Nama Lengkap <span class="req">*</span></label>
				<input type="text" id="full_name" name="full_name" value="<?php echo html_escape($current_user['full_name']); ?>" required>
			</div>

			<div class="form-group">
				<label for="email">Email <span class="req">*</span></label>
				<input type="email" id="email" name="email" value="<?php echo html_escape($current_user['email']); ?>" required>
			</div>

			<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
		<?php echo form_close(); ?>

		<hr>

		<ul class="spec-list">
			<li><span class="spec-key">Terdaftar</span><span class="spec-val"><?php echo datetime_id($current_user['created_at']); ?></span></li>
			<li><span class="spec-key">Login terakhir</span><span class="spec-val"><?php echo datetime_id($current_user['last_login_at']); ?></span></li>
			<li><span class="spec-key">Status akun</span><span class="spec-val"><?php echo ((int) $current_user['is_active'] === 1) ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>'; ?></span></li>
		</ul>
	</section>

	<section class="card">
		<div class="card-head"><h3>Ganti password</h3></div>

		<?php echo form_open('account/profile'); ?>
			<input type="hidden" name="action" value="password">

			<div class="form-group">
				<label for="current_password">Password Saat Ini <span class="req">*</span></label>
				<?php $this->load->view('partials/password_field', array('password_id' => 'current_password', 'password_name' => 'current_password', 'password_autocomplete' => 'current-password')); ?>
			</div>

			<div class="form-group">
				<label for="new_password">Password Baru <span class="req">*</span></label>
				<?php $this->load->view('partials/password_field', array('password_id' => 'new_password', 'password_name' => 'new_password', 'password_autocomplete' => 'new-password')); ?>
				<div class="form-hint">Minimal 8 karakter. Gunakan kombinasi huruf, angka, dan simbol.</div>
			</div>

			<div class="form-group">
				<label for="new_password_confirm">Ulangi Password Baru <span class="req">*</span></label>
				<?php $this->load->view('partials/password_field', array('password_id' => 'new_password_confirm', 'password_name' => 'new_password_confirm', 'password_autocomplete' => 'new-password')); ?>
			</div>

			<button type="submit" class="btn btn-primary">Perbarui Password</button>
		<?php echo form_close(); ?>
	</section>
</div>
