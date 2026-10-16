<?php
/**
 * M.O.R.P.H.I.C - demo traffic seeder
 * ============================================================================
 * Fills `page_visits` with a realistic spread of storefront page views so the
 * admin dashboard charts have something to draw on a fresh install.
 *
 * Real traffic is logged automatically by MY_Controller::track_visit() - this
 * script only exists so a freshly imported database does not open on an empty
 * analytics page.
 *
 * Usage (from the project root):
 *   php tools/seed_demo_traffic.php            # 30 days, ~800 hits
 *   php tools/seed_demo_traffic.php 60 1500    # 60 days, ~1500 hits
 *   php tools/seed_demo_traffic.php --clear    # wipe the log and exit
 *
 * To remove the demo data later, use the "Bersihkan riwayat" button on
 * admin/visits, or run this script with --clear.
 */

if (PHP_SAPI !== 'cli')
{
	exit('This script can only be run from the command line.');
}

// Keep the generated timestamps on the same clock as the application.
date_default_timezone_set('Asia/Jakarta');

$args = array_slice($argv, 1);

if (in_array('--clear', $args, TRUE) || in_array('-c', $args, TRUE))
{
	$db = seed_connect();
	$db->query('TRUNCATE TABLE page_visits');
	echo 'page_visits cleared.'.PHP_EOL;
	exit(0);
}

$days = isset($args[0]) ? max(7, (int) $args[0]) : 30;
$hits = isset($args[1]) ? max(50, (int) $args[1]) : 800;

// ---------------------------------------------------------------------------
// Connection (mirrors application/config/database.php)
// ---------------------------------------------------------------------------

function seed_connect()
{
	$host = getenv('DB_HOST') ?: 'localhost';
	$user = getenv('DB_USER') ?: 'root';
	$pass = getenv('DB_PASS') !== FALSE ? getenv('DB_PASS') : '';
	$name = getenv('DB_NAME') ?: 'morphic_store';

	$db = @new mysqli($host, $user, $pass, $name);

	if ($db->connect_errno)
	{
		exit('Cannot connect to the database: '.$db->connect_error.PHP_EOL);
	}

	$db->set_charset('utf8mb4');

	return $db;
}

$db = seed_connect();

// ---------------------------------------------------------------------------
// Build a realistic URI pool from the real catalogue
// ---------------------------------------------------------------------------

$uris = array(
	array('uri' => '',                'weight' => 22),
	array('uri' => 'products',        'weight' => 24),
	array('uri' => 'search',          'weight' => 7),
	array('uri' => 'cart',            'weight' => 6),
	array('uri' => 'login',           'weight' => 5),
	array('uri' => 'register',        'weight' => 3),
	array('uri' => 'library',         'weight' => 3),
	array('uri' => 'account',         'weight' => 3),
	array('uri' => 'checkout',        'weight' => 2),
);

$categories = $db->query('SELECT slug FROM categories WHERE is_active = 1 ORDER BY id');

while ($row = $categories->fetch_assoc())
{
	$uris[] = array('uri' => 'category/'.$row['slug'], 'weight' => 6);
}

$products = $db->query('SELECT slug FROM products ORDER BY id');

while ($row = $products->fetch_assoc())
{
	$uris[] = array('uri' => 'product/'.$row['slug'], 'weight' => 8);
}

$weighted_uris = seed_expand($uris);

// ---------------------------------------------------------------------------
// Visitors, devices, referers
// ---------------------------------------------------------------------------

$sessions = array();
$members  = array(2, 3);          // seeded customers
$devices  = seed_expand(array(
	array('value' => 'desktop', 'weight' => 62),
	array('value' => 'mobile',  'weight' => 28),
	array('value' => 'tablet',  'weight' => 7),
	array('value' => 'bot',     'weight' => 3),
));

$referers = seed_expand(array(
	array('value' => NULL,                        'weight' => 52),
	array('value' => 'https://www.google.com/',   'weight' => 20),
	array('value' => 'https://www.instagram.com/','weight' => 10),
	array('value' => 'https://www.youtube.com/',  'weight' => 8),
	array('value' => 'https://www.facebook.com/','weight' => 5),
	array('value' => 'https://twitter.com/',      'weight' => 5),
));

$agents = array(
	'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36',
	'mobile'  => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
	'tablet'  => 'Mozilla/5.0 (iPad; CPU OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
	'bot'     => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
);

// Reproducible demo data.
mt_srand(20261016);

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

$db->query('TRUNCATE TABLE page_visits');

$rows   = array();
$now    = time();
$total  = 0;

