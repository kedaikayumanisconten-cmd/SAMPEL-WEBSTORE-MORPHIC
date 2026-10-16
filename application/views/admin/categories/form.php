<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin category create / edit form.
 *
 * @var array|null $category
 * @var bool       $is_edit
 */
$value = function ($field, $default = '') use ($category) {
	return set_value($field, $category && isset($category[$field]) && $category[$field] !== NULL ? $category[$field] : $default);
};

$selected = function ($field, $default) use ($category) {
	return set_value($field, $category && isset($category[$field]) ? $category[$field] : $default);
};
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

				<button type="submit" class="btn btn-primary btn-block mt-2">
					<?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Kategori'; ?>
				</button>
			</div>
		</div>
	<?php echo form_close(); ?>
</div>
