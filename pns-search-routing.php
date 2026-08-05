<?php
/*
 * x-release-please-start-version
 */
/**
 * Plugin Name: PNS Search Routing
 * Description: Canonical WordPress search routes and editorial search policy for Protests and Suffragettes.
 * Version: 0.2.0
 * Author: Protests and Suffragettes
 * Text Domain: pns-search-routing
 * Requires at least: 6.5
 * Requires PHP: 8.0
 *
 * @package PNS_Search_Routing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PNS_SEARCH_ROUTING_VERSION', '0.2.0' );
/*
 * x-release-please-end
 */
define( 'PNS_SEARCH_ROUTING_PLUGIN_FILE', __FILE__ );
define( 'PNS_SEARCH_ROUTING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once PNS_SEARCH_ROUTING_PLUGIN_DIR . 'includes/SearchRouting.php';

\PNS\SearchRouting\SearchRouting::register();
