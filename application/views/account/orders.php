<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Order history.
 *
 * @var array  $orders
 * @var string $pagination
 */
?>
<div class="breadcrumb">
	<a href="<?php echo site_url('account'); ?>">Akun</a>
	<span>/</span>
	<span>Riwayat Pesanan</span>
</div>

<div class="row-between mb-3">
	<div>
		<h2 class="mb-1">Riwayat Pesanan</h2>
		<p class="text-muted mb-0">Semua transaksi digital Anda beserta status pembayarannya.</p>
	</div>
	<a class="btn btn-ghost" href="<?php echo site_url('library'); ?>">Digital Library</a>
</div>

<?php if (empty($orders)): ?>
	<div class="empty-state">
		<div class="empty-icon">&#128179;</div>
		<h3>Belum ada pesanan</h3>
		<p>Produk yang Anda beli akan tercatat di sini bersama status pembayaran dan lisensinya.</p>
		<a class="btn btn-primary" href="<?php echo site_url('products'); ?>">Jelajahi katalog</a>
	</div>
<?php else: ?>
	<div class="table-wrap">
		<table class="data">
			<thead>
				<tr>
					<th>Nomor Pesanan</th>
					<th>Tanggal</th>
					<th>Metode</th>
					<th>Total</th>
					<th>Status</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($orders as $order): ?>
					<tr>
						<td class="cell-title mono"><?php echo html_escape($order['order_number']); ?></td>
						<td><?php echo datetime_id($order['created_at']); ?></td>
						<td><?php echo html_escape(payment_method_label($order['payment_method'])); ?></td>
						<td class="nowrap">Rp <?php echo rupiah($order['total_amount'], FALSE); ?></td>
						<td><?php echo status_badge($order['payment_status']); ?></td>
						<td class="actions">
							<?php if ($order['payment_status'] === 'pending'): ?>
								<a class="btn btn-primary btn-sm" href="<?php echo site_url('checkout/success/'.$order['id']); ?>">Bayar</a>
							<?php endif; ?>
							<a class="btn btn-ghost btn-sm" href="<?php echo site_url('account/order/'.$order['id']); ?>">Detail</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php echo $pagination; ?>
<?php endif; ?>
