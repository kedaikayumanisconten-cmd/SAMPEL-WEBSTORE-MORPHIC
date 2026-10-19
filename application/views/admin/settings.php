<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin site settings: branding, logo, contact, hero/about copy and footer.
 *
 * @var array       $groups      Grouped text field definitions.
 * @var array       $values      Current effective values (key => value).
 * @var string      $logo_value  Stored logo reference.
 * @var string|null $logo_url    Renderable logo URL, or NULL.
 * @var string      $upload_path Upload directory for the logo.
 */
$val = function ($field) use ($values) {
	return set_value($field, isset($values[$field]) ? $values[$field] : '', FALSE);
};
?>
<div class="card mb-3">
	<div class="row-between">
		<div>
			<h3 class="mb-1">Pengaturan Situs</h3>
			<p class="text-muted mb-0"><small>Ubah identitas, logo, kontak, teks hero, tentang, dan footer tanpa menyentuh kode. Perubahan langsung tampil di storefront.</small></p>
		</div>
		<a class="btn btn-ghost btn-sm" href="<?php echo site_url('/'); ?>" target="_blank" rel="noopener">Lihat Storefront &#8599;</a>
	</div>
</div>

<?php echo form_open_multipart('admin/settings/save'); ?>

	<!-- ============================ Logo ============================ -->
	<div class="card mb-3">
		<div class="card-head">
			<h3>Logo Toko</h3>
			<span class="badge badge-muted">brand</span>
		</div>

		<div class="grid grid-2">
			<div>
				<div class="thumb-current mb-2">
					<?php if ($logo_url): ?>
						<img src="<?php echo html_escape($logo_url); ?>" alt="Logo saat ini" style="background:#fff">
					<?php else: ?>
						<span class="thumb-preview-empty">Belum ada logo — memakai huruf awal merek</span>
					<?php endif; ?>
				</div>
				<p class="text-muted mb-0"><small>Jika kosong, header akan memakai lambang huruf (mis. <span class="mono">M</span>).</small></p>
			</div>

			<div>
				<div class="form-group">
					<label for="logo_file">Upload logo baru</label>
					<input type="file" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif,image/avif">
					<div class="form-hint">PNG, JPG, WEBP, GIF, atau AVIF. Maksimal 2 MB. Disimpan di <span class="mono"><?php echo html_escape($upload_path); ?></span>.</div>
				</div>

				<div class="form-group">
					<label for="logo_url">Atau tempel URL logo</label>
					<input type="text" id="logo_url" name="logo_url" value="<?php echo html_escape($logo_value); ?>" placeholder="https://... atau <?php echo html_escape($upload_path); ?>logo.png">
					<div class="form-hint">Kosongkan dan biarkan untuk tetap memakai logo saat ini.</div>
				</div>

				<?php if ($logo_value !== ''): ?>
					<label class="checkbox">
						<input type="checkbox" name="remove_logo" value="1">
						<span>Hapus logo dan kembali ke lambang huruf</span>
					</label>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- ============================ Text groups ============================ -->
	<?php foreach ($groups as $group): ?>
		<div class="card mb-3">
			<div class="card-head"><h3><?php echo html_escape($group['title']); ?></h3></div>

			<?php foreach ($group['fields'] as $field): ?>
				<div class="form-group">
					<label for="<?php echo html_escape($field['key']); ?>">
						<?php echo html_escape($field['label']); ?>
						<?php if ( ! empty($field['required'])): ?><span class="req">*</span><?php endif; ?>
					</label>

					<?php if ($field['type'] === 'textarea'): ?>
						<textarea id="<?php echo html_escape($field['key']); ?>" name="<?php echo html_escape($field['key']); ?>" rows="<?php echo (int) (isset($field['rows']) ? $field['rows'] : 3); ?>"><?php echo html_escape($val($field['key'])); ?></textarea>
					<?php else: ?>
						<input
							type="<?php echo html_escape($field['type']); ?>"
							id="<?php echo html_escape($field['key']); ?>"
							name="<?php echo html_escape($field['key']); ?>"
							value="<?php echo html_escape($val($field['key'])); ?>"
							<?php echo ! empty($field['max']) ? 'maxlength="'.(int) $field['max'].'"' : ''; ?>
							<?php echo ! empty($field['required']) ? 'required' : ''; ?>
						>
					<?php endif; ?>

					<?php if ( ! empty($field['hint'])): ?>
						<div class="form-hint"><?php echo $field['hint']; ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<div class="card">
		<button type="submit" class="btn btn-primary btn-lg">Simpan Pengaturan</button>
	</div>

<?php echo form_close(); ?>
