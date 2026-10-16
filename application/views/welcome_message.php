<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Base Fullstack Project</title>

	<style type="text/css">

	::selection { background-color: #E13300; color: white; }
	::-moz-selection { background-color: #E13300; color: white; }

	body {
		margin: 0;
		padding: 0;
		background: #f4f6f9;
		color: #374151;
		font: 15px/1.7 "Segoe UI", Helvetica, Arial, sans-serif;
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	a {
		color: #0369a1;
		text-decoration: none;
	}

	a:hover {
		text-decoration: underline;
	}

	#container {
		max-width: 720px;
		margin: 40px auto;
		padding: 48px;
		background: #ffffff;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
		text-align: center;
	}

	.badge {
		display: inline-block;
		padding: 6px 16px;
		margin-bottom: 20px;
		background: #e0f2fe;
		color: #0369a1;
		border: 1px solid #bae6fd;
		border-radius: 999px;
		font-size: 13px;
		font-weight: 600;
		letter-spacing: 0.5px;
	}

	h1 {
		margin: 0 0 12px 0;
		color: #111827;
		font-size: 30px;
		font-weight: 700;
	}

	#body {
		margin: 24px 0;
	}

	#body p {
		margin: 0 0 14px;
		color: #4b5563;
	}

	code {
		font-family: Consolas, Monaco, "Courier New", Courier, monospace;
		font-size: 13px;
		background: #f3f4f6;
		border: 1px solid #e5e7eb;
		color: #0f766e;
		border-radius: 6px;
		padding: 4px 10px;
	}

	.stack {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
		gap: 12px;
		margin: 28px 0;
		text-align: center;
	}

	.stack .item {
		padding: 18px 12px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 10px;
	}

	.stack .item strong {
		display: block;
		font-size: 16px;
		color: #111827;
	}

	.stack .item span {
		font-size: 13px;
		color: #6b7280;
	}
	</style>
</head>
<body>

<div id="container">
	<span class="badge">READY TO DEVELOP</span>

	<h1>Base Fullstack Web Project</h1>

	<div id="body">
		<p>Selamat! Project ini sudah siap dikembangkan lebih lanjut.</p>

		<div class="stack">
			<div class="item"><strong>CodeIgniter <?php echo CI_VERSION; ?></strong><span>Framework Backend (PHP)</span></div>
			<div class="item"><strong>PHP <?php echo PHP_VERSION; ?></strong><span>Runtime</span></div>
			<div class="item"><strong>MVC Architecture</strong><span>Controller - Model - View</span></div>
		</div>
	</div>

</div>

</body>
</html>