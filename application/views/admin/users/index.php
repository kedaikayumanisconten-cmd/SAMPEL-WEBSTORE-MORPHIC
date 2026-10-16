<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin user listing.
 *
 * @var array  $users
 * @var array  $filters
 * @var array  $stats
 * @var int    $total
 * @var string $pagination
 */
?>
<div class="stat-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Total pengguna</div>
		<div class="stat-value"><?php echo (int) $stats['total']; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Akun aktif</div>
		<div class="stat-value"><?php echo (int) $stats['active']; ?></div>
		<div class="stat-foot"><?php echo (int) $stats['total'] - (int) $stats['active']; ?> nonaktif</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Staf (Owner &amp; Admin)</div>
		<div class="stat-value"><?php echo (int) $stats['admins']; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Halaman</div>
		<div class="stat-value"><?php echo count($users); ?></div>
		<div class="stat-foot">dari <?php echo (int) $total; ?> pengguna</div>
	</div>
</div>

<div class="card">
	<form action="<?php echo site_url('admin/users'); ?>" method="get" class="row row-wrap">
		<input type="text" name="q" placeholder="Cari nama atau email&hellip;" value="<?php echo html_escape($filters['q']); ?>" style="width:280px">
		<select name="role" style="width:auto">
			<option value="">Semua role</option>
			<?php foreach ($roles as $role): ?>
				<option value="<?php echo html_escape($role); ?>" <?php echo $filters['role'] === $role ? 'selected' : ''; ?>><?php echo html_escape(role_label($role)); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="status" style="width:auto">
			<option value="">Semua status</option>
			<option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Aktif</option>
			<option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Nonaktif</option>
		</select>
		<button type="submit" class="btn btn-ghost btn-sm">Filter</button>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/users'); ?>">Reset</a>
	</form>
</div>

<div class="card">
	<div class="card-head">
		<h3><?php echo (int) $total; ?> pengguna</h3>
		<span class="badge badge-muted">Halaman ini: <?php echo count($users); ?></span>
	</div>

	<?php if (empty($users)): ?>
		<div class="empty-state">
			<div class="empty-icon">&#128100;</div>
			<h3>Tidak ada pengguna</h3>
			<p>Ubah filter pencarian atau kata kunci di atas.</p>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th>Pengguna</th>
						<th>Username</th>
						<th>Role</th>
						<th>Pesanan</th>
						<th>Lisensi</th>
						<th>Login Terakhir</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($users as $user): ?>
						<?php
						$is_self    = (int) $user['id'] === (int) $current_user['id'];
						$active     = (int) $user['is_active'] === 1;
						$can_manage = can_manage_role($current_user['role'], $user['role']);
						?>
						<tr>
							<td>
								<div class="cell-title">
									<?php echo html_escape($user['full_name']); ?>
									<?php if ($is_self): ?>
										<span class="badge badge-accent">Anda</span>
									<?php endif; ?>
								</div>
								<div class="cell-sub"><?php echo html_escape($user['email']); ?></div>
								<?php if ( ! empty($user['phone'])): ?>
									<div class="cell-sub"><?php echo html_escape($user['phone']); ?></div>
								<?php endif; ?>
								<div class="cell-sub">Gabung <?php echo datetime_id($user['created_at'], FALSE); ?></div>
							</td>
							<td class="nowrap">
								<?php if ( ! empty($user['username'])): ?>
									<span class="mono">@<?php echo html_escape($user['username']); ?></span>
								<?php else: ?>
									<span class="text-dim"><small>Tanpa username</small></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ($can_manage && ! $is_self): ?>
									<?php echo form_open('admin/users/set_role/'.$user['id'], array('class' => 'inline-form')); ?>
										<select name="role" style="width:auto" onchange="this.form.submit()">
											<?php foreach ($assignable as $role): ?>
												<option value="<?php echo html_escape($role); ?>" <?php echo $user['role'] === $role ? 'selected' : ''; ?>><?php echo html_escape(role_label($role)); ?></option>
											<?php endforeach; ?>
										</select>
									<?php echo form_close(); ?>
								<?php else: ?>
									<?php echo role_badge($user['role']); ?>
								<?php endif; ?>
							</td>
							<td><?php echo (int) $user['order_count']; ?></td>
							<td><?php echo (int) $user['license_count']; ?></td>
							<td class="nowrap">
								<?php echo $user['last_login_at'] ? datetime_id($user['last_login_at'], FALSE) : '<span class="text-dim">Belum pernah</span>'; ?>
							</td>
							<td>
								<?php echo $active
									? '<span class="badge badge-success">Aktif</span>'
									: '<span class="badge badge-danger">Nonaktif</span>'; ?>
							</td>
							<td class="actions">
								<?php if ($can_manage): ?>
									<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/users/edit/'.$user['id']); ?>">Ubah</a>
								<?php endif; ?>
								<?php if ($can_manage && ! $is_self): ?>
									<?php echo form_open('admin/users/toggle/'.$user['id'], array('class' => 'inline-form')); ?>
										<button type="submit" class="btn <?php echo $active ? 'btn-danger' : 'btn-success'; ?> btn-sm"
											data-confirm="<?php echo $active ? 'Nonaktifkan akun ini? Pengguna tidak akan bisa masuk.' : 'Aktifkan kembali akun ini?'; ?>">
											<?php echo $active ? 'Nonaktifkan' : 'Aktifkan'; ?>
										</button>
									<?php echo form_close(); ?>
								<?php elseif ( ! $can_manage): ?>
									<span class="text-dim"><small>Terkunci</small></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php echo $pagination; ?>
	<?php endif; ?>
</div>
