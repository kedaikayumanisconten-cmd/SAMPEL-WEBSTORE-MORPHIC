<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront shell: header + content + footer.
 *
 * @var string $content_view
 */
$this->load->view('layouts/partials/header');
$this->load->view($content_view);
$this->load->view('layouts/partials/footer');
