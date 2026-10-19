<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin account settings.
 *
 * @var array $account
 * @var array $stats
 */
$has_username = ! empty($account['username']);
?>
<div class="grid grid-2 mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Identitas Admin</h3>
			<?php echo role_badge($account['role']); ?>
		</div>

		<div class="profile-identity">
			<?php $avatar = avatar_url($account['avatar']); ?>
			<?php if ($avatar): ?>
				<span class="avatar avatar-lg"><img src="<?php echo html_escape($avatar); ?>" alt="Foto profil <?php echo html_escape($account['full_name']); ?>"></span>
			<?php else: ?>
				<span class="avatar avatar-lg"><?php echo html_escape(initials_of($account['full_name'])); ?></span>
			<?php endif; ?>
			<div>
				<strong class="profile-name"><?php echo html_escape($account['full_name']); ?></strong>
				<div class="profile-handle">
					<?php if ($has_username): ?>
						<span class="mono">@<?php echo html_escape($account['username']); ?></span>
					<?php else: ?>
						<span class="text-dim">Belum ada username</span>
					<?php endif; ?>
				</div>
				<div class="cell-sub"><?php echo html_escape($account['email']); ?></div>
				<?php if ( ! empty($account['phone'])): ?>
					<div class="cell-sub"><?php echo html_escape($account['phone']); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<ul class="spec-list mt-2">
			<li><span class="spec-key">Nomor telepon</span><span class="spec-val"><?php echo ! empty($account['phone']) ? html_escape($account['phone']) : '<span class="text-dim">Belum diisi</span>'; ?></span></li>
			<li><span class="spec-key">Status akun</span><span class="spec-val"><?php echo (int) $account['is_active'] === 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>'; ?></span></li>
			<li><span class="spec-key">Login terakhir</span><span class="spec-val"><?php echo $account['last_login_at'] ? html_escape(datetime_id($account['last_login_at'])) : '<span class="text-dim">Belum pernah</span>'; ?></span></li>
			<li><span class="spec-key">Terdaftar sejak</span><span class="spec-val"><?php echo html_escape(datetime_id($account['created_at'], FALSE)); ?></span></li>
			<li><span class="spec-key">Terakhir diubah</span><span class="spec-val"><?php echo html_escape(datetime_id($account['updated_at'])); ?></span></li>
		</ul>

		<hr>

		<span class="eyebrow">Ringkasan panel</span>
		<ul class="spec-list">
			<li><span class="spec-key">Pesanan lunas</span><span class="spec-val"><?php echo (int) $stats['orders']; ?></span></li>
			<li><span class="spec-key">Menunggu verifikasi</span><span class="spec-val"><?php echo (int) $stats['pending']; ?></span></li>
			<li><span class="spec-key">Lisensi terbit</span><span class="spec-val"><?php echo (int) $stats['licenses']; ?></span></li>
		</ul>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Ubah Username &amp; Data Akun</h3>
			<span class="badge badge-muted">users</span>
		</div>

		<p class="text-muted"><small>Username dan email sama-sama bisa dipakai untuk masuk ke panel admin.</small></p>

		<?php echo form_open_multipart('admin/profile'); ?>
			<input type="hidden" name="action" value="account">

			<div class="form-group">
				<label for="avatar_file">Foto Profil</label>
				<input type="file" id="avatar_file" name="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif">
				<div class="form-hint">Format JPG, PNG, WEBP, atau GIF. Ukuran maksimal 2 MB.</div>
				<?php if ( ! empty($account['avatar'])): ?>
					<label class="checkbox mt-2">
						<input type="checkbox" name="remove_avatar" value="1">
						<span>Hapus foto profil saat ini</span>
					</label>
				<?php endif; ?>
			</div>

			<div class="form-group">
				<label for="full_name">Nama Lengkap <span class="req">*</span></label>
				<input type="text" id="full_name" name="full_name" value="<?php echo html_escape(set_value('full_name', $account['full_name'], FALSE)); ?>" required>
			</div>

			<div class="form-group">
				<label for="username">Username</label>
				<div class="input-prefix">
					<span>@</span>
					<input type="text" id="username" name="username" value="<?php echo html_escape(set_value('username', $account['username'], FALSE)); ?>" placeholder="admin" autocomplete="username">
				</div>
				<div class="form-hint">Huruf, angka, titik, garis bawah, dan strip. Minimal 3 karakter. Kosongkan untuk menghapus username.</div>
			</div>

			<div class="form-group">
				<label for="email">Email <span class="req">*</span></label>
				<input type="email" id="email" name="email" value="<?php echo html_escape(set_value('email', $account['email'], FALSE)); ?>" required autocomplete="email">
				<div class="form-hint">Dipakai untuk login dan menerima notifikasi pesanan.</div>
			</div>

			<div class="form-group">
				<label for="phone">Nomor Telepon / WhatsApp</label>
				<input type="tel" id="phone" name="phone" value="<?php echo html_escape(set_value('phone', $account['phone'], FALSE)); ?>" placeholder="mis. 08123456789" autocomplete="tel">
				<div class="form-hint">Hanya angka, spasi, dan simbol + - ( ).</div>
			</div>

			<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
		<?php echo form_close(); ?>
	</section>
</div>

<div class="grid grid-2">
	<section class="card">
		<div class="card-head">
			<h3>Ubah Password</h3>
			<span class="badge badge-muted">keamanan</span>
		</div>

		<p class="text-muted"><small>Password saat ini wajib diisi sebagai konfirmasi. Sesi Anda akan diperbarui setelah password berubah.</small></p>

		<?php echo form_open('admin/profile'); ?>
			<input type="hidden" name="action" value="password">

			<div class="form-group">
				<label for="current_password">Password Saat Ini <span class="req">*</span></label>
				<?php $this->load->view('partials/password_field', array('password_id' => 'current_password', 'password_name' => 'current_password', 'password_placeholder' => '••••••••', 'password_autocomplete' => 'current-password')); ?>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="new_password">Password Baru <span class="req">*</span></label>
					<?php $this->load->view('partials/password_field', array('password_id' => 'new_password', 'password_name' => 'new_password', 'password_placeholder' => 'Minimal 8 karakter', 'password_autocomplete' => 'new-password')); ?>
				</div>

				<div class="form-group">
					<label for="new_password_confirm">Ulangi Password Baru <span class="req">*</span></label>
					<?php $this->load->view('partials/password_field', array('password_id' => 'new_password_confirm', 'password_name' => 'new_password_confirm', 'password_placeholder' => 'Ulangi password baru', 'password_autocomplete' => 'new-password')); ?>
				</div>
			</div>

			<button type="submit" class="btn btn-primary">Perbarui Password</button>
		<?php echo form_close(); ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Tips Keamanan</h3>
		</div>
		<ul class="buy-list">
			<li>Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol.</li>
			<li>Jangan pakai password yang sama dengan akun lain.</li>
			<li>Ganti password secara berkala, terutama setelah mengakses panel dari perangkat publik.</li>
			<li>Riwayat percobaan login yang gagal dibatasi otomatis selama 15 menit.</li>
		</ul>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/users'); ?>">Kelola pengguna lain</a>
	</section>
</div>
