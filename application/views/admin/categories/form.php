<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin category create / edit form.
 *
 * @var array|null $category
 * @var bool       $is_edit
 * @var array      $quick_picks
 * @var array      $unsplash_keywords
 */
$value = function ($field, $default = '') use ($category) {
	return set_value($field, $category && isset($category[$field]) && $category[$field] !== NULL ? $category[$field] : $default);
};

$selected = function ($field, $default) use ($category) {
	return set_value($field, $category && isset($category[$field]) ? $category[$field] : $default);
};

$image     = category_image_url($value('image_url'));
$keyword   = isset($unsplash_keywords['default']) ? $unsplash_keywords['default'] : 'digital audio workstation';
$search_url = 'https://unsplash.com/s/photos/'.rawurlencode($keyword);
?>
<div class="card">
	<div class="card-head">
		<h3><?php echo $is_edit ? 'Edit Kategori' : 'Kategori Baru'; ?></h3>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('admin/categories'); ?>">Kembali</a>
	</div>

	<?php echo form_open($is_edit ? 'admin/categories/update/'.$category['id'] : 'admin/categories/store'); ?>
		<div class="grid grid-2">
			<div>
				<div class="form-group">
					<label for="name">Nama Kategori <span class="req">*</span></label>
					<input type="text" id="name" name="name" value="<?php echo html_escape($value('name')); ?>" required data-slug-source>
				</div>

				<div class="form-group">
					<label for="slug">Slug</label>
					<input type="text" id="slug" name="slug" value="<?php echo html_escape($value('slug')); ?>" data-slug-target placeholder="otomatis dari nama">
				</div>

				<div class="form-group">
					<label for="target_group">Target Audiens <span class="req">*</span></label>
					<select id="target_group" name="target_group" required>
						<?php foreach (array('creator', 'musician', 'both') as $group): ?>
							<option value="<?php echo $group; ?>" <?php echo $selected('target_group', 'both') === $group ? 'selected' : ''; ?>><?php echo html_escape(target_group_label($group)); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div>
				<div class="form-group">
					<label for="description">Deskripsi</label>
					<textarea id="description" name="description" rows="6"><?php echo html_escape($value('description')); ?></textarea>
					<div class="form-hint">Muncul pada kartu kategori di halaman beranda.</div>
				</div>

				<label class="checkbox mb-2">
					<input type="checkbox" name="is_active" value="1" <?php echo set_checkbox('is_active', '1', $category ? (int) $category['is_active'] === 1 : TRUE); ?>>
					<span>Tampilkan kategori di storefront</span>
				</label>
			</div>
		</div>

		<hr>

		<!-- ============================ Cover image ============================ -->
		<div class="card-head">
			<h3>Gambar Kategori</h3>
			<span class="badge badge-muted">image_url</span>
		</div>

		<div class="grid grid-2">
			<div>
				<div class="thumb-current mb-2">
					<?php if ($image): ?>
						<img id="category-image-preview" src="<?php echo html_escape($image); ?>" alt="Pratinjau gambar kategori">
						<span class="thumb-preview-empty" id="category-image-empty" style="display:none"></span>
					<?php else: ?>
						<img id="category-image-preview" src="" alt="Pratinjau gambar kategori" style="display:none">
						<span class="thumb-preview-empty" id="category-image-empty">Belum ada gambar</span>
					<?php endif; ?>
				</div>

				<span class="eyebrow">Kurasi cepat Unsplash</span>
				<div class="quick-pick-grid mb-2">
					<?php foreach ($quick_picks as $pick): ?>
						<button type="button" class="quick-pick" data-pick-url="<?php echo html_escape($pick['url']); ?>" data-pick-credit="Photo from Unsplash">
							<img src="<?php echo html_escape($pick['url']); ?>" alt="<?php echo html_escape($pick['label']); ?>" loading="lazy">
							<span><?php echo html_escape($pick['label']); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<a class="btn btn-ghost btn-sm" id="category-unsplash-open" href="<?php echo html_escape($search_url); ?>" target="_blank" rel="noopener">Buka pencarian Unsplash &#8599;</a>
			</div>

			<div>
				<div class="form-group">
					<label for="image_url">URL gambar</label>
					<input type="text" id="image_url" name="image_url" value="<?php echo html_escape($value('image_url')); ?>" placeholder="https://images.unsplash.com/photo-...">
					<div class="form-hint">
						Tempel alamat gambar publik, atau pilih salah satu kurasi cepat di samping.
						Untuk mengambil sendiri: buka <a id="category-unsplash-link" href="<?php echo html_escape($search_url); ?>" target="_blank" rel="noopener">pencarian Unsplash</a>,
						klik kanan pada foto &rarr; <em>Copy image address</em>.
					</div>
				</div>

				<div class="form-group">
					<label for="image_credit">Kredit gambar (opsional)</label>
					<input type="text" id="image_credit" name="image_credit" value="<?php echo html_escape($value('image_credit')); ?>" placeholder="Photo by Jane Doe on Unsplash">
					<div class="form-hint">Ditampilkan kecil di bawah gambar kategori. Terisi otomatis saat memilih dari Unsplash.</div>
				</div>
			</div>
		</div>

		<button type="submit" class="btn btn-primary btn-lg btn-block mt-2">
			<?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Kategori'; ?>
		</button>
	<?php echo form_close(); ?>
</div>

<script>
	(function () {
		'use strict';

		var urlInput      = document.getElementById('image_url');
		var creditInput   = document.getElementById('image_credit');
		var preview       = document.getElementById('category-image-preview');
		var previewEmpty  = document.getElementById('category-image-empty');
		var keywordMap    = <?php echo json_encode($unsplash_keywords); ?>;
		var slugInput     = document.getElementById('slug');
		var nameInput     = document.getElementById('name');
		var openLinks     = [document.getElementById('category-unsplash-open'), document.getElementById('category-unsplash-link')];

		function renderPreview(url) {
			if (!preview || !previewEmpty) {
				return;
			}

			if (url) {
				preview.src = url;
				preview.style.display = '';
				previewEmpty.style.display = 'none';
			} else {
				preview.removeAttribute('src');
				preview.style.display = 'none';
				previewEmpty.style.display = '';
			}
		}

		if (urlInput) {
			urlInput.addEventListener('input', function () {
				renderPreview(urlInput.value.trim());
			});
		}

		document.querySelectorAll('.quick-pick[data-pick-url]').forEach(function (button) {
			button.addEventListener('click', function () {
				if (urlInput) {
					urlInput.value = button.getAttribute('data-pick-url');
				}

				if (creditInput) {
					creditInput.value = button.getAttribute('data-pick-credit') || 'Photo from Unsplash';
				}

				renderPreview(button.getAttribute('data-pick-url'));
			});
		});

		// Keep the Unsplash search links on-topic with the chosen slug.
		function syncSearchLinks() {
			var source = slugInput ? slugInput.value : (nameInput ? nameInput.value : '');
			var term = keywordMap.default || 'digital audio workstation';

			if (source && keywordMap[source]) {
				term = keywordMap[source];
			}

			var href = 'https://unsplash.com/s/photos/' + encodeURIComponent(term);

			openLinks.forEach(function (link) {
				if (link) {
					link.href = href;
				}
			});
		}

		if (slugInput) {
			slugInput.addEventListener('input', syncSearchLinks);
		}

		if (nameInput) {
			nameInput.addEventListener('input', syncSearchLinks);
		}
	})();
</script>
