<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * M.O.R.P.H.I.C view helpers.
 *
 * Small, presentation focused helpers shared by the storefront and the admin
 * panel. Kept deliberately free of business logic.
 */

if ( ! function_exists('rupiah'))
{
	/**
	 * Format a numeric amount as Indonesian Rupiah.
	 *
	 * @param  float|string $amount
	 * @param  bool         $with_prefix
	 * @return string
	 */
	function rupiah($amount, $with_prefix = TRUE)
	{
		$formatted = number_format((float) $amount, 0, ',', '.');

		return $with_prefix ? 'Rp '.$formatted : $formatted;
	}
}

if ( ! function_exists('rupiah_short'))
{
	/**
	 * Compact Rupiah formatter, e.g. 450.000 -> "Rp 450rb".
	 *
	 * @param  float|string $amount
	 * @return string
	 */
	function rupiah_short($amount)
	{
		$amount = (float) $amount;

		if ($amount >= 1000000)
		{
			return 'Rp '.(rtrim(rtrim(number_format($amount / 1000000, 1, ',', '.'), '0'), ',')).'jt';
		}

		if ($amount >= 1000)
		{
			return 'Rp '.(rtrim(rtrim(number_format($amount / 1000, 0, ',', '.')), '.')).'rb';
		}

		return 'Rp '.number_format($amount, 0, ',', '.');
	}
}

if ( ! function_exists('asset_exists'))
{
	/**
	 * Check whether a repository relative asset path exists on disk.
	 *
	 * @param  string|null $path
	 * @return bool
	 */
	function asset_exists($path)
	{
		return ! empty($path) && is_file(FCPATH.$path);
	}
}

if ( ! function_exists('asset_url'))
{
	/**
	 * Absolute URL of a repository relative asset.
	 *
	 * @param  string $path
	 * @return string
	 */
	function asset_url($path)
	{
		return base_url($path);
	}
}

if ( ! function_exists('asset_or_null'))
{
	/**
	 * Return base_url() of an asset when it exists, otherwise NULL so the
	 * view can fall back to a generated placeholder.
	 *
	 * @param  string|null $path
	 * @return string|null
	 */
	function asset_or_null($path)
	{
		return asset_exists($path) ? base_url($path) : NULL;
	}
}

if ( ! function_exists('is_remote_url'))
{
	/**
	 * Does the value look like an absolute http(s) URL?
	 *
	 * @param  string|null $value
	 * @return bool
	 */
	function is_remote_url($value)
	{
		return is_string($value) && preg_match('#^https?://#i', trim($value)) === 1;
	}
}

if ( ! function_exists('whatsapp_number'))
{
	/**
	 * Digits-only WhatsApp number in international format (no "+" or
	 * separators), ready to be embedded in a wa.me link.
	 *
	 * @return string
	 */
	function whatsapp_number()
	{
		$ci =& get_instance();

		return preg_replace('/\D+/', '', (string) $ci->config->item('store_whatsapp'));
	}
}

if ( ! function_exists('whatsapp_format'))
{
	/**
	 * Human readable representation of any international WhatsApp number,
	 * e.g. "+62 852-8885-5892".
	 *
	 * @param  string $number
	 * @return string
	 */
	function whatsapp_format($number)
	{
		$number = preg_replace('/\D+/', '', (string) $number);

		if ($number === '')
		{
			return '';
		}

		if (strpos($number, '62') === 0)
		{
			$rest   = substr($number, 2);
			$groups = array();

			if (strlen($rest) > 3)
			{
				$groups[] = substr($rest, 0, 3);
				$rest     = substr($rest, 3);
			}

			while (strlen($rest) > 4)
			{
				$groups[] = substr($rest, 0, 4);
				$rest     = substr($rest, 4);
			}

			if ($rest !== '')
			{
				$groups[] = $rest;
			}

			return '+62 '.implode('-', $groups);
		}

		return '+'.$number;
	}
}

if ( ! function_exists('whatsapp_display'))
{
	/**
	 * Human readable representation of the configured owner WhatsApp
	 * number, e.g. "+62 852-8885-5892".
	 *
	 * @return string
	 */
	function whatsapp_display()
	{
		return whatsapp_format(whatsapp_number());
	}
}

if ( ! function_exists('whatsapp_link_to'))
{
	/**
	 * wa.me deep link for an arbitrary WhatsApp number, optionally
	 * prefilled with a chat message.
	 *
	 * @param  string $number
	 * @param  string $message
	 * @return string
	 */
	function whatsapp_link_to($number, $message = '')
	{
		$number = preg_replace('/\D+/', '', (string) $number);

		if ($number === '')
		{
			return '';
		}

		$link = 'https://wa.me/'.$number;

		if ($message !== '')
		{
			$link .= '?text='.rawurlencode($message);
		}

		return $link;
	}
}

