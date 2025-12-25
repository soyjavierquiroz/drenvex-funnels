<?php
/**
 * Uninstall del plugin.
 *
 * Regla:
 * - Solo borrar settings si el admin desinstala explícitamente.
 * - No hay datos de negocio aquí (no hay leads locales).
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('drenvex_funnels_settings');
