<?php
namespace Drenvex_Funnels;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Activación del plugin.
 *
 * Responsabilidad:
 * - Registrar rewrite rules mínimas
 * - Flush de reglas
 *
 * NO debe depender de métodos internos del Router.
 */
final class Activator {

	public static function activate(): void {

		// Inicializar options si no existen
		if (get_option(Plugin::OPTION_KEY, null) === null) {
			add_option(Plugin::OPTION_KEY, [
				'core_base_url' => '',
				'core_api_key'  => '',
			], '', false);
		}

		// Registrar rewrite rule mínima para /r/{ref}/{slug}
		add_rewrite_rule(
			'^r/([^/]+)/([^/]+)/?$',
			'index.php?dxf_ref=$matches[1]&dxf_funnel=$matches[2]',
			'top'
		);

		flush_rewrite_rules();
	}
}
