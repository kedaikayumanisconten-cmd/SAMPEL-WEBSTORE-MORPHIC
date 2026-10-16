<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics helpers.
 *
 * Two jobs:
 *   1. Classify a user agent (desktop / mobile / tablet / bot).
 *   2. Draw charts as inline SVG, so the dashboard stays dependency free
 *      (no Chart.js, no canvas, no external requests). Every mark carries a
 *      native <title> tooltip, so the charts work without any JavaScript.
 */

// ---------------------------------------------------------------------------
// Visitor classification
// ---------------------------------------------------------------------------

if ( ! function_exists('detect_device'))
{
	/**
	 * Very small user agent classifier. Deliberately regex only: the project
	 * ships without the CI user_agent library to keep requests cheap.
	 *
	 * @param  string|null $user_agent
	 * @return string desktop|mobile|tablet|bot
	 */
	function detect_device($user_agent)
	{
		$ua = strtolower((string) $user_agent);

		if ($ua === '')
		{
			return 'desktop';
		}

		$bots = array('bot', 'crawl', 'spider', 'slurp', 'curl/', 'wget', 'python', 'httpclient',
			'headless', 'pingdom', 'uptime', 'monitor', 'facebookexternalhit', 'whatsapp', 'telegrambot');

		foreach ($bots as $needle)
		{
			if (strpos($ua, $needle) !== FALSE)
			{
				return 'bot';
			}
		}

		if (preg_match('/ipad|tablet|playbook|silk|kindle|android(?!.*mobile)/i', $ua))
		{
			return 'tablet';
		}

		if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $ua))
		{
			return 'mobile';
		}

		return 'desktop';
	}
}

if ( ! function_exists('device_label'))
{
	/**
	 * Human label + icon for a device bucket.
	 *
	 * @param  string $device
	 * @return string
	 */
	function device_label($device)
	{
		$labels = array(
			'desktop' => '&#128421; Desktop',
			'mobile'  => '&#128241; Mobile',
			'tablet'  => '&#128223; Tablet',
			'bot'     => '&#129302; Bot / crawler',
		);

		return isset($labels[$device]) ? $labels[$device] : html_escape($device);
	}
}

if ( ! function_exists('device_name'))
{
	/**
	 * Plain text device name (no icon entity), for badges and tables.
	 *
	 * @param  string $device
	 * @return string
	 */
	function device_name($device)
	{
		$labels = array(
			'desktop' => 'Desktop',
			'mobile'  => 'Mobile',
			'tablet'  => 'Tablet',
			'bot'     => 'Bot',
		);

		return isset($labels[$device]) ? $labels[$device] : ucfirst((string) $device);
	}
}

if ( ! function_exists('visit_page_label'))
{
	/**
	 * Friendly Indonesian name for a tracked storefront URI.
	 *
	 * @param  string $uri
	 * @return string
	 */
	function visit_page_label($uri)
	{
		$uri = trim((string) $uri, '/');

		$labels = array(
			''         => 'Beranda',
			'products' => 'Katalog produk',
			'search'   => 'Pencarian',
			'cart'     => 'Keranjang',
			'checkout' => 'Checkout',
			'library'  => 'Digital Library',
			'login'    => 'Halaman masuk',
			'register' => 'Halaman daftar',
			'account'  => 'Dashboard akun',
		);

		if (isset($labels[$uri]))
		{
			return $labels[$uri];
		}

		if (strpos($uri, 'product/') === 0)
		{
			return 'Detail: '.ucwords(str_replace('-', ' ', substr($uri, 8)));
		}

		if (strpos($uri, 'category/') === 0)
		{
			return 'Kategori: '.ucwords(str_replace('-', ' ', substr($uri, 9)));
		}

		return $uri === '' ? 'Beranda' : '/'.$uri;
	}
}

