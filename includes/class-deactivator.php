<?php
namespace Drenvex_Funnels;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Desactivación del plugin.
 *
 * - Flush de rewrite rules para limpiar rutas.
 * - NO borra settings: se considera configuración del sitio.
 */
final class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
