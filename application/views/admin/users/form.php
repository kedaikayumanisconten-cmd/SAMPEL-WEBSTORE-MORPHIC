<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin single-user account editor.
 *
 * Lets an administrator change another member's login username and reset a
 * forgotten password. Username/e-mail must stay unique; the password field is
 * optional and only replaces the current hash when filled.
 *
 * @var array       $user
 * @var bool        $is_self
 * @var string|null $form_error
 */
$value = function ($field, $default = '') use ($user) {
	return set_value($field, isset($user[$field]) && $user[$field] !== NULL ? $user[$field] : $default);
};

$temp_password = $this->session->flashdata('temp_password');
?>
<div class="row-between mb-3">
	<div>
		<h2 class="mb-1">Ubah Akun</h2>
		<p class="text-muted mb-0"><small>Perbarui username, email, atau reset password pengguna.</small></p>
	</div>
	<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/users'); ?>">&larr; Kembali ke daftar pengguna</a>
</div>

<?php if ( ! empty($form_error)): ?>
	<div class="alert alert-error"><span class="alert-icon">&#9888;</span><span><?php echo html_escape($form_error); ?></span></div>
<?php endif; ?>

<?php if ( ! empty($temp_password)): ?>
	<div class="card">
		<div class="card-head">
			<h3>Password Sementara</h3>
			<span class="badge badge-accent">tampil sekali</span>
		</div>
		<div class="row-between">
			<span class="mono" style="font-size:1.35rem;letter-spacing:1px"><?php echo html_escape($temp_password); ?></span>
			<button type="button" class="btn btn-primary btn-sm" data-copy="<?php echo html_escape($temp_password); ?>">Salin</button>
		</div>
		<div class="form-hint">Berikan password ini kepada pemilik akun lalu minta ia menggantinya setelah masuk. Halaman ini tidak akan menampilkannya lagi setelah dimuat ulang.</div>
	</div>
<?php endif; ?>

<?php echo form_open('admin/users/generate_password/'.$user['id'], array('id' => 'genpass-form')); ?>
<?php echo form_close(); ?>