if ( ! function_exists('time_ago'))
{
	/**
	 * Indonesian relative time, e.g. "12 menit lalu".
	 *
	 * @param  string|null $datetime
	 * @return string
	 */
	function time_ago($datetime)
	{
		if (empty($datetime))
		{
			return '-';
		}

		$timestamp = strtotime($datetime);

		if ($timestamp === FALSE)
		{
			return '-';
		}

		$diff = max(0, time() - $timestamp);

		if ($diff < 60)
		{
			return 'baru saja';
		}

		$units = array(
			array(31536000, 'tahun'),
			array(2592000,  'bulan'),
			array(604800,   'minggu'),
			array(86400,    'hari'),
			array(3600,     'jam'),
			array(60,       'menit'),
		);

		foreach ($units as $unit)
		{
			if ($diff >= $unit[0])
			{
				return floor($diff / $unit[0]).' '.$unit[1].' lalu';
			}
		}

		return 'baru saja';
	}
}

// ---------------------------------------------------------------------------
// Chart plumbing
// ---------------------------------------------------------------------------

if ( ! function_exists('chart_palette'))
{
	/**
	 * Chart colour palette, tuned to the storefront theme.
	 *
	 * @return array
	 */
	function chart_palette()
	{
		return array('#7c5cff', '#22d3ee', '#34d399', '#fbbf24', '#f87171', '#a48bff', '#38bdf8', '#f472b6');
	}
}

if ( ! function_exists('chart_sequential_id'))
{
	/**
	 * Unique id per rendered chart, so gradients never clash on one page.
	 *
	 * @param  string $prefix
	 * @return string
	 */
	function chart_sequential_id($prefix = 'chart')
	{
		static $seq = 0;
		$seq++;

		return $prefix.'-'.$seq;
	}
}

if ( ! function_exists('chart_compact_value'))
{
	/**
	 * Short axis label, e.g. 12500 -> "12,5rb" / 2300000 -> "2,3jt".
	 *
	 * @param  float  $value
	 * @param  string $format number|rupiah
	 * @return string
	 */
	function chart_compact_value($value, $format = 'number')
	{
		$value = (float) $value;

		if ($format === 'rupiah')
		{
			return $value > 0 ? rupiah_short($value) : 'Rp 0';
		}

		if ($value >= 1000000)
		{
			return rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').'jt';
		}

		if ($value >= 1000)
		{
			return rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',').'rb';
		}

		return number_format($value, 0, ',', '.');
	}
}

if ( ! function_exists('chart_full_value'))
{
	/**
	 * Tooltip / legend value.
	 *
	 * @param  float  $value
	 * @param  string $format number|rupiah
	 * @return string
	 */
	function chart_full_value($value, $format = 'number')
	{
		return $format === 'rupiah'
			? 'Rp '.number_format((float) $value, 0, ',', '.')
			: number_format((float) $value, 0, ',', '.');
	}
}

if ( ! function_exists('chart_nice_max'))
{
	/**
	 * Round an axis maximum up to a friendly step (1, 2, 2.5, 5 x 10^n).
	 *
	 * @param  float $max
	 * @param  int   $ticks
	 * @return float
	 */
	function chart_nice_max($max, $ticks = 4)
	{
		$max   = (float) $max;
		$ticks = max(2, (int) $ticks);

		if ($max <= 0)
		{
			return (float) $ticks;
		}

		$rough      = $max / $ticks;
		$magnitude  = pow(10, floor(log10($rough)));
		$normalized = $rough / $magnitude;

		if ($normalized <= 1)
		{
			$step = 1;
		}
		elseif ($normalized <= 2)
		{
			$step = 2;
		}
		elseif ($normalized <= 2.5)
		{
			$step = 2.5;
		}
		elseif ($normalized <= 5)
		{
			$step = 5;
		}
		else
		{
			$step = 10;
		}

		return $step * $magnitude * $ticks;
	}
}

if ( ! function_exists('chart_empty_state'))
{
	/**
	 * Placeholder shown when a series has no data yet.
	 *
	 * @param  string $message
	 * @return string
	 */
	function chart_empty_state($message = 'Belum ada data untuk periode ini.')
	{
		return '<div class="chart-empty"><span class="chart-empty-icon">&#128202;</span><span>'.html_escape($message).'</span></div>';
	}
}

