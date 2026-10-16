<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model
 *
 * Handles authentication, password hashing and the admin user listing.
 * Backed by the `users` table.
 */
class User_model extends CI_Model
{
	/**
	 * @var string
	 */
	protected $table = 'users';

	public function __construct()
	{
		parent::__construct();
	}

	// ---------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------

	/**
	 * Find a user by primary key.
	 *
	 * @param  int $id
	 * @return array|null
	 */
	public function get($id)
	{
		$row = $this->db->get_where($this->table, array('id' => (int) $id))->row_array();

		return $row ?: NULL;
	}

	/**
	 * Find a user by e-mail address.
	 *
	 * @param  string $email
	 * @return array|null
	 */
	public function get_by_email($email)
	{
		$row = $this->db->get_where($this->table, array('email' => strtolower(trim($email))))->row_array();

		return $row ?: NULL;
	}

	/**
	 * Is an e-mail already registered?
	 *
	 * @param  string   $email
	 * @param  int|null $except_id
	 * @return bool
	 */
	public function email_exists($email, $except_id = NULL)
	{
		$this->db->where('email', strtolower(trim($email)));

		if ($except_id !== NULL)
		{
			$this->db->where('id !=', (int) $except_id);
		}

		return $this->db->count_all_results($this->table) > 0;
	}

