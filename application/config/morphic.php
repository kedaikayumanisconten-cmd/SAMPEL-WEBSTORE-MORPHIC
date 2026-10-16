<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| M.O.R.P.H.I.C Store Configuration
|--------------------------------------------------------------------------
| Application specific settings for the digital software & asset store.
|
*/

/*
|--------------------------------------------------------------------------
| Store Identity
|--------------------------------------------------------------------------
*/
$config['store_name']        = 'M.O.R.P.H.I.C';
$config['store_tagline']     = 'Digital Software & Asset Store untuk Kreator & Musisi';
$config['store_email']       = 'support@morphic.store';
$config['store_currency']    = 'IDR';

/*
 * The values below are the fallback defaults for the editable storefront
 * content. Anything saved through the admin "Pengaturan Situs" page is stored
 * in the site_settings table and overrides these at runtime (see
 * MY_Controller::_apply_site_settings()).
 */
$config['brand_subtitle']    = 'Digital Asset Store';
$config['store_logo']        = ''; // relative path (assets/uploads/brand/...) or absolute URL; empty = letter mark

$config['hero_eyebrow']      = 'M.O.R.P.H.I.C Marketplace';
$config['hero_title']        = 'Semua alat produksi digital untuk kreator & musisi, dalam satu lisensi.';
$config['hero_description']  = 'VST instrument, preset synth, paket transisi video, sampai LUT color grading sinematik. Setiap pembelian otomatis masuk ke Digital Library dengan serial number dan kuota unduh yang terlacak.';

$config['about_title']       = 'Tentang M.O.R.P.H.I.C';
$config['about_description'] = 'M.O.R.P.H.I.C adalah toko aset digital untuk kreator konten dan musisi. Kami mengurasi plugin, preset, LUT, dan paket suara yang siap dipakai, lalu mengirimkannya lewat Digital Library dengan serial number serta kuota unduh yang dapat diaudit.';

$config['footer_note']       = 'Semua produk digital dikirim melalui Library dengan serial number & kuota unduh yang dapat diaudit.';
$config['footer_copyright']  = 'M.o.r.p.h.i.c Hydra x Shiddiq Project';

/*
| 'store_owner' is the public name shown next to the owner contact info.
| 'store_whatsapp' is the owner's WhatsApp number in international format
| (country code, digits only - no "+" or separators). It powers the
| "Chat via WhatsApp" buttons and the contact block in the footer.
*/
$config['store_owner']       = 'Shiddiq';
$config['store_whatsapp']    = '6285288855892';

/*
|--------------------------------------------------------------------------
| Catalog
|--------------------------------------------------------------------------
| 'products_per_page' is used by the public shop pagination.
| 'account_orders_per_page' is used by the customer order history.
| 'admin_per_page' is used by every admin listing table.
|
*/
$config['products_per_page']        = 9;
$config['account_orders_per_page']  = 10;
$config['admin_per_page']           = 15;
$config['visits_per_page']          = 25;

/*
|--------------------------------------------------------------------------
| Traffic Analytics
|--------------------------------------------------------------------------
| 'dashboard_window_days' is the default period for the dashboard charts.
| 'visit_retention_days' prunes the page_visits log (0 disables pruning).
|
*/
$config['dashboard_window_days'] = 30;
$config['visit_retention_days']  = 180;

/*
|--------------------------------------------------------------------------
| Digital Delivery (Protected Storage)
|--------------------------------------------------------------------------
| 'protected_path' is relative to the FCPATH (project root). Product
| installers must live here and are only ever streamed through the
| Download controller - never linked directly.
|
*/
$config['protected_path']    = 'storage/private_products/';

/*
|--------------------------------------------------------------------------
| Upload Locations (public, relative to FCPATH)
|--------------------------------------------------------------------------
*/
$config['upload_thumbnail_path']    = 'assets/uploads/thumbnails/';
$config['upload_preview_path']      = 'assets/uploads/previews/';
$config['upload_logo_path']         = 'assets/uploads/brand/';
$config['upload_payment_proof_path'] = 'assets/uploads/payment_proofs/';

/*
|--------------------------------------------------------------------------
| Payment Settings
|--------------------------------------------------------------------------
| Manual bank transfer / QRIS verification flow (matches the schema's
| orders.payment_method + orders.payment_proof columns).
|
*/
$config['payment_methods'] = array(
	'bank_transfer'   => 'Bank Transfer',
	'virtual_account' => 'Virtual Account',
	'qris'            => 'QRIS',
);

$config['bank_accounts'] = array(
	array(
		'bank'     => 'Bank Central Asia (BCA)',
		'number'   => '1234567890',
		'holder'   => 'PT Morphic Digital Nusantara',
	),
	array(
		'bank'     => 'Bank Mandiri',
		'number'   => '0987654321',
		'holder'   => 'PT Morphic Digital Nusantara',
	),
);

/*
|--------------------------------------------------------------------------
| Licenses & Downloads
|--------------------------------------------------------------------------
| 'license_key_prefix' is prepended to every generated serial number.
| 'default_max_downloads' mirrors the schema default on user_licenses.
|
*/
$config['license_key_prefix']     = 'MPH';
$config['download_token_prefix']  = 'dl_tok_';
$config['default_max_downloads']  = 10;

/*
|--------------------------------------------------------------------------
| Rate Limiting (Application Level)
|--------------------------------------------------------------------------
| 'max_attempts' failed attempts on an endpoint before the IP is blocked
| for 'block_seconds'. Mirrors the rate_limits table in the schema.
|
*/
$config['rate_limit_max_attempts'] = array(
	'auth_login'    => 5,
	'auth_register' => 5,
	'checkout'      => 10,
	'download'      => 30,
);

$config['rate_limit_block_seconds'] = 900; // 15 minutes
$config['rate_limit_window_seconds'] = 900; // attempts decay after 15 minutes
