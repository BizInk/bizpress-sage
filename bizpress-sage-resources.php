<?php
/**
 * Plugin Name: BizPress Sage Resources
 * Description: Show Sage resources on your site. Automatically updated by the Bizink team.
 * Plugin URI: https://bizinkonline.com
 * Author: Bizink
 * Author URI: https://bizinkonline.com
 * Version: 1.2
 * Text Domain: bizink-client-sage
 * Domain Path: /languages
 */

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Updater
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker('https://github.com/BizInk/bizpress-sage',__FILE__,'bizpress-sage-resources');
$myUpdateChecker->setBranch('master');
$myUpdateChecker->setAuthentication('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');

if(is_plugin_active("bizpress-client/bizink-client.php")){
	require 'sage-resources.php';
}