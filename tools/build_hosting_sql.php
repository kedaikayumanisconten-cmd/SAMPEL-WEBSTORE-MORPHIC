<?php
/**
 * M.O.R.P.H.I.C - hosting SQL builder
 * ============================================================================
 * database.sql starts with CREATE DATABASE and USE `morphic_store`, which is
 * convenient from the MySQL CLI but fails on shared hosting: most cPanel
 * accounts may not run CREATE DATABASE and always use a prefixed name such as
 * `user_morphic`.
 *
 * This script derives database_hosting.sql from database.sql by dropping those
 * two statements. The result is imported into an already selected database
 * from phpMyAdmin (Import -> choose file -> Go).
 *
 * Usage (from the project root):
 *   php tools/build_hosting_sql.php
 *
 * Re-run it whenever database.sql changes so both files stay identical.
 */

if (PHP_SAPI !== 'cli')
{
	exit('This script can only be run from the command line.');
}

$root = dirname(__DIR__);
$source = $root.DIRECTORY_SEPARATOR.'database.sql';
$target = $root.DIRECTORY_SEPARATOR.'database_hosting.sql';

if ( ! is_file($source))
{
	fwrite(STDERR, "database.sql tidak ditemukan: {$source}\n");
	exit(1);
}

$sql = file_get_contents($source);

if ($sql === FALSE)
{
	fwrite(STDERR, "Gagal membaca database.sql\n");
	exit(1);
}

// Remove CREATE DATABASE ...; (may span several lines).
$sql = preg_replace('/^\s*CREATE DATABASE\b[^;]*;\s*$/mi', '', $sql);

// Remove USE `...`; so the file follows whatever database is selected.
$sql = preg_replace('/^\s*USE\s+`[^`]+`\s*;\s*$/mi', '', $sql);

$header = <<<'SQL'
-- ============================================================================
-- M.O.R.P.H.I.C - database schema for phpMyAdmin / shared hosting
-- ============================================================================
-- Generated from database.sql by tools/build_hosting_sql.php - do not edit.
--
-- Cara pakai:
--   1. Buat database di cPanel (mis. user_morphic) lalu buka phpMyAdmin.
--   2. Pilih database tersebut di panel kiri.
--   3. Tab "Import" -> pilih file ini -> Go.
--
-- File ini tidak membuat/memilih database, jadi aman untuk akun hosting yang
-- tidak punya izin CREATE DATABASE dan memakai nama database berprefix.
-- ============================================================================

SQL;

$sql = $header."\n".ltrim($sql);

if (file_put_contents($target, $sql) === FALSE)
{
	fwrite(STDERR, "Gagal menulis {$target}\n");
	exit(1);
}

echo "OK: database_hosting.sql dibuat dari database.sql\n";
echo 'Ukuran: '.number_format(strlen($sql))." byte\n";
