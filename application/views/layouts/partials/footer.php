<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
		</div>
	</main>

	<footer class="site-footer">
		<div class="container">
			<div class="footer-grid">
				<div>
					<a class="brand mb-2" href="<?php echo site_url('/'); ?>">
						<span class="brand-mark">M</span>
						<span><?php echo html_escape($store_name); ?></span>
					</a>
					<p class="text-muted"><?php echo html_escape($store_tagline); ?></p>
					<p class="text-dim"><small>Semua produk digital dikirim melalui Library dengan serial number &amp; kuota unduh yang dapat diaudit.</small></p>
				</div>

				<div>
					<h4>Kategori</h4>
					<ul>
						<?php foreach ($nav_categories as $footer_category): ?>
							<li><a href="<?php echo site_url('category/'.$footer_category['slug']); ?>"><?php echo html_escape($footer_category['name']); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div>
					<h4>Akun</h4>
					<ul>
						<?php if ($is_logged_in): ?>
							<li><a href="<?php echo site_url('account'); ?>">Profil Saya</a></li>
							<li><a href="<?php echo site_url('account/orders'); ?>">Riwayat Pesanan</a></li>
							<li><a href="<?php echo site_url('library'); ?>">Digital Library</a></li>
						<?php else: ?>
							<li><a href="<?php echo site_url('login'); ?>">Masuk</a></li>
							<li><a href="<?php echo site_url('register'); ?>">Daftar Akun</a></li>
						<?php endif; ?>
						<li><a href="<?php echo site_url('cart'); ?>">Keranjang</a></li>
					</ul>
				</div>
			</div>

			<div class="footer-bottom">
				<span>Copyright &copy; <?php echo date('Y'); ?> M.o.r.p.h.i.c Hydra x Shiddiq Project</span>
			</div>
		</div>
	</footer>

	<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
</body>
</html>