// ---------------------------------------------------------------------------
// Area / line chart
// ---------------------------------------------------------------------------

if ( ! function_exists('svg_area_chart'))
{
	/**
	 * Responsive area chart drawn server side.
	 *
	 * @param  array $series  list of ['label' => string, 'value' => float, 'note' => string|null]
	 * @param  array $options width, height, format, color, color2, ticks, empty,
	 *                        label_every, series2 (same list shape, draws a
	 *                        dashed comparison line), series2_label
	 * @return string         SVG markup
	 */
	function svg_area_chart(array $series, array $options = array())
	{
		$series  = array_values($series);
		$second  = isset($options['series2']) ? array_values($options['series2']) : array();
		$count   = count($series);

		if ($count === 0)
		{
			return chart_empty_state(isset($options['empty']) ? $options['empty'] : NULL);
		}

		$width  = isset($options['width']) ? (float) $options['width'] : 760.0;
		$height = isset($options['height']) ? (float) $options['height'] : 260.0;
		$format = isset($options['format']) ? $options['format'] : 'number';
		$color  = isset($options['color']) ? $options['color'] : '#7c5cff';
		$color2 = isset($options['color2']) ? $options['color2'] : '#22d3ee';
		$ticks  = isset($options['ticks']) ? max(2, (int) $options['ticks']) : 4;

		$pad_l = 62.0;
		$pad_r = 18.0;
		$pad_t = 18.0;
		$pad_b = 34.0;

		$plot_w   = $width - $pad_l - $pad_r;
		$plot_h   = $height - $pad_t - $pad_b;
		$baseline = $pad_t + $plot_h;

		$max = 0.0;

		foreach ($series as $point)
		{
			$max = max($max, (float) $point['value']);
		}

		foreach ($second as $point)
		{
			$max = max($max, (float) $point['value']);
		}

		$axis_max = chart_nice_max($max, $ticks);

		$position = function ($i, $value) use ($count, $pad_l, $plot_w, $baseline, $plot_h, $axis_max) {
			$x = ($count === 1)
				? $pad_l + ($plot_w / 2)
				: $pad_l + ($plot_w * $i / ($count - 1));

			$y = $baseline - (((float) $value / $axis_max) * $plot_h);

			return array(round($x, 2), round($y, 2));
		};

		$coords  = array();
		$coords2 = array();

		foreach ($series as $i => $point)
		{
			$coords[] = $position($i, $point['value']);
		}

		foreach ($second as $i => $point)
		{
			$coords2[] = $position($i, $point['value']);
		}

		$line_path = '';

		foreach ($coords as $i => $point)
		{
			$line_path .= ($i === 0 ? 'M' : ' L').$point[0].' '.$point[1];
		}

		$area_path = $line_path
			.' L'.end($coords)[0].' '.$baseline
			.' L'.$coords[0][0].' '.$baseline
			.' Z';

		$grad_id = chart_sequential_id('area-grad');

		$svg  = '<svg class="chart-svg" viewBox="0 0 '.$width.' '.$height.'" preserveAspectRatio="xMidYMid meet" role="img">';
		$svg .= '<defs>';
		$svg .= '<linearGradient id="'.$grad_id.'" x1="0" y1="0" x2="0" y2="1">';
		$svg .= '<stop offset="0%" stop-color="'.$color.'" stop-opacity=".55"/>';
		$svg .= '<stop offset="100%" stop-color="'.$color.'" stop-opacity="0"/>';
		$svg .= '</linearGradient>';
		$svg .= '</defs>';

		// Horizontal grid + y axis labels.
		for ($t = 0; $t <= $ticks; $t++)
		{
			$y     = $baseline - ($plot_h * $t / $ticks);
			$label = chart_compact_value($axis_max * $t / $ticks, $format);

			$svg .= '<line class="chart-grid" x1="'.$pad_l.'" y1="'.round($y, 2).'" x2="'.($width - $pad_r).'" y2="'.round($y, 2).'"/>';
			$svg .= '<text class="chart-axis-y" x="'.($pad_l - 10).'" y="'.round($y + 4, 2).'" text-anchor="end">'.html_escape($label).'</text>';
		}

		$svg .= '<path class="chart-area" d="'.$area_path.'" fill="url(#'.$grad_id.')"/>';
		$svg .= '<path class="chart-line" d="'.$line_path.'" fill="none" stroke="'.$color.'"/>';

		$label_every = isset($options['label_every'])
			? max(1, (int) $options['label_every'])
			: max(1, (int) ceil($count / 8));

		foreach ($coords as $i => $point)
		{
			$point_data = $series[$i];
			$note       = isset($point_data['note']) && $point_data['note'] !== NULL
				? ' · '.$point_data['note']
				: '';

			$svg .= '<circle class="chart-point" cx="'.$point[0].'" cy="'.$point[1].'" r="3.5">';
			$svg .= '<title>'.html_escape($point_data['label'].': '.chart_full_value($point_data['value'], $format)).html_escape($note).'</title>';
			$svg .= '</circle>';

			if ($i % $label_every === 0 || $i === $count - 1)
			{
				$svg .= '<text class="chart-axis-x" x="'.$point[0].'" y="'.($baseline + 22).'" text-anchor="middle">'.html_escape($point_data['label']).'</text>';
			}
		}

		// Optional comparison line (e.g. unique visitors under raw visits).
		if ( ! empty($coords2))
		{
			$path2 = '';

			foreach ($coords2 as $i => $point)
			{
				$path2 .= ($i === 0 ? 'M' : ' L').$point[0].' '.$point[1];
			}

			$svg .= '<path class="chart-line-alt" d="'.$path2.'" fill="none" stroke="'.$color2.'"/>';

			foreach ($coords2 as $i => $point)
			{
				$note = isset($second[$i]['note']) && $second[$i]['note'] !== NULL ? ' · '.$second[$i]['note'] : '';

				$svg .= '<circle class="chart-point-alt" cx="'.$point[0].'" cy="'.$point[1].'" r="3">';
				$svg .= '<title>'.html_escape($second[$i]['label'].': '.chart_full_value($second[$i]['value'], $format)).html_escape($note).'</title>';
				$svg .= '</circle>';
			}
		}

		$svg .= '</svg>';

		return $svg;
	}
}

