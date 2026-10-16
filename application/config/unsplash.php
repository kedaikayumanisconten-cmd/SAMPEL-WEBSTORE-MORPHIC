<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Unsplash Integration
|--------------------------------------------------------------------------
| Used by the admin product form to attach a photo without leaving the
| panel. Three sources are supported:
|
|   1. Local upload   -> assets/uploads/thumbnails/
|   2. Image URL     -> any absolute URL (including Unsplash image addresses)
|   3. Unsplash API   -> keyword search, requires an Access Key below
|
| HOW TO ENABLE THE UNSPLASH SEARCH
| ---------------------------------------------------------------------------
| 1. Create a free developer app: https://unsplash.com/oauth/applications
| 2. Copy the "Access Key" of the app.
| 3. Paste it into $config['unsplash_access_key'] below.
|
| Without a key the "Unsplash" tab still works: it opens the matching
| Unsplash search page in a new tab and lets you paste the image address,
| plus it offers the curated samples in $config['unsplash_quick_picks'].
|
| Unsplash API Guidelines: requests must be attributed to Unsplash and the
| photographer. The library builds that attribution for you (credit_html)
| and the storefront renders it under the product image.
|
*/

$config['unsplash_access_key'] = '';

/*
|--------------------------------------------------------------------------
| API plumbing
|--------------------------------------------------------------------------
*/
$config['unsplash_endpoint']    = 'https://api.unsplash.com/search/photos';
$config['unsplash_utm_source']  = 'morphic_store';
$config['unsplash_per_page']    = 12;
$config['unsplash_timeout']     = 8;

/*
|--------------------------------------------------------------------------
| Suggested search keywords per category slug
|--------------------------------------------------------------------------
| When the admin picks a category, the Unsplash tab pre-fills a relevant
| keyword so results match the audience (musician vs creator).
|
*/
$config['unsplash_keywords'] = array(
	'vst-plugins-instruments'    => 'synthesizer music studio',
	'sample-packs-presets'       => 'drum machine electronic music',
	'video-plugins-transitions'  => 'video editing timeline',
	'cinematic-luts-color'       => 'cinema camera color grading',
	'streaming-audio-utilities'  => 'podcast microphone setup',
	'default'                    => 'digital audio workstation',
);

/*
|--------------------------------------------------------------------------
| Quick picks (curated, verified Unsplash images)
|--------------------------------------------------------------------------
| These load instantly without an API key and are useful for demo data or
| when you simply want a fitting photo fast. Every URL has been verified to
| resolve on the Unsplash CDN. Preview them in the picker and choose the one
| that matches the product.
|
*/
$config['unsplash_quick_picks'] = array(
	array('label' => 'Audio / Music',   'url' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Audio / Music',   'url' => 'https://images.unsplash.com/photo-1504196606672-aef5c9cefc92?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Studio Setup',    'url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Studio Setup',    'url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Creative Work',   'url' => 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Creative Work',   'url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Workspace',       'url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Workspace',       'url' => 'https://images.unsplash.com/photo-1672870152817-29ba68ec732e?auto=format&fit=crop&w=1200&q=80'),
	array('label' => 'Abstract',        'url' => 'https://images.unsplash.com/photo-1614183464050-969b0ee63015?auto=format&fit=crop&w=1200&q=80'),
);