if ( ! function_exists('whatsapp_link'))
{
	/**
	 * wa.me deep link for the configured owner number, optionally prefilled
	 * with a chat message.
	 *
	 * @param  string $message
	 * @return string
	 */
	function whatsapp_link($message = '')
	{
		return whatsapp_link_to(whatsapp_number(), $message);
	}
}

if ( ! function_exists('product_image_url'))
{
	/**
	 * Resolve a product image reference into a renderable URL.
	 *
	 * Accepts either:
	 *  - a repository relative path, e.g. assets/uploads/thumbnails/a.jpg
	 *  - an absolute external URL, e.g. an Unsplash image address
	 *
	 * @param  string|null $value
	 * @return string|null NULL when there is nothing usable to render.
	 */
	function product_image_url($value)
	{
		if (empty($value))
		{
			return NULL;
		}

		if (is_remote_url($value))
		{
			return trim($value);
		}

		return asset_or_null($value);
	}
}

if ( ! function_exists('category_image_url'))
{
	/**
	 * Resolve a category cover reference into a renderable URL.
	 *
	 * Same rules as product_image_url(): a repository relative asset path or
	 * an absolute external URL (e.g. an Unsplash image address).
	 *
	 * @param  string|null $value
	 * @return string|null NULL when there is nothing usable to render.
	 */
	function category_image_url($value)
	{
		return product_image_url($value);
	}
}

if ( ! function_exists('brand_logo_url'))
{
	/**
	 * Resolve the configured store logo into a renderable URL. Accepts the
	 * same reference shapes as product_image_url() and returns NULL when no
	 * logo is set, so views can fall back to the letter mark.
	 *
	 * @param  string|null $value
	 * @return string|null
	 */
	function brand_logo_url($value)
	{
		return product_image_url($value);
	}
}

if ( ! function_exists('preview_media_url'))
{
	/**
	 * Resolve a preview (audio/video/image) reference into a URL.
	 *
	 * @param  string|null $value
	 * @return string|null
	 */
	function preview_media_url($value)
	{
		if (empty($value))
		{
			return NULL;
		}

		if (is_remote_url($value))
		{
			return trim($value);
		}

		return asset_or_null($value);
	}
}

if ( ! function_exists('os_label'))
{
	/**
	 * Human readable OS support label.
	 *
	 * @param  string $os
	 * @return string
	 */
	function os_label($os)
	{
		$labels = array(
			'windows'        => 'Windows',
			'macos'          => 'macOS',
			'both'           => 'Windows & macOS',
			'cross_platform' => 'Cross Platform',
		);

		return isset($labels[$os]) ? $labels[$os] : ucfirst((string) $os);
	}
}

if ( ! function_exists('target_group_label'))
{
	/**
	 * Human readable audience label.
	 *
	 * @param  string $group
	 * @return string
	 */
	function target_group_label($group)
	{
		$labels = array(
			'creator'  => 'Kreator Konten',
			'musician' => 'Musisi & Produser',
			'both'     => 'Kreator & Musisi',
		);

		return isset($labels[$group]) ? $labels[$group] : ucfirst((string) $group);
	}
}

if ( ! function_exists('payment_method_label'))
{
	/**
	 * Human readable payment method label.
	 *
	 * @param  string $method
	 * @return string
	 */
	function payment_method_label($method)
	{
		$ci =& get_instance();
		$methods = $ci->config->item('payment_methods');

		return isset($methods[$method]) ? $methods[$method] : ucwords(str_replace('_', ' ', (string) $method));
	}
}

if ( ! function_exists('status_badge'))
{
	/**
	 * Render a payment status pill.
	 *
	 * @param  string $status pending|paid|failed|cancelled
	 * @return string
	 */
	function status_badge($status)
	{
		$map = array(
			'pending'   => array('badge-warning', 'Menunggu Pembayaran'),
			'paid'      => array('badge-success', 'Lunas'),
			'failed'    => array('badge-danger', 'Gagal'),
			'cancelled' => array('badge-muted', 'Dibatalkan'),
		);

		$badge = isset($map[$status]) ? $map[$status] : array('badge-muted', ucfirst((string) $status));

		return '<span class="badge ' . $badge[0] . '">' . html_escape($badge[1]) . '</span>';
	}
}