// ---------------------------------------------------------------------------
// Bar chart
// ---------------------------------------------------------------------------

if ( ! function_exists('svg_bar_chart'))
{
	/**
	 * Vertical bar chart drawn server side.
	 *
	 * @param  array $series  list of ['label' => string, 'value' => float, 'note' => string|null]
	 * @param  array $options width, height, format, color, ticks, empty, label_every
	 * @return string
	 */
	function svg_bar_chart(array $series, array $options = array())
	{
		$series = array_values($series);
		$count  = count($series);

		if ($count === 0)
		{
			return chart_empty_state(isset($options['empty']) ? $options['empty'] : NULL);
		}

		$width  = isset($options['width']) ? (float) $options['width'] : 760.0;
		$height = isset($options['height']) ? (float) $options['height'] : 240.0;
		$format = isset($options['format']) ? $options['format'] : 'number';
		$color  = isset($options['color']) ? $options['color'] : '#22d3ee';
		$ticks  = isset($options['ticks']) ? max(2, (int) $options['ticks']) : 4;

		$pad_l = 60.0;
		$pad_r = 18.0;
		$pad_t = 18.0;
		$pad_b = 34.0;

		$plot_w   = $width - $pad_l - $pad_r;
		$plot_h   = $height - $pad_t - $pad_b;
		$baseline = $pad_t + $plot_h;

		$max = 0.0;

		foreach ($series as $point)
		{
			$max = max($max, (float) $point['value']);
		}

		$axis_max = chart_nice_max($max, $ticks);
		$slot     = $plot_w / $count;
		$bar_w    = min(46.0, $slot * 0.62);

		$grad_id = chart_sequential_id('bar-grad');

		$svg  = '<svg class="chart-svg" viewBox="0 0 '.$width.' '.$height.'" preserveAspectRatio="xMidYMid meet" role="img">';
		$svg .= '<defs>';
		$svg .= '<linearGradient id="'.$grad_id.'" x1="0" y1="0" x2="0" y2="1">';
		$svg .= '<stop offset="0%" stop-color="'.$color.'" stop-opacity="1"/>';
		$svg .= '<stop offset="100%" stop-color="'.$color.'" stop-opacity=".28"/>';
		$svg .= '</linearGradient>';
		$svg .= '</defs>';

		for ($t = 0; $t <= $ticks; $t++)
		{
			$y     = $baseline - ($plot_h * $t / $ticks);
			$label = chart_compact_value($axis_max * $t / $ticks, $format);

			$svg .= '<line class="chart-grid" x1="'.$pad_l.'" y1="'.round($y, 2).'" x2="'.($width - $pad_r).'" y2="'.round($y, 2).'"/>';
			$svg .= '<text class="chart-axis-y" x="'.($pad_l - 10).'" y="'.round($y + 4, 2).'" text-anchor="end">'.html_escape($label).'</text>';
		}

		$label_every = isset($options['label_every'])
			? max(1, (int) $options['label_every'])
			: max(1, (int) ceil($count / 10));

		foreach ($series as $i => $point)
		{
			$value = (float) $point['value'];
			$bar_h = $axis_max > 0 ? ($value / $axis_max) * $plot_h : 0;
			$bar_h = max($value > 0 ? 3.0 : 1.0, $bar_h);

			$x = $pad_l + ($slot * $i) + (($slot - $bar_w) / 2);
			$y = $baseline - $bar_h;

			$note = isset($point['note']) && $point['note'] !== NULL ? ' · '.$point['note'] : '';

			$svg .= '<rect class="chart-bar" x="'.round($x, 2).'" y="'.round($y, 2).'" width="'.round($bar_w, 2).'" height="'.round($bar_h, 2).'" rx="5" fill="url(#'.$grad_id.')">';
			$svg .= '<title>'.html_escape($point['label'].': '.chart_full_value($value, $format)).html_escape($note).'</title>';
			$svg .= '</rect>';

			if ($i % $label_every === 0 || $i === $count - 1)
			{
				$svg .= '<text class="chart-axis-x" x="'.round($x + ($bar_w / 2), 2).'" y="'.($baseline + 22).'" text-anchor="middle">'.html_escape($point['label']).'</text>';
			}
		}

		$svg .= '</svg>';

		return $svg;
	}
}