	/**
	 * Find a user by username (the optional login alias).
	 *
	 * @param  string $username
	 * @return array|null
	 */
	public function get_by_username($username)
	{
		$row = $this->db
			->where('username', strtolower(trim($username)))
			->get($this->table)
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Find the account behind a login attempt. The login form accepts either
	 * the e-mail address or the username, so both columns are checked.
	 *
	 * @param  string $login
	 * @return array|null
	 */
	public function get_by_login($login)
	{
		$login = strtolower(trim($login));

		if ($login === '')
		{
			return NULL;
		}

		$row = $this->db
			->group_start()
				->where('email', $login)
				->or_where('username', $login)
			->group_end()
			->get($this->table)
			->row_array();

		return $row ?: NULL;
	}

	/**
	 * Is a username already taken?
	 *
	 * @param  string   $username
	 * @param  int|null $except_id
	 * @return bool
	 */
	public function username_exists($username, $except_id = NULL)
	{
		$this->db->where('username', strtolower(trim($username)));

		if ($except_id !== NULL)
		{
			$this->db->where('id !=', (int) $except_id);
		}

		return $this->db->count_all_results($this->table) > 0;
	}

	// ---------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------

	/**
	 * Register a new user. The plain password is hashed here so callers never
	 * have to deal with hashes directly.
	 *
	 * @param  array $input full_name, email, password, role?
	 * @return int   New user id
	 */
	public function create(array $input)
	{
		$username = isset($input['username']) ? strtolower(trim((string) $input['username'])) : '';

		$role = isset($input['role']) ? (string) $input['role'] : 'customer';

		$data = array(
			'full_name'     => trim($input['full_name']),
			'username'      => $username !== '' ? $username : NULL,
			'email'         => strtolower(trim($input['email'])),
			'phone'         => (isset($input['phone']) && trim((string) $input['phone']) !== '') ? trim((string) $input['phone']) : NULL,
			'password_hash' => $this->hash_password($input['password']),
			'role'          => in_array($role, array('owner', 'super_admin', 'admin', 'customer'), TRUE) ? $role : 'customer',
			'is_active'     => isset($input['is_active']) ? (int) $input['is_active'] : 1,
			'created_at'    => date('Y-m-d H:i:s'),
		);

		$this->db->insert($this->table, $data);

		return (int) $this->db->insert_id();
	}

	/**
	 * Update profile level fields (never the password).
	 *
	 * @param  int   $id
	 * @param  array $input
	 * @return bool
	 */
	public function update($id, array $input)
	{
		$allowed = array('full_name', 'username', 'email', 'phone', 'avatar', 'is_active', 'role');
		$data    = array();

		foreach ($allowed as $field)
		{
			if ( ! array_key_exists($field, $input))
			{
				continue;
			}

			if ($field === 'email' || $field === 'username')
			{
				$value = strtolower(trim((string) $input[$field]));

				// An empty username clears the alias (email login still works).
				$data[$field] = ($field === 'username' && $value === '') ? NULL : $value;

				continue;
			}

			if ($field === 'phone' || $field === 'avatar')
			{
				$value = trim((string) $input[$field]);

				// An empty contact field clears the stored value.
				$data[$field] = ($value === '') ? NULL : $value;

				continue;
			}

			$data[$field] = $input[$field];
		}

		if (empty($data))
		{
			return FALSE;
		}

		return $this->db->where('id', (int) $id)->update($this->table, $data);
	}

	/**
	 * Replace a user's password.
	 *
	 * @param  int    $id
	 * @param  string $plain_password
	 * @return bool
	 */
	public function set_password($id, $plain_password)
	{
		return $this->db->where('id', (int) $id)->update($this->table, array(
			'password_hash' => $this->hash_password($plain_password),
		));
	}

	/**
	 * Build a random, human-typable password.
	 *
	 * Visually ambiguous characters (0/O, 1/l/I) are left out so the value can
	 * be read aloud or copied by hand. The result always mixes lower case,
	 * upper case, digits and a symbol, which keeps it compatible with the
	 * application's minimum length rule (8).
	 *
	 * @param  int $length
	 * @return string
	 */
	public function random_password($length = 12)
	{
		$length = max(8, min(72, (int) $length));

		$lower   = 'abcdefghijkmnopqrstuvwxyz';
		$upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		$digits  = '23456789';
		$symbols = '!@#$%&*?';
		$all     = $lower.$upper.$digits.$symbols;

		$chars = array(
			$lower[random_int(0, strlen($lower) - 1)],
			$upper[random_int(0, strlen($upper) - 1)],
			$digits[random_int(0, strlen($digits) - 1)],
			$symbols[random_int(0, strlen($symbols) - 1)],
		);

		while (count($chars) < $length)
		{
			$chars[] = $all[random_int(0, strlen($all) - 1)];
		}

		// Fisher-Yates shuffle so the guaranteed characters land anywhere.
		for ($i = count($chars) - 1; $i > 0; $i--)
		{
			$j    = random_int(0, $i);
			$swap = $chars[$i];

			$chars[$i] = $chars[$j];
			$chars[$j] = $swap;
		}

		return implode('', $chars);
	}

	/**
	 * Record the successful login timestamp.
	 *
	 * @param  int $id
	 * @return void
	 */
	public function touch_last_login($id)
	{
		$this->db->where('id', (int) $id)->update($this->table, array(
			'last_login_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Enable or disable an account.
	 *
	 * @param  int  $id
	 * @param  bool $active
	 * @return bool
	 */
	public function set_active($id, $active)
	{
		return $this->db->where('id', (int) $id)->update($this->table, array(
			'is_active' => $active ? 1 : 0,
		));
	}

	/**
	 * Delete every saved session belonging to a user.
	 *
	 * Used after an administrator resets somebody else's password: the
	 * affected account is signed out everywhere and must log in again with
	 * the new credentials. The session payload stores the id either as an
	 * integer or as a string, so both encodings are matched. When the format
	 * is not recognised nothing is deleted, which is harmless.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function purge_sessions_for($id)
	{
		$id     = (int) $id;
		$table  = (string) $this->config->item('sess_save_path');
		$table  = ($table !== '') ? $table : 'ci_sessions';

		return $this->db
			->group_start()
				->like('data', 'user_id|i:'.$id.';')
				->or_like('data', 'user_id|s:'.strlen((string) $id).':"'.$id.'";')
			->group_end()
			->delete($table);
	}

	// ---------------------------------------------------------------------
	// Authentication
	// ---------------------------------------------------------------------

	/**
	 * Validate credentials.
	 *
	 * @param  string $login    E-mail address or username
	 * @param  string $password
	 * @return array|string User row on success, or an error keyword:
	 *                      'invalid' | 'inactive'
	 */
	public function authenticate($login, $password)
	{
		$user = $this->get_by_login($login);

		if ( ! $user || ! $this->verify_password($password, $user['password_hash']))
		{
			return 'invalid';
		}

		if ((int) $user['is_active'] !== 1)
		{
			return 'inactive';
		}

		// Transparently upgrade legacy / low cost hashes.
		if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT))
		{
			$this->set_password($user['id'], $password);
		}

		return $user;
	}

	/**
	 * Hash a plain text password.
	 *
	 * @param  string $password
	 * @return string
	 */
	public function hash_password($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	/**
	 * Verify a plain text password against a stored hash.
	 *
	 * @param  string $password
	 * @param  string $hash
	 * @return bool
	 */
	public function verify_password($password, $hash)
	{
		return password_verify($password, $hash);
	}

	// ---------------------------------------------------------------------
	// Admin listings
	// ---------------------------------------------------------------------

	/**
	 * Paginated user list for the admin panel.
	 *
	 * @param  array $filters q, role, status
	 * @param  int   $limit
	 * @param  int   $offset
	 * @return array
	 */
	public function paginate(array $filters = array(), $limit = 15, $offset = 0)
	{
		$this->_apply_filters($filters);

		return $this->db
			->order_by('created_at', 'DESC')
			->limit((int) $limit, (int) $offset)
			->get($this->table)
			->result_array();
	}

	/**
	 * Count rows for the paginated user list.
	 *
	 * @param  array $filters
	 * @return int
	 */
	public function count_all(array $filters = array())
	{
		$this->_apply_filters($filters);

		return $this->db->count_all_results($this->table);
	}

	/**
	 * Aggregate counters for the admin dashboard.
	 *
	 * @return array
	 */
	public function stats()
	{
		$row = $this->db
			->select('COUNT(*) AS total, SUM(role <> "customer") AS staff, SUM(is_active = 1) AS active')
			->get($this->table)
			->row_array();

		return array(
			'total'  => (int) ($row['total'] ?? 0),
			'admins' => (int) ($row['staff'] ?? 0),
			'active' => (int) ($row['active'] ?? 0),
		);
	}

	/**
	 * New customer registrations per day, for the growth chart.
	 *
	 * @param  int $days
	 * @return array
	 */
	public function registrations_by_day($days = 30)
	{
		$days = max(1, (int) $days);

		$rows = $this->db
			->select('DATE(created_at) AS day, COUNT(*) AS total')
			->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-'.($days - 1).' days')))
			->group_by('DATE(created_at)')
			->order_by('day', 'ASC')
			->get($this->table)
			->result_array();

		$indexed = array();

		foreach ($rows as $row)
		{
			$indexed[$row['day']] = (int) $row['total'];
		}

		$series = array();

		for ($i = $days - 1; $i >= 0; $i--)
		{
			$day = date('Y-m-d', strtotime('-'.$i.' days'));

			$series[] = array(
				'day'   => $day,
				'label' => date('d/m', strtotime($day)),
				'value' => isset($indexed[$day]) ? $indexed[$day] : 0,
			);
		}

		return $series;
	}

	/**
	 * Number of accounts created inside the rolling window.
	 *
	 * @param  int $days
	 * @return int
	 */
	public function count_new($days = 30)
	{
		return (int) $this->db
			->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-'.(max(1, (int) $days) - 1).' days')))
			->count_all_results($this->table);
	}

	/**
	 * Latest registrations, for the activity feed.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function get_recent($limit = 5)
	{
		return $this->db
			->select('id, full_name, username, email, role, created_at')
			->order_by('created_at', 'DESC')
			->limit((int) $limit)
			->get($this->table)
			->result_array();
	}

	/**
	 * Apply listing filters to the active query builder instance.
	 *
	 * @param  array $filters
	 * @return void
	 */
	protected function _apply_filters(array $filters)
	{
		if ( ! empty($filters['q']))
		{
			$this->db->group_start()
				->like('full_name', $filters['q'])
				->or_like('email', $filters['q'])
				->or_like('phone', $filters['q'])
				->group_end();
		}

		if ( ! empty($filters['role']))
		{
			$this->db->where('role', $filters['role']);
		}

		if (isset($filters['status']) && $filters['status'] !== '')
		{
			$this->db->where('is_active', (int) $filters['status']);
		}
	}
}