for ($day_offset = $days - 1; $day_offset >= 0; $day_offset--)
{
	$day_start = strtotime('-'.$day_offset.' days', strtotime(date('Y-m-d 00:00:00')));

	// Gentle upward trend over the window, with weekday/weekend variation.
	$progress   = ($days - 1 - $day_offset) / max(1, $days - 1);
	$base       = ($hits / $days) * (0.45 + 1.15 * $progress);
	$weekday    = (int) date('N', $day_start);
	$multiplier = ($weekday >= 6) ? 0.72 : (($weekday === 5) ? 1.12 : 1.0);

	$count = (int) round($base * $multiplier * (mt_rand(85, 115) / 100));

	// Never log into the future.
	$max_hour = ($day_offset === 0) ? (int) date('G') : 23;

	// The current day is still in progress: only seed the part that elapsed.
	if ($day_offset === 0)
	{
		$count = (int) round($count * (($max_hour + 1) / 24));
	}

	for ($i = 0; $i < $count; $i++)
	{
		$hour = seed_weighted_hour($max_hour);
		$ts   = $day_start + ($hour * 3600) + mt_rand(0, 3599);

		if ($ts > $now)
		{
			$ts = $now - mt_rand(0, 600);
		}

		$device    = $devices[array_rand($devices)];
		$is_member = ($device !== 'bot') && (mt_rand(1, 100) <= 26);
		$user_id   = $is_member ? $members[array_rand($members)] : NULL;

		// Roughly half of the hits start a new session, the rest continue one.
		if (empty($sessions) || mt_rand(1, 100) <= 46)
		{
			$session = bin2hex(random_bytes(16));
			$sessions[] = $session;

			if (count($sessions) > 400)
			{
				array_shift($sessions);
			}
		}
		else
		{
			$session = $sessions[array_rand($sessions)];
		}

		$rows[] = array(
			'user_id'    => $user_id,
			'session_id' => $session,
			'uri'        => $weighted_uris[array_rand($weighted_uris)],
			'referer'    => $referers[array_rand($referers)],
			'ip_address' => seed_ip(),
			'user_agent' => $agents[$device],
			'device'     => $device,
			'is_admin'   => ((int) $user_id === 1) ? 1 : 0,
			'visited_at' => date('Y-m-d H:i:s', $ts),
		);

		$total++;
	}
}

// Batch insert.
foreach (array_chunk($rows, 200) as $chunk)
{
	$values = array();

	foreach ($chunk as $row)
	{
		$values[] = sprintf(
			'(%s, %s, %s, %s, %s, %s, %s, %d, %s)',
			$row['user_id'] === NULL ? 'NULL' : (int) $row['user_id'],
			$row['session_id'] === NULL ? 'NULL' : "'".$db->real_escape_string($row['session_id'])."'",
			"'".$db->real_escape_string($row['uri'])."'",
			$row['referer'] === NULL ? 'NULL' : "'".$db->real_escape_string($row['referer'])."'",
			"'".$db->real_escape_string($row['ip_address'])."'",
			$row['user_agent'] === NULL ? 'NULL' : "'".$db->real_escape_string($row['user_agent'])."'",
			"'".$row['device']."'",
			$row['is_admin'],
			"'".$row['visited_at']."'"
		);
	}

	$sql = 'INSERT INTO page_visits (user_id, session_id, uri, referer, ip_address, user_agent, device, is_admin, visited_at) VALUES '.implode(',', $values);

	if ( ! $db->query($sql))
	{
		exit('Insert failed: '.$db->error.PHP_EOL);
	}
}

echo 'Seeded '.number_format($total).' demo page views across the last '.$days.' days.'.PHP_EOL;
echo 'Clear them any time: php tools/seed_demo_traffic.php --clear'.PHP_EOL;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Expand a weighted list into a flat pick-from array.
 */
function seed_expand(array $items)
{
	$flat = array();
	$key  = array_key_exists('uri', $items[0]) ? 'uri' : 'value';

	foreach ($items as $item)
	{
		$weight = max(1, (int) $item['weight']);

		for ($i = 0; $i < $weight; $i++)
		{
			$flat[] = $item[$key];
		}
	}

	return $flat;
}

/**
 * Traffic peaks in the afternoon/evening, like a creator tool store.
 */
function seed_weighted_hour($max_hour = 23)
{
	$weights = array(1, 1, 1, 1, 2, 3, 5, 8, 13, 18, 22, 24, 22, 25, 28, 30, 32, 30, 28, 27, 24, 18, 10, 4);

	$total = 0;

	for ($h = 0; $h <= $max_hour; $h++)
	{
		$total += $weights[$h];
	}

	if ($total <= 0)
	{
		return max(0, (int) $max_hour);
	}

	$roll = mt_rand(1, $total);

	for ($h = 0; $h <= $max_hour; $h++)
	{
		$roll -= $weights[$h];

		if ($roll <= 0)
		{
			return $h;
		}
	}

	return max(0, (int) $max_hour);
}

/**
 * Small pool of repeat visitors so "unique visitors" stays believable.
 */
function seed_ip()
{
	static $pool = NULL;

	if ($pool === NULL)
	{
		$pool = array();

		for ($i = 0; $i < 90; $i++)
		{
			$pool[] = '36.'.mt_rand(60, 95).'.'.mt_rand(0, 255).'.'.mt_rand(1, 254);
		}
	}

	return $pool[array_rand($pool)];
}