// ---------------------------------------------------------------------------
// Donut chart
// ---------------------------------------------------------------------------

if ( ! function_exists('svg_donut_chart'))
{
	/**
	 * Donut chart with an HTML legend.
	 *
	 * @param  array $slices  list of ['label' => string, 'value' => float, 'color' => string|null]
	 * @param  array $options caption, size, empty, value_format
	 * @return string
	 */
	function svg_donut_chart(array $slices, array $options = array())
	{
		$palette = chart_palette();
		$total   = 0.0;

		foreach ($slices as $slice)
		{
			$total += (float) $slice['value'];
		}

		if (empty($slices) || $total <= 0)
		{
			return chart_empty_state(isset($options['empty']) ? $options['empty'] : NULL);
		}

		$size    = isset($options['size']) ? (float) $options['size'] : 168.0;
		$caption = isset($options['caption']) ? $options['caption'] : 'Total';
		$format  = isset($options['value_format']) ? $options['value_format'] : 'number';

		$radius     = 62.0;
		$stroke     = 20.0;
		$center     = $size / 2;
		$circum     = 2 * M_PI * $radius;
		$offset     = 0.0;

		$svg = '<svg class="donut-svg" viewBox="0 0 '.$size.' '.$size.'" role="img">';
		$svg .= '<circle class="donut-track" cx="'.$center.'" cy="'.$center.'" r="'.$radius.'" fill="none" stroke-width="'.$stroke.'"/>';

		foreach ($slices as $i => $slice)
		{
			$value = (float) $slice['value'];

			if ($value <= 0)
			{
				continue;
			}

			$color = isset($slice['color']) && $slice['color'] !== '' ? $slice['color'] : $palette[$i % count($palette)];
			$len   = ($value / $total) * $circum;

			$svg .= '<circle class="donut-slice" cx="'.$center.'" cy="'.$center.'" r="'.$radius.'" fill="none"';
			$svg .= ' stroke="'.$color.'" stroke-width="'.$stroke.'"';
			$svg .= ' stroke-dasharray="'.round($len, 2).' '.round($circum - $len, 2).'"';
			$svg .= ' stroke-dashoffset="'.round(-$offset, 2).'"';
			$svg .= ' transform="rotate(-90 '.$center.' '.$center.')">';
			$svg .= '<title>'.html_escape($slice['label'].': '.chart_full_value($value, $format).' ('.round(($value / $total) * 100).'%)').'</title>';
			$svg .= '</circle>';

			$offset += $len;
		}

		$svg .= '<text class="donut-total" x="'.$center.'" y="'.($center + 4).'" text-anchor="middle">'.html_escape(chart_compact_value($total, $format)).'</text>';
		$svg .= '<text class="donut-caption" x="'.$center.'" y="'.($center + 24).'" text-anchor="middle">'.html_escape($caption).'</text>';
		$svg .= '</svg>';

		$legend = '<ul class="chart-legend">';

		foreach ($slices as $i => $slice)
		{
			$value = (float) $slice['value'];
			$color = isset($slice['color']) && $slice['color'] !== '' ? $slice['color'] : $palette[$i % count($palette)];
			$pct   = $total > 0 ? round(($value / $total) * 100) : 0;

			$legend .= '<li>';
			$legend .= '<span class="legend-dot" style="background:'.$color.'"></span>';
			$legend .= '<span class="legend-label">'.html_escape($slice['label']).'</span>';
			$legend .= '<span class="legend-value">'.html_escape(chart_full_value($value, $format)).' <small>'.$pct.'%</small></span>';
			$legend .= '</li>';
		}

		$legend .= '</ul>';

		return '<div class="donut-wrap">'.$svg.$legend.'</div>';
	}
}

