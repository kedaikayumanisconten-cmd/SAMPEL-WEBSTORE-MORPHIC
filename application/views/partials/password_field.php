<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password input with a reveal ("eye") toggle.
 *
 * @var string $password_id          Input id (also targeted by the toggle).
 * @var string $password_name        Input name.
 * @var string $password_placeholder Placeholder text.
 * @var string $password_autocomplete Optional autocomplete hint.
 * @var bool   $password_required    Whether to add the required attribute.
 */
$password_id           = isset($password_id) ? $password_id : 'password';
$password_name         = isset($password_name) ? $password_name : 'password';
$password_placeholder  = isset($password_placeholder) ? $password_placeholder : '';
$password_autocomplete = isset($password_autocomplete) ? $password_autocomplete : '';
$password_required     = ! isset($password_required) || $password_required;
?>
<div class="input-with-toggle">
	<input type="password" id="<?php echo html_escape($password_id); ?>" name="<?php echo html_escape($password_name); ?>" placeholder="<?php echo html_escape($password_placeholder); ?>"<?php echo $password_autocomplete !== '' ? ' autocomplete="'.html_escape($password_autocomplete).'"' : ''; ?><?php echo $password_required ? ' required' : ''; ?>>
	<button type="button" class="password-toggle" data-password-toggle="<?php echo html_escape($password_id); ?>" aria-label="Tampilkan password" aria-pressed="false">
		<svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"></path>
			<circle cx="12" cy="12" r="3"></circle>
		</svg>
		<svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"></path>
		</svg>
	</button>
</div>
