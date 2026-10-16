<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin product create / edit form.
 *
 * @var array|null $product
 * @var array      $categories
 * @var bool       $is_edit
 * @var bool       $unsplash_ready
 * @var array      $unsplash_keywords
 * @var array      $quick_picks
 * @var string     $max_upload
 */
$value = function ($field, $default = '') use ($product) {
	return set_value($field, $product && isset($product[$field]) && $product[$field] !== NULL ? $product[$field] : $default);
};

$selected = function ($field, $default) use ($product) {
	$current = set_value($field, $product && isset($product[$field]) ? $product[$field] : $default);

	return $current;
};

$current_thumb  = product_image_url($product && isset($product['thumbnail_url']) ? $product['thumbnail_url'] : set_value('thumbnail_url'));
$current_credit = set_value('thumbnail_credit', $product ? (string) $product['thumbnail_credit'] : '');
$source         = 'upload';

if ($product && ! empty($product['thumbnail_url']))
{
	$source = is_remote_url($product['thumbnail_url']) ? 'url' : 'upload';
}

if (set_value('thumbnail_source') !== '')
{
	$source = set_value('thumbnail_source');
}

$category_slug_keywords = array();

foreach ($unsplash_keywords as $key => $keyword)
{
	$category_slug_keywords[$key] = $keyword;
}
?>
<div class="card">
	<div class="row-between">
		<div>
			<h3 class="mb-1"><?php echo $is_edit ? 'Edit Produk' : 'Produk Baru'; ?></h3>
			<p class="text-muted mb-0"><small>Semua kolom bertanda <span class="req">*</span> wajib diisi.</small></p>
		</div>
		<div class="row">
			<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/products'); ?>">Kembali ke daftar</a>
			<?php if ($is_edit): ?>
				<a class="btn btn-ghost btn-sm" href="<?php echo site_url('product/'.$product['slug']); ?>" target="_blank" rel="noopener">Lihat di toko &#8599;</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