if ( ! function_exists('all_roles'))
{
	/**
	 * Every role in the system, ordered from the highest privilege down.
	 *
	 * @return array
	 */
	function all_roles()
	{
		return array('owner', 'super_admin', 'admin', 'customer');
	}
}

if ( ! function_exists('role_level'))
{
	/**
	 * Numeric weight of a role. A higher number means more privileges.
	 *
	 * owner (4) > super_admin (3) > admin (2) > customer (1)
	 *
	 * @param  string|null $role
	 * @return int
	 */
	function role_level($role)
	{
		switch ((string) $role)
		{
			case 'owner':
				return 4;
			case 'super_admin':
				return 3;
			case 'admin':
				return 2;
			case 'customer':
				return 1;
		}

		return 0;
	}
}

if ( ! function_exists('is_staff_role'))
{
	/**
	 * Does this role get access to the admin panel?
	 *
	 * @param  string|null $role
	 * @return bool
	 */
	function is_staff_role($role)
	{
		return role_level($role) >= 2;
	}
}

if ( ! function_exists('role_label'))
{
	/**
	 * Human readable role name.
	 *
	 * @param  string|null $role
	 * @return string
	 */
	function role_label($role)
	{
		switch ((string) $role)
		{
			case 'owner':
				return 'Owner';
			case 'super_admin':
				return 'Super Admin';
			case 'admin':
				return 'Admin';
		}

		return 'Customer';
	}
}

if ( ! function_exists('can_manage_role'))
{
	/**
	 * Can an actor holding $actor_role manage a target holding $target_role?
	 *
	 * A member may handle accounts at or below their own level, never above
	 * (so an admin can never touch a super_admin or an owner). The acting
	 * account itself is always manageable.
	 *
	 * @param  string|null $actor_role
	 * @param  string|null $target_role
	 * @return bool
	 */
	function can_manage_role($actor_role, $target_role)
	{
		return role_level($target_role) <= role_level($actor_role);
	}
}

if ( ! function_exists('assignable_roles'))
{
	/**
	 * Roles an actor is allowed to hand out: at or below their own level.
	 *
	 * @param  string|null $actor_role
	 * @return array
	 */
	function assignable_roles($actor_role)
	{
		$out = array();

		foreach (all_roles() as $candidate)
		{
			if (can_manage_role($actor_role, $candidate))
			{
				$out[] = $candidate;
			}
		}

		return $out;
	}
}

if ( ! function_exists('role_badge'))
{
	/**
	 * Render a user role pill.
	 *
	 * @param  string $role
	 * @return string
	 */
	function role_badge($role)
	{
		$classes = array(
			'owner'       => 'badge-danger',
			'super_admin' => 'badge-warning',
			'admin'       => 'badge-accent',
			'customer'    => 'badge-muted',
		);

		$role  = (string) $role;
		$class = isset($classes[$role]) ? $classes[$role] : 'badge-muted';

		return '<span class="badge '.$class.'">'.html_escape(role_label($role)).'</span>';
	}
}

if ( ! function_exists('file_size_label'))
{
	/**
	 * Turn a MB value into a readable size label.
	 *
	 * @param  float $mb
	 * @return string
	 */
	function file_size_label($mb)
	{
		$mb = (float) $mb;

		if ($mb >= 1024)
		{
			return number_format($mb / 1024, 2, ',', '.').' GB';
		}

		return number_format($mb, 2, ',', '.').' MB';
	}
}

if ( ! function_exists('excerpt'))
{
	/**
	 * Trim a string to a maximum length with an ellipsis.
	 *
	 * @param  string $text
	 * @param  int    $limit
	 * @return string
	 */
	function excerpt($text, $limit = 120)
	{
		return character_limiter(strip_tags((string) $text), $limit, '…');
	}
}

if ( ! function_exists('initials_of'))
{
	/**
	 * Two letter initials for avatar placeholders.
	 *
	 * @param  string $name
	 * @return string
	 */
	function initials_of($name)
	{
		$parts = preg_split('/\s+/', trim((string) $name));
		$initials = '';

		foreach ($parts as $part)
		{
			if ($part === '')
			{
				continue;
			}

			$initials .= mb_strtoupper(mb_substr($part, 0, 1));

			if (mb_strlen($initials) >= 2)
			{
				break;
			}
		}

		return $initials !== '' ? $initials : 'MX';
	}
}

if ( ! function_exists('avatar_url'))
{
	/**
	 * Resolve a stored profile photo reference into a renderable URL.
	 *
	 * Accepts a repository relative path (assets/uploads/avatars/...) or an
	 * absolute external URL, and returns NULL when there is nothing to show
	 * so the view can fall back to the initials placeholder.
	 *
	 * @param  string|null $value
	 * @return string|null
	 */
	function avatar_url($value)
	{
		return product_image_url($value);
	}
}

