<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="auth-shell">
	<div class="card">
		<div class="auth-head">
			<span class="brand-mark">M</span>
			<h2 class="mb-1">Buat akun M.O.R.P.H.I.C</h2>
			<p class="text-muted mb-0"><small>Gratis. Tidak ada biaya langganan, cukup bayar produk yang Anda beli.</small></p>
		</div>

		<?php echo form_open('register'); ?>
			<div class="form-group">
				<label for="full_name">Nama Lengkap <span class="req">*</span></label>
				<input type="text" id="full_name" name="full_name" value="<?php echo html_escape(set_value('full_name')); ?>" placeholder="Nama Anda" required autofocus>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="username">Username <span class="text-dim">(opsional)</span></label>
					<input type="text" id="username" name="username" value="<?php echo html_escape(set_value('username')); ?>" placeholder="mis. raka_editor">
					<div class="form-hint">Bisa dipakai untuk masuk sebagai pengganti email.</div>
				</div>

				<div class="form-group">
					<label for="email">Email <span class="req">*</span></label>
					<input type="email" id="email" name="email" value="<?php echo html_escape(set_value('email')); ?>" placeholder="nama@email.com" required>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="password">Password <span class="req">*</span></label>
					<?php $this->load->view('partials/password_field', array('password_placeholder' => 'Minimal 8 karakter')); ?>
				</div>

				<div class="form-group">
					<label for="password_confirm">Ulangi Password <span class="req">*</span></label>
					<?php $this->load->view('partials/password_field', array(
						'password_id'          => 'password_confirm',
						'password_name'        => 'password_confirm',
						'password_placeholder' => 'Ulangi password',
					)); ?>
				</div>
			</div>

			<div class="form-group">
				<label class="checkbox">
					<input type="checkbox" name="terms" value="1" required>
					<span>Saya menyetujui syarat &amp; ketentuan serta kebijakan lisensi produk digital M.O.R.P.H.I.C.</span>
				</label>
			</div>

			<button type="submit" class="btn btn-primary btn-lg btn-block">Daftar Sekarang</button>
		<?php echo form_close(); ?>

		<div class="auth-foot">
			Sudah punya akun? <a href="<?php echo site_url('login'); ?>">Masuk di sini</a>
		</div>
	</div>
</div>