$action = $is_edit ? 'admin/products/update/'.$product['id'] : 'admin/products/store';
echo form_open_multipart($action);
?>
	<div class="grid grid-2">
		<!-- ============================ LEFT: details ============================ -->
		<div>
			<div class="card">
				<div class="card-head"><h3>Informasi dasar</h3></div>

				<div class="form-group">
					<label for="title">Judul Produk <span class="req">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo html_escape($value('title')); ?>" required data-slug-source>
				</div>

				<div class="form-group">
					<label for="slug">Slug URL</label>
					<input type="text" id="slug" name="slug" value="<?php echo html_escape($value('slug')); ?>" data-slug-target placeholder="otomatis dari judul">
					<div class="form-hint">Digunakan pada alamat produk, contoh <span class="mono">/product/nama-produk</span>. Biarkan kosong untuk dibuat otomatis.</div>
				</div>

				<div class="form-row">
					<div class="form-group">
						<label for="category_id">Kategori <span class="req">*</span></label>
						<select id="category_id" name="category_id" required>
							<option value="">Pilih kategori</option>
							<?php foreach ($categories as $id => $name): ?>
								<option value="<?php echo (int) $id; ?>" <?php echo (int) $selected('category_id', 0) === (int) $id ? 'selected' : ''; ?>><?php echo html_escape($name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label for="price">Harga (Rp) <span class="req">*</span></label>
						<input type="number" id="price" name="price" value="<?php echo html_escape($value('price', '0')); ?>" min="0" step="1000" required>
					</div>
				</div>

				<div class="form-group">
					<label for="short_description">Deskripsi Singkat <span class="req">*</span></label>
					<input type="text" id="short_description" name="short_description" value="<?php echo html_escape($value('short_description')); ?>" maxlength="255" required>
					<div class="form-hint">Tampil pada kartu produk di katalog. Maksimal 255 karakter.</div>
				</div>

				<div class="form-group">
					<label for="description">Deskripsi Lengkap <span class="req">*</span></label>
					<textarea id="description" name="description" required><?php echo html_escape($value('description')); ?></textarea>
				</div>
			</div>

			<div class="card">
				<div class="card-head"><h3>Teknis &amp; kompatibilitas</h3></div>

				<div class="form-row-3">
					<div class="form-group">
						<label for="version">Versi <span class="req">*</span></label>
						<input type="text" id="version" name="version" value="<?php echo html_escape($value('version', '1.0.0')); ?>" required>
					</div>

					<div class="form-group">
						<label for="os_support">Dukungan OS <span class="req">*</span></label>
						<select id="os_support" name="os_support" required>
							<?php foreach (array('windows', 'macos', 'both', 'cross_platform') as $os): ?>
								<option value="<?php echo $os; ?>" <?php echo $selected('os_support', 'both') === $os ? 'selected' : ''; ?>><?php echo html_escape(os_label($os)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label for="file_size_mb">Ukuran File (MB) <span class="req">*</span></label>
						<input type="number" id="file_size_mb" name="file_size_mb" value="<?php echo html_escape($value('file_size_mb', '0')); ?>" min="0" step="0.01" required>
					</div>
				</div>

				<div class="form-group">
					<label for="file_format">Format File <span class="req">*</span></label>
					<input type="text" id="file_format" name="file_format" value="<?php echo html_escape($value('file_format')); ?>" placeholder="VST3, AU (64-bit) &middot; .cube 33x33 &middot; WAV 24-bit" required>
				</div>

				<div class="form-group">
					<label for="system_requirements">Kebutuhan Sistem</label>
					<textarea id="system_requirements" name="system_requirements" rows="4"><?php echo html_escape($value('system_requirements')); ?></textarea>
				</div>
			</div>

			<div class="card">
				<div class="card-head"><h3>File produk &amp; preview</h3></div>

				<div class="form-group">
					<label for="file_path">Path File Produk (protected) <span class="req">*</span></label>
					<input type="text" id="file_path" name="file_path" value="<?php echo html_escape($value('file_path', 'storage/private_products/')); ?>" required>
					<div class="form-hint">
						File asli disimpan di luar akses publik dan hanya keluar melalui token unduhan.
						Upload langsung dibatasi <span class="mono"><?php echo html_escape($max_upload); ?></span> oleh php.ini — untuk installer besar cukup tempel path-nya secara manual.
					</div>
				</div>

				<div class="form-group">
					<label for="installer_file">Upload installer (opsional)</label>
					<input type="file" id="installer_file" name="installer_file" data-filename-target="#installer_file_name">
					<div class="form-hint mono" id="installer_file_name"></div>
				</div>

				<div class="form-row">
					<div class="form-group">
						<label for="preview_type">Tipe Preview</label>
						<select id="preview_type" name="preview_type">
							<?php foreach (array('none', 'audio', 'video', 'image') as $type): ?>
								<option value="<?php echo $type; ?>" <?php echo $selected('preview_type', 'none') === $type ? 'selected' : ''; ?>><?php echo html_escape(preview_type_label($type)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label for="preview_url">URL Preview</label>
						<input type="text" id="preview_url" name="preview_url" value="<?php echo html_escape($value('preview_url')); ?>" placeholder="https://... atau assets/uploads/previews/demo.mp3">
					</div>
				</div>
			</div>
		</div>

		<!-- ============================ RIGHT: photo + status ============================ -->
		<div>
			<div class="card" id="thumb-picker">
				<div class="card-head">
					<h3>Foto Produk</h3>
					<span class="badge badge-muted">thumbnail_url</span>
				</div>

				<div class="thumb-current mb-2">
					<?php if ($current_thumb): ?>
						<img id="thumb-preview" src="<?php echo html_escape($current_thumb); ?>" alt="Pratinjau gambar produk">
						<span class="thumb-preview-empty" id="thumb-preview-empty" style="display:none"></span>
					<?php else: ?>
						<img id="thumb-preview" src="" alt="Pratinjau gambar produk" style="display:none">
						<span class="thumb-preview-empty" id="thumb-preview-empty">Belum ada gambar</span>
					<?php endif; ?>
				</div>

				<div class="chip-row mb-2">
					<?php
					$sources = array(
						'upload'   => 'Upload File',
						'url'      => 'Dari URL',
						'unsplash' => 'Unsplash',
						'none'     => 'Tanpa Gambar',
					);
					?>
					<?php foreach ($sources as $key => $label): ?>
						<label class="chip <?php echo $source === $key ? 'is-active' : ''; ?>" style="cursor:pointer">
							<input type="radio" name="thumbnail_source" value="<?php echo $key; ?>" <?php echo $source === $key ? 'checked' : ''; ?> style="width:auto;margin-right:6px">
							<?php echo html_escape($label); ?>
						</label>
					<?php endforeach; ?>
				</div>

				<!-- Panel: upload -->
				<div data-panel="upload">
					<div class="form-group">
						<label for="thumbnail_file">File gambar</label>
						<input type="file" id="thumbnail_file" name="thumbnail_file" accept="image/png,image/jpeg,image/webp,image/gif,image/avif">
						<div class="form-hint">JPG, PNG, WEBP, GIF, atau AVIF. Maksimal 4 MB. Disimpan di <span class="mono">assets/uploads/thumbnails/</span>.</div>
						<div class="form-hint mono" id="thumbnail_file_name"></div>
					</div>
				</div>

				<!-- Panel: URL -->
				<div data-panel="url">
					<div class="form-group">
						<label for="thumbnail_url">URL gambar</label>
						<input type="text" id="thumbnail_url" name="thumbnail_url" value="<?php echo html_escape($value('thumbnail_url')); ?>" placeholder="https://images.unsplash.com/photo-...">
						<div class="form-hint">
							Tempel alamat gambar publik. Untuk mengambil dari Unsplash: buka
							<a id="unsplash-open" href="https://unsplash.com/s/photos/digital-audio-workstation" target="_blank" rel="noopener">pencarian Unsplash</a>,
							klik kanan pada foto &rarr; <em>Copy image address</em>.
						</div>
					</div>
				</div>

				<!-- Panel: Unsplash -->
				<div data-panel="unsplash">
					<?php if ( ! $unsplash_ready): ?>
						<div class="alert alert-info">
							<span class="alert-icon">&#8505;</span>
							<span>
								Unsplash Access Key belum diisi. Tambahkan pada
								<span class="mono">application/config/unsplash.php</span> untuk mengaktifkan pencarian otomatis.
								Tanpa key, gunakan tombol di bawah lalu tempel URL gambar pada tab <strong>Dari URL</strong>.
								Key gratis dibuat di <a href="https://unsplash.com/oauth/applications" target="_blank" rel="noopener">unsplash.com/oauth/applications</a>.
							</span>
						</div>
					<?php endif; ?>

					<div class="form-group">
						<label for="unsplash-keyword">Kata kunci pencarian</label>
						<div class="row">
							<input type="text" id="unsplash-keyword" value="<?php echo html_escape(reset($unsplash_keywords)); ?>" placeholder="synthesizer music studio">
							<button type="button" class="btn btn-primary nowrap" id="unsplash-search">Cari</button>
						</div>
						<div class="form-hint">
							Kata kunci saran per kategori:
							<?php foreach ($unsplash_keywords as $key => $keyword): ?>
								<button type="button" class="chip" style="cursor:pointer" onclick="document.getElementById('unsplash-keyword').value='<?php echo html_escape($keyword); ?>'"><?php echo html_escape($keyword); ?></button>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="form-hint" id="unsplash-status" style="min-height:20px"></div>
					<div id="unsplash-results" class="mb-2"></div>

					<span class="eyebrow">Kurasi cepat (tanpa API key)</span>
					<div class="quick-pick-grid">
						<?php foreach ($quick_picks as $pick): ?>
							<button type="button" class="quick-pick" data-pick-url="<?php echo html_escape($pick['url']); ?>" data-pick-credit="Photo from Unsplash">
								<img src="<?php echo html_escape($pick['url']); ?>" alt="<?php echo html_escape($pick['label']); ?>" loading="lazy">
								<span><?php echo html_escape($pick['label']); ?></span>
							</button>
						<?php endforeach; ?>
					</div>

					<a class="btn btn-ghost btn-sm mt-2" id="unsplash-open-2" href="https://unsplash.com" target="_blank" rel="noopener">Buka Unsplash &#8599;</a>
				</div>

				<div data-panel="none">
					<p class="text-muted mb-0"><small>Produk akan memakai placeholder otomatis berisi inisial judul produk.</small></p>
				</div>

				<div class="form-group mt-2">
					<label for="thumbnail_credit">Kredit gambar (opsional)</label>
					<input type="text" id="thumbnail_credit" name="thumbnail_credit" value="<?php echo html_escape($current_credit); ?>" placeholder="Photo by Jane Doe on Unsplash">
					<div class="form-hint">Ditampilkan kecil di bawah foto pada halaman produk. Terisi otomatis saat memilih dari Unsplash.</div>
				</div>
			</div>

			<div class="card">
				<div class="card-head"><h3>Status &amp; lisensi</h3></div>

				<label class="checkbox mb-2">
					<input type="checkbox" name="is_active" value="1" <?php echo set_checkbox('is_active', '1', $product ? (int) $product['is_active'] === 1 : TRUE); ?>>
					<span>Tampilkan produk di storefront</span>
				</label>

				<label class="checkbox mb-2">
					<input type="checkbox" name="is_featured" value="1" <?php echo set_checkbox('is_featured', '1', $product ? (int) $product['is_featured'] === 1 : FALSE); ?>>
					<span>Tandai sebagai produk unggulan</span>
				</label>

				<label class="checkbox mb-2">
					<input type="checkbox" name="requires_license_key" value="1" <?php echo set_checkbox('requires_license_key', '1', $product ? (int) $product['requires_license_key'] === 1 : TRUE); ?>>
					<span>Terbitkan serial number untuk setiap pembeli</span>
				</label>

				<button type="submit" class="btn btn-primary btn-lg btn-block mt-2">
					<?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Produk'; ?>
				</button>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>

<script>
	window.MORPHIC_THUMB = {
		searchUrl: '<?php echo site_url('admin/products/unsplash_search'); ?>',
		csrfName: '<?php echo $this->config->item('csrf_token_name'); ?>',
		csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>'
	};
</script>
<script src="<?php echo base_url('assets/js/thumb-picker.js'); ?>"></script>