// ---------------------------------------------------------------------------
// Horizontal bar list (rankings: top pages, top products, referrers)
// ---------------------------------------------------------------------------

if ( ! function_exists('html_hbar_list'))
{
	/**
	 * Ranked horizontal bars, rendered as plain HTML + CSS.
	 *
	 * @param  array $rows    list of ['label' => string, 'value' => float, 'sub' => string|null, 'url' => string|null]
	 * @param  array $options format, empty, show_percent
	 * @return string
	 */
	function html_hbar_list(array $rows, array $options = array())
	{
		if (empty($rows))
		{
			return chart_empty_state(isset($options['empty']) ? $options['empty'] : NULL);
		}

		$format = isset($options['format']) ? $options['format'] : 'number';
		$max    = 0.0;

		foreach ($rows as $row)
		{
			$max = max($max, (float) $row['value']);
		}

		$html = '<ul class="hbar-list">';

		foreach ($rows as $row)
		{
			$value = (float) $row['value'];
			$width = $max > 0 ? max(2, round(($value / $max) * 100)) : 2;

			$label = html_escape($row['label']);
			$value_html = html_escape(chart_full_value($value, $format));

			if (isset($row['sub']) && $row['sub'] !== '')
			{
				$value_html .= ' <small>'.html_escape($row['sub']).'</small>';
			}

			$html .= '<li>';
			$html .= '<div class="hbar-head"><span class="hbar-label">'.$label.'</span><span class="hbar-value">'.$value_html.'</span></div>';
			$html .= '<span class="hbar-track"><span class="hbar-fill" style="width:'.$width.'%"></span></span>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}
}
