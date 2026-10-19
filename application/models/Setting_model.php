<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setting_model
 *
 * Key/value store for the editable storefront content (branding, logo,
 * contact details, hero/about copy, footer text). Values are read once per
 * request and merged over the defaults from application/config/morphic.php
 * by MY_Controller::_apply_site_settings().
 */
class Setting_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'site_settings';

	/**
	 * Per-request cache of the stored key/value pairs, or NULL when not
	 * loaded yet.
	 *
	 * @var array|null
	 */
	protected $values = NULL;

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------

	/**
	 * Every stored setting as key => value.
	 *
	 * @return array
	 */
	public function all()
	{
		if ($this->values !== NULL)
		{
			return $this->values;
		}

		$this->values = array();

		if ( ! $this->table_ready())
		{
			return $this->values;
		}

		$rows = $this->db->get($this->table)->result_array();

		foreach ($rows as $row)
		{
			// Stored values are plain text. Decode any HTML entities so older
			// rows that were saved pre-escaped (e.g. "&amp;") render once,
			// not as literal entity text after the view escapes them again.
			$this->values[$row['setting_key']] = $row['setting_value'] === NULL
				? NULL
				: html_entity_decode((string) $row['setting_value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		return $this->values;
	}

	/**
	 * Single setting with an optional fallback.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = NULL)
	{
		$all = $this->all();

		return (array_key_exists($key, $all) && $all[$key] !== NULL) ? $all[$key] : $default;
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Upsert a single setting.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return bool
	 */
	public function set($key, $value)
	{
		return $this->set_many(array($key => $value));
	}

	/**
	 * Upsert several settings in one round trip.
	 *
	 * @param  array $pairs key => value
	 * @return bool
	 */
	public function set_many(array $pairs)
	{
		if ( ! $this->table_ready())
		{
			return FALSE;
		}

		foreach ($pairs as $key => $value)
		{
			// Keep the database plain-text so a single html_escape() at the
			// output layer is always enough, even if an admin pastes an
			// entity such as "&amp;" into a settings field.
			if ($value !== NULL)
			{
				$value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}

			$this->db->query(
				'INSERT INTO `'.$this->table.'` (`setting_key`, `setting_value`, `updated_at`) '
				.'VALUES (?, ?, NOW()) '
				.'ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()',
				array((string) $key, $value)
			);
		}

		// Force a fresh read on the next all()/get() call.
		$this->values = NULL;

		return TRUE;
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Make sure the backing table exists. New installs get it from
	 * database.sql; the CREATE IF NOT EXISTS here lets an existing install
	 * start using the feature without re-importing the whole schema.
	 *
	 * @return bool
	 */
	protected function table_ready()
	{
		if ($this->db->table_exists($this->table))
		{
			return TRUE;
		}

		$this->db->query(
			'CREATE TABLE IF NOT EXISTS `'.$this->table.'` ('
			.'`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, '
			.'`setting_key` VARCHAR(100) NOT NULL, '
			.'`setting_value` TEXT NULL, '
			.'`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
			.'PRIMARY KEY (`id`), '
			.'UNIQUE KEY `idx_site_settings_key` (`setting_key`)'
			.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		return $this->db->table_exists($this->table);
	}
}
