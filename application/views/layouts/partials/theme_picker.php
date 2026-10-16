<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Theme picker.
 *
 * The available palettes are defined as CSS token overrides in style.css
 * (section 1b). The current selection is stored in localStorage under
 * "morphic-theme" and applied to <html> before paint by the inline script in
 * the page <head>, then kept in sync here by initThemePicker() in app.js.
 */
$themes = array(
	array('id' => 'morphic',  'name' => 'Morphic',  'sw1' => '#F55D2C', 'sw2' => '#FFFFFF'),
	array('id' => 'midnight', 'name' => 'Midnight', 'sw1' => '#FF6A3D', 'sw2' => '#151926'),
	array('id' => 'emerald',  'name' => 'Emerald',  'sw1' => '#10B981', 'sw2' => '#ECFDF5'),
	array('id' => 'royal',    'name' => 'Royal',    'sw1' => '#4F46E5', 'sw2' => '#EEF2FF'),
	array('id' => 'rose',     'name' => 'Rose',     'sw1' => '#F43F5E', 'sw2' => '#FFF1F5'),
	array('id' => 'ocean',    'name' => 'Ocean',    'sw1' => '#06B6D4', 'sw2' => '#ECFEFF'),
);
?>
<div class="theme-picker" data-theme-picker>
	<button type="button" class="theme-toggle" data-theme-toggle aria-haspopup="true" aria-expanded="false" aria-label="Ganti tema tampilan" title="Ganti tema tampilan">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<circle cx="13.5" cy="6.5" r="1.3"></circle>
			<circle cx="17.5" cy="10.5" r="1.3"></circle>
			<circle cx="8.5" cy="7.5" r="1.3"></circle>
			<circle cx="6.5" cy="12.5" r="1.3"></circle>
			<path d="M12 2a10 10 0 0 0 0 20 2.5 2.5 0 0 0 2.5-2.5c0-.7-.3-1.3-.7-1.8-.4-.4-.6-1-.6-1.6 0-1.1.9-2 2-2H17a5 5 0 0 0 5-5c0-3.9-4.5-7-10-7Z"></path>
		</svg>
	</button>

	<div class="theme-menu" data-theme-menu role="menu" aria-label="Pilih tema tampilan">
		<span class="theme-menu-title">Tema Tampilan</span>
		<?php foreach ($themes as $theme): ?>
			<button type="button" class="theme-option" data-theme-value="<?php echo html_escape($theme['id']); ?>" role="menuitemradio" aria-checked="false">
				<span class="theme-swatch" style="--sw1: <?php echo $theme['sw1']; ?>; --sw2: <?php echo $theme['sw2']; ?>;"></span>
				<span class="theme-name"><?php echo html_escape($theme['name']); ?></span>
				<span class="theme-check" aria-hidden="true">&#10003;</span>
			</button>
		<?php endforeach; ?>
	</div>
</div>
