<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin order listing.
 *
 * @var array  $orders
 * @var array  $filters
 * @var array  $payment_methods
 * @var array  $stats
 * @var int    $total
 * @var string $pagination
 */
?>
<div class="stat-grid mb-3">
	<div class="stat-card">
		<div class="stat-label">Total pesanan</div>
		<div class="stat-value"><?php echo (int) $stats['total']; ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Lunas</div>
		<div class="stat-value"><?php echo (int) $stats['paid']; ?></div>
		<div class="stat-foot">Rp <?php echo rupiah($stats['revenue'], FALSE); ?></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Menunggu</div>
		<div class="stat-value"><?php echo (int) $stats['pending']; ?></div>
		<div class="stat-foot"><a href="<?php echo site_url('admin/orders?status=pending'); ?>">Verifikasi</a></div>
	</div>
	<div class="stat-card">
		<div class="stat-label">Gagal / batal</div>
		<div class="stat-value"><?php echo (int) $stats['failed'] + (int) $stats['cancelled']; ?></div>
	</div>
</div>

<div class="card">
	<form action="<?php echo site_url('admin/orders'); ?>" method="get" class="row row-wrap">
		<input type="text" name="q" placeholder="Cari nomor pesanan / nama / email&hellip;" value="<?php echo html_escape($filters['q']); ?>" style="width:280px">
		<select name="status" style="width:auto">
			<option value="">Semua status</option>
			<?php foreach (array('pending', 'paid', 'failed', 'cancelled') as $status): ?>
				<option value="<?php echo $status; ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
			<?php endforeach; ?>
		</select>
		<select name="method" style="width:auto">
			<option value="">Semua metode</option>
			<?php foreach ($payment_methods as $key => $label): ?>
				<option value="<?php echo html_escape($key); ?>" <?php echo $filters['method'] === $key ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn btn-ghost btn-sm">Filter</button>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/orders'); ?>">Reset</a>
	</form>
</div>

<div class="card">
	<div class="card-head">
		<h3><?php echo (int) $total; ?> pesanan</h3>
	</div>

	<?php if (empty($orders)): ?>
		<div class="empty-state">
			<div class="empty-icon">&#128179;</div>
			<h3>Tidak ada pesanan</h3>
			<p>Pesanan dari pelanggan akan muncul di sini untuk diverifikasi.</p>
		</div>
	<?php else: ?>
		<div class="table-wrap">
			<table class="data">
				<thead>
					<tr>
						<th>Nomor</th>
						<th>Pelanggan</th>
						<th>Tanggal</th>
						<th>Metode</th>
						<th>Total</th>
						<th>Bukti</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($orders as $order): ?>
						<tr>
							<td class="mono"><a href="<?php echo site_url('admin/orders/detail/'.$order['id']); ?>"><?php echo html_escape($order['order_number']); ?></a></td>
							<td>
								<div class="cell-title"><?php echo html_escape($order['full_name']); ?></div>
								<div class="cell-sub"><?php echo html_escape($order['email']); ?></div>
							</td>
							<td class="nowrap"><?php echo datetime_id($order['created_at']); ?></td>
							<td><?php echo html_escape(payment_method_label($order['payment_method'])); ?></td>
							<td class="nowrap">Rp <?php echo rupiah($order['total_amount'], FALSE); ?></td>
							<td>
								<?php echo ! empty($order['payment_proof'])
									? '<span class="badge badge-success">Ada</span>'
									: '<span class="badge badge-warning">Belum</span>'; ?>
							</td>
							<td><?php echo status_badge($order['payment_status']); ?></td>
							<td class="actions">
								<?php if ($order['payment_status'] !== 'paid'): ?>
									<?php echo form_open('admin/orders/mark_paid/'.$order['id'], array('class' => 'inline-form')); ?>
										<button type="submit" class="btn btn-success btn-sm" data-confirm="Tandai pesanan ini lunas dan terbitkan lisensi?">Tandai Lunas</button>
									<?php echo form_close(); ?>
								<?php endif; ?>
								<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/orders/detail/'.$order['id']); ?>">Detail</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php echo $pagination; ?>
	<?php endif; ?>
</div>
