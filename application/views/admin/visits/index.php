<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Traffic log listing.
 *
 * @var array  $visits
 * @var array  $filters
 * @var int    $total
 * @var int    $window
 * @var array  $stats
 * @var array  $top_pages
 * @var array  $devices
 * @var string $pagination
 */
$top_pages_rows = array();

foreach ($top_pages as $page)
{
	$top_pages_rows[] = array(
		'label' => $page['label'],
		'value' => $page['value'],
		'sub'   => $page['sub'],
	);
}

$export_query = array_filter(array(
	'q'            => $filters['q'],
	'device'       => $filters['device'],
	'only_members' => $filters['only_members'] ? '1' : '',
));
?>
<div class="kpi-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Page view (<?php echo (int) $window; ?> hari)</div>
		<div class="stat-value"><?php echo number_format((int) $stats['hits'], 0, ',', '.'); ?></div>
		<div class="stat-foot"><?php echo (int) $stats['today']; ?> hari ini</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Pengunjung unik</div>
		<div class="stat-value"><?php echo number_format((int) $stats['unique'], 0, ',', '.'); ?></div>
		<div class="stat-foot"><?php echo (int) $stats['ips']; ?> alamat IP berbeda</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Member vs tamu</div>
		<div class="stat-value"><?php echo number_format((int) $stats['member_hits'], 0, ',', '.'); ?></div>
		<div class="stat-foot"><?php echo (int) $stats['guest_hits']; ?> kunjungan tamu</div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Total baris tersimpan</div>
		<div class="stat-value"><?php echo number_format((int) $stats['total_rows'], 0, ',', '.'); ?></div>
		<div class="stat-foot"><?php echo (int) $stats['bots']; ?> hit bot diabaikan</div>
	</div>
</div>

<div class="grid grid-2 mb-3">
	<section class="card">
		<div class="card-head">
			<h3>Halaman terpopuler</h3>
			<span class="badge badge-muted"><?php echo (int) $window; ?> hari</span>
		</div>
		<?php echo html_hbar_list($top_pages_rows, array('empty' => 'Belum ada kunjungan.')); ?>
	</section>

	<section class="card">
		<div class="card-head">
			<h3>Perangkat</h3>
		</div>
		<?php echo svg_donut_chart($devices, array('size' => 150, 'caption' => 'Kunjungan', 'empty' => 'Belum ada data.')); ?>
	</section>
</div>

<section class="card">
	<div class="card-head">
		<h3>Log kunjungan</h3>
		<span class="badge badge-muted"><?php echo number_format((int) $total, 0, ',', '.'); ?> baris</span>
	</div>

	<form class="toolbar" method="get" action="<?php echo site_url('admin/visits'); ?>">
		<div class="toolbar-group">
			<input type="search" name="q" value="<?php echo html_escape($filters['q']); ?>" placeholder="Cari halaman, IP, atau nama pelanggan">
			<select name="device">
				<option value="">Semua perangkat</option>
				<?php foreach (array('desktop', 'mobile', 'tablet', 'bot') as $device): ?>
					<option value="<?php echo $device; ?>" <?php echo $filters['device'] === $device ? 'selected' : ''; ?>><?php echo device_name($device); ?></option>
				<?php endforeach; ?>
			</select>
			<label class="checkbox inline">
				<input type="checkbox" name="only_members" value="1" <?php echo $filters['only_members'] ? 'checked' : ''; ?>>
				<span>Hanya member</span>
			</label>
		</div>
		<div class="toolbar-group">
			<button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/visits'); ?>">Reset</a>
		</div>
	</form>

	<?php if (empty($visits)): ?>
		<div class="chart-empty">
			<span class="chart-empty-icon">&#128202;</span>
			<span>Belum ada kunjungan yang cocok dengan filter ini.</span>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th>Waktu</th>
						<th>Halaman</th>
						<th>Pengunjung</th>
						<th>IP</th>
						<th>Perangkat</th>
						<th>Referer</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($visits as $visit): ?>
						<tr>
							<td class="nowrap">
								<div class="cell-title"><?php echo html_escape(time_ago($visit['visited_at'])); ?></div>
								<div class="cell-sub"><?php echo html_escape(datetime_id($visit['visited_at'])); ?></div>
							</td>
							<td>
								<div class="cell-title"><?php echo html_escape(visit_page_label($visit['uri'])); ?></div>
								<div class="cell-sub mono">/<?php echo html_escape($visit['uri']); ?></div>
							</td>
							<td>
								<?php if ($visit['user_id'] !== NULL): ?>
									<div class="cell-title"><?php echo html_escape($visit['full_name']); ?></div>
									<div class="cell-sub"><?php echo is_staff_role($visit['role']) ? role_badge($visit['role']) : '<span class="badge badge-muted">Member</span>'; ?></div>
								<?php else: ?>
									<span class="badge badge-muted">Tamu</span>
								<?php endif; ?>
							</td>
							<td class="mono nowrap"><?php echo html_escape($visit['ip_address']); ?></td>
							<td><span class="badge badge-muted"><?php echo device_name($visit['device']); ?></span></td>
							<td class="cell-sub"><?php echo $visit['referer'] ? html_escape(excerpt($visit['referer'], 42)) : '<span class="text-dim">Langsung</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php echo $pagination; ?>

		<form method="post" action="<?php echo site_url('admin/visits/clear'); ?>" data-confirm="Hapus seluruh riwayat kunjungan? Tindakan ini tidak dapat dibatalkan.">
			<input type="hidden" name="csrf_test_name" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<button type="submit" class="btn btn-ghost btn-sm btn-danger">Bersihkan riwayat</button>
		</form>
	<?php endif; ?>
</section>