<?php echo form_open_multipart('admin/users/update/'.$user['id']); ?>
	<div class="grid grid-2">
		<!-- ======================= LEFT: identity ======================= -->
		<div>
			<section class="card">
				<div class="card-head">
					<h3>Username &amp; Data Akun</h3>
					<?php echo role_badge($user['role']); ?>
				</div>

				<div class="profile-identity mb-3">
					<?php $avatar = avatar_url($user['avatar']); ?>
					<?php if ($avatar): ?>
						<span class="avatar avatar-lg"><img src="<?php echo html_escape($avatar); ?>" alt="Foto profil <?php echo html_escape($user['full_name']); ?>"></span>
					<?php else: ?>
						<span class="avatar avatar-lg"><?php echo html_escape(initials_of($user['full_name'])); ?></span>
					<?php endif; ?>
					<div>
						<strong class="profile-name"><?php echo html_escape($user['full_name']); ?></strong>
						<div class="profile-handle">
							<?php if ( ! empty($user['username'])): ?>
								<span class="mono">@<?php echo html_escape($user['username']); ?></span>
							<?php else: ?>
								<span class="text-dim">Belum ada username</span>
							<?php endif; ?>
						</div>
						<div class="cell-sub"><?php echo html_escape($user['email']); ?></div>
						<?php if ( ! empty($user['phone'])): ?>
							<div class="cell-sub"><?php echo html_escape($user['phone']); ?></div>
						<?php endif; ?>
					</div>
				</div>

				<div class="form-group">
					<label for="avatar_file">Foto Profil</label>
					<input type="file" id="avatar_file" name="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif">
					<div class="form-hint">Format JPG, PNG, WEBP, atau GIF. Ukuran maksimal 2 MB.</div>
					<?php if ( ! empty($user['avatar'])): ?>
						<label class="checkbox mt-2">
							<input type="checkbox" name="remove_avatar" value="1">
							<span>Hapus foto profil saat ini</span>
						</label>
					<?php endif; ?>
				</div>

				<div class="form-group">
					<label for="full_name">Nama Lengkap <span class="req">*</span></label>
					<input type="text" id="full_name" name="full_name" value="<?php echo html_escape($value('full_name')); ?>" required>
				</div>

				<div class="form-group">
					<label for="username">Username</label>
					<div class="input-prefix">
						<span>@</span>
						<input type="text" id="username" name="username" value="<?php echo html_escape($value('username')); ?>" placeholder="admin" autocomplete="username">
					</div>
					<div class="form-hint">Huruf, angka, titik, garis bawah, dan strip. Minimal 3 karakter. Kosongkan untuk menghapus username.</div>
				</div>

				<div class="form-group">
					<label for="email">Email <span class="req">*</span></label>
					<input type="email" id="email" name="email" value="<?php echo html_escape($value('email')); ?>" required autocomplete="email">
					<div class="form-hint">Email dan username sama-sama bisa dipakai untuk login.</div>
				</div>

				<div class="form-group">
					<label for="phone">Nomor Telepon / WhatsApp</label>
					<input type="tel" id="phone" name="phone" value="<?php echo html_escape($value('phone')); ?>" placeholder="mis. 08123456789" autocomplete="tel">
					<div class="form-hint">Hanya angka, spasi, dan simbol + - ( ).</div>
				</div>
			</section>
		</div>

		<!-- ======================= RIGHT: password + meta ======================= -->
		<div>
			<section class="card">
				<div class="card-head">
					<h3>Reset Password</h3>
					<span class="badge badge-muted">opsional</span>
				</div>

				<p class="text-muted"><small>Biarkan kosong bila password tidak ingin diubah.</small></p>

				<div class="form-group">
					<label for="password">Password Baru</label>
					<?php $this->load->view('partials/password_field', array('password_placeholder' => 'Minimal 8 karakter', 'password_autocomplete' => 'new-password', 'password_required' => FALSE)); ?>
				</div>

				<div class="form-group">
					<label for="password_confirm">Ulangi Password Baru</label>
					<?php $this->load->view('partials/password_field', array('password_id' => 'password_confirm', 'password_name' => 'password_confirm', 'password_placeholder' => 'Ulangi password baru', 'password_autocomplete' => 'new-password', 'password_required' => FALSE)); ?>
				</div>

				<?php if ($is_self): ?>
					<div class="alert alert-info">
						<span class="alert-icon">&#8505;</span>
						<span>Ini akun Anda sendiri. Sesi Anda akan diperbarui otomatis setelah password berubah.</span>
					</div>
				<?php else: ?>
					<div class="alert alert-info">
						<span class="alert-icon">&#8505;</span>
						<span>Setelah password berubah, semua sesi login pengguna ini diakhiri dan ia harus masuk kembali dengan password baru.</span>
					</div>
				<?php endif; ?>
			</section>

			<section class="card">
				<div class="card-head"><h3>Ringkasan Akun</h3></div>

				<ul class="spec-list">
					<li><span class="spec-key">ID</span><span class="spec-val">#<?php echo (int) $user['id']; ?></span></li>
					<li><span class="spec-key">Role</span><span class="spec-val"><?php echo role_badge($user['role']); ?></span></li>
					<li><span class="spec-key">Status</span><span class="spec-val"><?php echo (int) $user['is_active'] === 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>'; ?></span></li>
					<li><span class="spec-key">Login terakhir</span><span class="spec-val"><?php echo $user['last_login_at'] ? html_escape(datetime_id($user['last_login_at'])) : '<span class="text-dim">Belum pernah</span>'; ?></span></li>
					<li><span class="spec-key">Terdaftar</span><span class="spec-val"><?php echo html_escape(datetime_id($user['created_at'], FALSE)); ?></span></li>
					<li><span class="spec-key">Terakhir diubah</span><span class="spec-val"><?php echo html_escape(datetime_id($user['updated_at'])); ?></span></li>
				</ul>
			</section>
		</div>
	</div>

	<div class="card">
		<div class="row-between">
			<span class="text-muted"><small>Username dan email wajib unik antar akun.</small></span>
			<div class="row">
				<a class="btn btn-ghost" href="<?php echo site_url('admin/users'); ?>">Batal</a>
				<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>