if ( ! function_exists('save_user_avatar'))
{
	/**
	 * Handle a profile photo upload for the current request.
	 *
	 * Looks at the `remove_avatar` flag and the named file field, stores the
	 * new file under the configured avatar folder and deletes the previous
	 * local file. Callers receive:
	 *   - the old path when nothing was submitted (keep),
	 *   - NULL when the photo was explicitly removed,
	 *   - the new relative path after a successful upload,
	 *   - FALSE when the upload failed (error text lives in $upload).
	 *
	 * @param  string|null $old_path
	 * @param  string      $field
	 * @return string|null|false
	 */
	function save_user_avatar($old_path, $field = 'avatar_file')
	{
		$ci =& get_instance();
		$ci->load->library('upload');

		if ((string) $ci->input->post('remove_avatar', TRUE) !== '')
		{
			delete_user_avatar($old_path);

			return NULL;
		}

		if (empty($_FILES[$field]['name']))
		{
			return $old_path;
		}

		$path = (string) $ci->config->item('upload_avatar_path');
		$path = ($path !== '') ? $path : 'assets/uploads/avatars/';

		if ( ! is_dir(FCPATH.$path))
		{
			@mkdir(FCPATH.$path, 0755, TRUE);
		}

		$ci->upload->initialize(array(
			'upload_path'   => FCPATH.$path,
			'allowed_types' => 'jpg|jpeg|png|webp|gif|avif',
			'max_size'      => 2048, // 2 MB
			'file_name'     => 'avatar_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)),
			'overwrite'     => FALSE,
		));

		if ( ! $ci->upload->do_upload($field))
		{
			return FALSE;
		}

		$file = $ci->upload->data();

		delete_user_avatar($old_path);

		return $path.$file['file_name'];
	}
}

if ( ! function_exists('delete_user_avatar'))
{
	/**
	 * Remove a previously uploaded local avatar file. Remote URLs and files
	 * living outside the avatar folder are left untouched.
	 *
	 * @param  string|null $path
	 * @return void
	 */
	function delete_user_avatar($path)
	{
		if (empty($path) || is_remote_url($path))
		{
			return;
		}

		$ci     =& get_instance();
		$prefix = (string) $ci->config->item('upload_avatar_path');
		$prefix = ($prefix !== '') ? $prefix : 'assets/uploads/avatars/';

		if (strpos($path, $prefix) === 0 && is_file(FCPATH.$path))
		{
			@unlink(FCPATH.$path);
		}
	}
}

if ( ! function_exists('datetime_id'))
{
	/**
	 * Format a MySQL datetime as a readable Indonesian date.
	 *
	 * @param  string|null $datetime
	 * @param  bool        $with_time
	 * @return string
	 */
	function datetime_id($datetime, $with_time = TRUE)
	{
		if (empty($datetime))
		{
			return '-';
		}

		$ts = strtotime($datetime);

		if ($ts === FALSE)
		{
			return '-';
		}

		$months = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');

		$label = date('j', $ts).' '.$months[(int) date('n', $ts)].' '.date('Y', $ts);

		return $with_time ? $label.', '.date('H:i', $ts).' WIB' : $label;
	}
}

if ( ! function_exists('csrf_query'))
{
	/**
	 * CSRF token query string fragment for GET based mutation links.
	 *
	 * @return string
	 */
	function csrf_query()
	{
		$ci =& get_instance();

		return $ci->config->item('csrf_token_name').'='.$ci->security->get_csrf_hash();
	}
}

if ( ! function_exists('progress_percent'))
{
	/**
	 * Percentage helper for download quota bars.
	 *
	 * @param  int $used
	 * @param  int $max
	 * @return int
	 */
	function progress_percent($used, $max)
	{
		$max = (int) $max;

		if ($max <= 0)
		{
			return 0;
		}

		$percent = (int) round(((int) $used / $max) * 100);

		return max(0, min(100, $percent));
	}
}

if ( ! function_exists('preview_type_label'))
{
	/**
	 * Human readable preview media type.
	 *
	 * @param  string $type
	 * @return string
	 */
	function preview_type_label($type)
	{
		$labels = array(
			'audio' => 'Audio Demo',
			'video' => 'Video Preview',
			'image' => 'Image Preview',
			'none'  => 'No Preview',
		);

		return isset($labels[$type]) ? $labels[$type] : ucfirst((string) $type);
	}
}
