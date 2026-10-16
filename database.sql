-- ============================================================================
-- M.O.R.P.H.I.C - Digital Software & Asset Store Database Schema
-- Target: Digital Content Creators & Musicians
-- Stack: CodeIgniter 3 (PHP 8.2+) & MySQL / MariaDB
-- Naming Convention: snake_case
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `morphic_store`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `morphic_store`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Table: ci_sessions (CodeIgniter 3 Native Session Table)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE `ci_sessions` (
    `id` varchar(128) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `timestamp` int(10) unsigned DEFAULT 0 NOT NULL,
    `data` blob NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: users
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `full_name` varchar(100) NOT NULL,
    `username` varchar(50) DEFAULT NULL COMMENT 'Optional login alias; email always works too',
    `email` varchar(150) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('owner','super_admin','admin','customer') NOT NULL DEFAULT 'customer',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `last_login_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_username` (`username`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: categories
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `slug` varchar(120) NOT NULL,
    `target_group` enum('creator','musician','both') NOT NULL DEFAULT 'both',
    `description` text DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_categories_slug` (`slug`),
    KEY `idx_categories_target_group` (`target_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: products
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `category_id` int(11) unsigned NOT NULL,
    `title` varchar(150) NOT NULL,
    `slug` varchar(170) NOT NULL,
    `short_description` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `price` decimal(12,2) NOT NULL DEFAULT 0.00,
    `version` varchar(30) NOT NULL DEFAULT '1.0.0',
    `os_support` enum('windows','macos','both','cross_platform') NOT NULL DEFAULT 'both',
    `file_format` varchar(120) NOT NULL COMMENT 'e.g., VST3 / AU, .cube / .prfpset, WAV 24-bit',
    `file_size_mb` decimal(8,2) NOT NULL DEFAULT 0.00,
    `system_requirements` text DEFAULT NULL,
    `thumbnail_url` varchar(255) DEFAULT NULL COMMENT 'Local path (assets/uploads/thumbnails/...) or absolute external URL (e.g. Unsplash)',
    `thumbnail_credit` varchar(255) DEFAULT NULL COMMENT 'Optional attribution shown under the product image, e.g. Photo by Jane Doe on Unsplash',
    `preview_type` enum('audio','video','image','none') NOT NULL DEFAULT 'none',
    `preview_url` varchar(255) DEFAULT NULL COMMENT 'Public path/URL for demo audio/video player',
    `file_path` varchar(255) NOT NULL COMMENT 'Protected storage path outside public webroot or protected folder',
    `requires_license_key` tinyint(1) NOT NULL DEFAULT 1,
    `is_featured` tinyint(1) NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_products_slug` (`slug`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_featured_active` (`is_featured`, `is_active`),
    KEY `idx_products_os_support` (`os_support`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: orders
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_number` varchar(40) NOT NULL,
    `user_id` int(11) unsigned NOT NULL,
    `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` varchar(50) NOT NULL DEFAULT 'bank_transfer',
    `payment_status` enum('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    `payment_proof` varchar(255) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `paid_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_orders_number` (`order_number`),
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_status` (`payment_status`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table: order_items
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` int(11) unsigned NOT NULL,
    `product_id` int(11) unsigned NOT NULL,
    `product_title_snapshot` varchar(150) NOT NULL,
    `price_at_purchase` decimal(12,2) NOT NULL DEFAULT 0.00,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Table: user_licenses (Digital Library & Serial Numbers)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_licenses`;
CREATE TABLE `user_licenses` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL,
    `order_id` int(11) unsigned NOT NULL,
    `product_id` int(11) unsigned NOT NULL,
    `license_key` varchar(80) NOT NULL,
    `download_token` varchar(100) NOT NULL,
    `download_count` int(11) unsigned NOT NULL DEFAULT 0,
    `max_downloads` int(11) unsigned NOT NULL DEFAULT 10,
    `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
    `last_downloaded_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_licenses_key` (`license_key`),
    UNIQUE KEY `idx_user_licenses_token` (`download_token`),
    KEY `idx_user_licenses_user` (`user_id`),
    KEY `idx_user_licenses_order` (`order_id`),
    KEY `idx_user_licenses_product` (`product_id`),
    CONSTRAINT `fk_user_licenses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_licenses_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_licenses_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. Table: rate_limits (Security for Sensitive Endpoints)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `endpoint` varchar(100) NOT NULL COMMENT 'e.g., auth_login, auth_register, checkout, download',
    `attempts` int(11) unsigned NOT NULL DEFAULT 1,
    `blocked_until` datetime DEFAULT NULL,
    `last_attempt_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rate_limits_ip_endpoint` (`ip_address`, `endpoint`),
    KEY `idx_rate_limits_blocked` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. Table: page_visits (Traffic Analytics for the Admin Dashboard)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `page_visits`;
CREATE TABLE `page_visits` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned DEFAULT NULL COMMENT 'NULL for guests',
    `session_id` varchar(64) DEFAULT NULL COMMENT 'Session id, used for unique visitor counts',
    `uri` varchar(160) NOT NULL COMMENT 'Path relative to base_url, e.g. products',
    `referer` varchar(255) DEFAULT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `device` enum('desktop','mobile','tablet','bot') NOT NULL DEFAULT 'desktop',
    `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 when the visitor was a signed-in admin',
    `visited_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_visits_visited_at` (`visited_at`),
    KEY `idx_visits_uri` (`uri`),
    KEY `idx_visits_user` (`user_id`),
    KEY `idx_visits_session` (`session_id`),
    KEY `idx_visits_device` (`device`),
    CONSTRAINT `fk_visits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SEED DATA (CONTOH DATA AWAL M.O.R.P.H.I.C)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Seed 1: Users
-- Default password untuk akun seed: password (atau ganti melalui fitur hash CI3)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Morphic Master Admin', 'admin', 'admin@morphic.store', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW()),
(2, 'Raka Pratama (Video Editor)', 'raka', 'raka.creator@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, NOW()),
(3, 'Nadia Symphony (Music Producer)', 'nadia', 'nadia.music@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, NOW()),
(4, 'Store Owner', 'owner', 'owner@morphic.store', '$2y$10$HPLRAyNLE9fPokiB7pzb6Oo.F/QNUdSjFRy8Fu5kBVTnE2f.I4pVO', 'owner', 1, NOW()),
(5, 'Super Administrator', 'superadmin', 'superadmin@morphic.store', '$2y$10$d8CYwhEjOQUR.kRSLLyMHuihKK2mMHVrsPLWHw/JNu8dGiq1pJK9K', 'super_admin', 1, NOW());

-- ----------------------------------------------------------------------------
-- Seed 2: Categories (Creator & Musician)
-- ----------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `slug`, `target_group`, `description`, `is_active`) VALUES
(1, 'VST Plugins & Virtual Instruments', 'vst-plugins-instruments', 'musician', 'Synthesizer, efek mixing/mastering, dan instrumen virtual untuk DAW favorit Anda.', 1),
(2, 'Sample Packs & Synth Presets', 'sample-packs-presets', 'musician', 'Koleksi loop audio royalty-free, drum one-shots, dan preset untuk Serum, Vital, serta Massive.', 1),
(3, 'Video Editing Plugins & Transitions', 'video-plugins-transitions', 'creator', 'Plugin transisi mulus, motion graphics template, dan efek visual untuk Premiere Pro, After Effects, & DaVinci Resolve.', 1),
(4, 'Cinematic LUTs & Color Grading', 'cinematic-luts-color', 'creator', 'Paket Color Grading LUTs (.cube) profesional untuk kamera Sony, Canon, RED, dan profil Log.', 1),
(5, 'Streaming & Audio Utility Tools', 'streaming-audio-utilities', 'both', 'Software pendukung live streaming, routing audio virtual, dan noise suppression untuk kreator & musisi.', 1);

-- ----------------------------------------------------------------------------
-- Seed 3: Products (6 Produk: 3 Musician, 2 Creator, 1 Hybrid)
-- ----------------------------------------------------------------------------
INSERT INTO `products` (
    `id`, `category_id`, `title`, `slug`, `short_description`, `description`,
    `price`, `version`, `os_support`, `file_format`, `file_size_mb`, `system_requirements`,
    `thumbnail_url`, `preview_type`, `preview_url`, `file_path`,
    `requires_license_key`, `is_featured`, `is_active`
) VALUES
-- Produk Musisi 1
(
    1, 1, 'MORPH-SYNTH: Analog Wavetable VST3', 'morph-synth-analog-wavetable-vst3',
    'Synthesizer wavetable modern dengan karakter filter analog hangat dan 200+ factory presets.',
    'MORPH-SYNTH adalah plugin instrumen virtual generasi baru untuk produser musik elektronik, synthwave, dan film scoring. Dilengkapi dengan dual oscillator wavetable, sub-bass generator, serta modulasi LFO drag-and-drop yang intuitif.',
    450000.00, '2.1.0', 'both', 'VST3, AU, AAX (64-bit)', 340.50,
    'Windows 10/11 (64-bit) atau macOS 12+ (Intel & Apple Silicon M1/M2/M3). RAM minimal 8GB, mendukung Ableton Live, FL Studio, Logic Pro, Cubase.',
    'assets/uploads/thumbnails/morph-synth.jpg', 'audio', 'assets/uploads/previews/morph-synth-demo.mp3',
    'storage/private_products/morph_synth_v2_1_0_installer.zip', 1, 1, 1
),
-- Produk Musisi 2
(
    2, 1, 'AetherVerb Pro: Shimmer & Space Reverb', 'aetherverb-pro-shimmer-reverb',
    'Plugin algorithmic reverb dengan pitch-shifting shimmer untuk gitar ambient, vokal, dan synth pad.',
    'Ciptakan ruang akustik raksasa mulai dari ruangan studio kecil hingga atmosfer sinematik tak terbatas. AetherVerb Pro memiliki fitur ducking otomatis sehingga ekor reverb tidak membuat frekuensi utama menjadi keruh (muddy).',
    320000.00, '1.4.2', 'both', 'VST3, AU (64-bit)', 125.00,
    'Windows 10+ atau macOS 11+. CPU Dual Core 2.5GHz+, RAM 4GB.',
    'assets/uploads/thumbnails/aetherverb-pro.jpg', 'audio', 'assets/uploads/previews/aetherverb-demo.mp3',
    'storage/private_products/aetherverb_pro_v1_4_2.zip', 1, 1, 1
),
-- Produk Musisi 3
(
    3, 2, 'Cyberpunk Odyssey: Serum Presets & WAV Loops', 'cyberpunk-odyssey-serum-presets-loops',
    '120+ preset Xfer Serum bergaya dark-synth & midtempo beserta 250+ drum loops 24-bit WAV.',
    'Paket suara wajib untuk produser musik elektronik, game soundtrack, dan trailer action. Semua file WAV sudah memiliki label BPM dan Key serta 100% Royalty-Free untuk rilisan komersial.',
    185000.00, '1.0.0', 'cross_platform', 'WAV 24-bit, .fxp (Serum Presets), MIDI', 890.00,
    'Membutuhkan Xfer Serum v1.36+ untuk membuka file preset. File WAV dan MIDI dapat digunakan di semua DAW.',
    'assets/uploads/thumbnails/cyberpunk-odyssey.jpg', 'audio', 'assets/uploads/previews/cyberpunk-odyssey-demo.mp3',
    'storage/private_products/cyberpunk_odyssey_pack.zip', 0, 0, 1
),
-- Produk Creator 1
(
    4, 3, 'KineticFlow: 500+ Seamless Video Transitions', 'kineticflow-500-seamless-transitions',
    'Koleksi plugin transisi glitch, zoom, spin, dan light leaks siap pakai untuk Premiere Pro & DaVinci Resolve.',
    'Hemat waktu editing hingga 70% dengan KineticFlow. Mendukung resolusi hingga 4K/8K, frame rate fleksibel, dan dilengkapi sound effects (SFX) bawaan di setiap transisi.',
    295000.00, '3.0.1', 'both', '.prfpset, .mogrt, .drfx', 620.00,
    'Adobe Premiere Pro CC 2022+ atau DaVinci Resolve 18/19 (Free & Studio).',
    'assets/uploads/thumbnails/kineticflow-transitions.jpg', 'video', 'assets/uploads/previews/kineticflow-preview.mp4',
    'storage/private_products/kineticflow_v3_0_1_bundle.zip', 1, 1, 1
),
-- Produk Creator 2
(
    5, 4, 'NeoNoir Master LUTs Collection (60 Cinematic LUTs)', 'neonoir-master-luts-collection',
    '60 LUTs sinematik (.cube) untuk profil S-Log3, C-Log, V-Log, D-Log, dan Rec.709 standar.',
    'Dirancang oleh colorist profesional untuk menghasilkan tone warna film box office modern. Termasuk folder khusus untuk konversi Log-to-Rec709 serta folder creative look (Teal & Orange, Cyberpunk, Vintage Kodak, Moody Matte).',
    175000.00, '1.2.0', 'cross_platform', '.cube (33x33x33 Grid)', 85.40,
    'Kompatibel dengan Premiere Pro, DaVinci Resolve, Final Cut Pro X, CapCut Desktop, Sony Vegas, dan monitor eksternal Atomos.',
    'assets/uploads/thumbnails/neonoir-luts.jpg', 'image', 'assets/uploads/previews/neonoir-before-after.jpg',
    'storage/private_products/neonoir_luts_v1_2.zip', 0, 1, 1
),
-- Produk Creator & Musisi (Both)
(
    6, 5, 'VoxClean AI: Real-Time Mic Noise & Reverb Remover', 'voxclean-ai-realtime-noise-remover',
    'Software standalone & VST3 untuk menjernihkan suara mikrofon saat live streaming, podcast, dan rekaman vokal.',
    'Hapus suara kipas PC, AC, ketikan keyboard mekanikal, dan pantulan gema ruangan secara real-time dengan latensi ultra-rendah (< 5ms). Bekerja langsung di OBS Studio, Discord, maupun di dalam DAW.',
    390000.00, '1.1.0', 'windows', 'Standalone (.exe) & VST3 (64-bit)', 210.00,
    'Windows 10/11 (64-bit), CPU Intel Core i5 Gen 8 / Ryzen 5 3000 ke atas, RAM 8GB.',
    'assets/uploads/thumbnails/voxclean-ai.jpg', 'audio', 'assets/uploads/previews/voxclean-demo.mp3',
    'storage/private_products/voxclean_ai_win_v1_1_0.zip', 1, 0, 1
);

-- ----------------------------------------------------------------------------
-- Seed 4: Sample Orders & Order Items (Simulasi Transaksi Pelanggan)
-- ----------------------------------------------------------------------------
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total_amount`, `payment_method`, `payment_status`, `paid_at`, `created_at`) VALUES
(1, 'MPH-20261015-0001', 2, 470000.00, 'qris', 'paid', NOW(), NOW()),
(2, 'MPH-20261015-0002', 3, 770000.00, 'virtual_account', 'paid', NOW(), NOW()),
(3, 'MPH-20261015-0003', 2, 390000.00, 'bank_transfer', 'pending', NULL, NOW());

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_title_snapshot`, `price_at_purchase`) VALUES
-- Order 1 milik Raka (Video Editor): KineticFlow + NeoNoir LUTs
(1, 1, 4, 'KineticFlow: 500+ Seamless Video Transitions', 295000.00),
(2, 1, 5, 'NeoNoir Master LUTs Collection (60 Cinematic LUTs)', 175000.00),
-- Order 2 milik Nadia (Music Producer): MORPH-SYNTH + AetherVerb Pro
(3, 2, 1, 'MORPH-SYNTH: Analog Wavetable VST3', 450000.00),
(4, 2, 2, 'AetherVerb Pro: Shimmer & Space Reverb', 320000.00),
-- Order 3 milik Raka (Pending): VoxClean AI
(5, 3, 6, 'VoxClean AI: Real-Time Mic Noise & Reverb Remover', 390000.00);

-- ----------------------------------------------------------------------------
-- Seed 5: User Licenses (Hanya untuk Order yang sudah PAID)
-- ----------------------------------------------------------------------------
INSERT INTO `user_licenses` (
    `id`, `user_id`, `order_id`, `product_id`,
    `license_key`, `download_token`, `download_count`, `max_downloads`, `is_revoked`
) VALUES
(1, 2, 1, 4, 'MPH-KFLW-8821-9904-ABCD', 'dl_tok_9f8e7d6c5b4a3f2e1d0c9b8a7f6e5d4c', 1, 10, 0),
(2, 2, 1, 5, 'NO-KEY-REQUIRED-ITEM-0002', 'dl_tok_1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d', 2, 10, 0),
(3, 3, 2, 1, 'MPH-SYNT-4410-7732-XZYW', 'dl_tok_5f4e3d2c1b0a9f8e7d6c5b4a3f2e1d0c', 1, 10, 0),
(4, 3, 2, 2, 'MPH-ATVB-1198-3345-LMNP', 'dl_tok_7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f', 0, 10, 0);

SET FOREIGN_KEY_CHECKS = 1;
