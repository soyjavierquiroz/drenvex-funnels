<?php
/**
 * Plugin Name:       Drenvex Funnels
 * Description:       Cliente de captación (funnels) para validar referrals y enviar leads al CORE Drenvex.
 * Version:           0.2.0
 * Author:            Drenvex
 * License:           GPL-2.0-or-later
 * Text Domain:       drenvex-funnels
 */

if (!defined('ABSPATH')) {
	exit;
}

define('DRENVEX_FUNNELS_VERSION', '0.2.0');
define('DRENVEX_FUNNELS_PLUGIN_FILE', __FILE__);
define('DRENVEX_FUNNELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DRENVEX_FUNNELS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Autoloader muy simple (sin Composer) para clases del plugin.
 * Convención: Drenvex_Funnels\Admin\Settings_Page => includes/admin/class-settings-page.php
 */
spl_autoload_register(function ($class) {
	$prefix = 'Drenvex_Funnels\\';
	if (strpos($class, $prefix) !== 0) {
		return;
	}

	$relative = substr($class, strlen($prefix));
	$relative = str_replace('\\', '/', $relative);
	$relative = strtolower($relative);

	$parts = explode('/', $relative);
	$last  = array_pop($parts);

	$subdir = implode('/', $parts);
	$path   = DRENVEX_FUNNELS_PLUGIN_DIR . 'includes/' . ($subdir ? $subdir . '/' : '') . 'class-' . str_replace('_', '-', $last) . '.php';

	if (file_exists($path)) {
		require_once $path;
	}
});

register_activation_hook(__FILE__, function () {
	require_once DRENVEX_FUNNELS_PLUGIN_DIR . 'includes/class-activator.php';
	\Drenvex_Funnels\Activator::activate();
});

register_deactivation_hook(__FILE__, function () {
	require_once DRENVEX_FUNNELS_PLUGIN_DIR . 'includes/class-deactivator.php';
	\Drenvex_Funnels\Deactivator::deactivate();
});

/**
 * Bootstrap del plugin.
 */
add_action('plugins_loaded', function () {
	\Drenvex_Funnels\Plugin::instance()->init();
});
