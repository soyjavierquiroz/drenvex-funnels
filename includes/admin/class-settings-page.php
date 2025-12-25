<?php
namespace Drenvex_Funnels\Admin;

use Drenvex_Funnels\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Página de settings del plugin (básica).
 *
 * Importante:
 * - La API Key es privada (solo admin). No se expone al frontend.
 * - El plugin no implementa lógica de negocio del CORE.
 */
final class Settings_Page {

	/**
	 * Registra hooks para settings.
	 */
	public function init(): void {
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_init', [$this, 'register_settings']);
	}

	public function register_menu(): void {
		add_options_page(
			'Drenvex Funnels',
			'Drenvex Funnels',
			'manage_options',
			'drenvex-funnels',
			[$this, 'render_page']
		);
	}

	public function register_settings(): void {
		register_setting(
			'drenvex_funnels_settings_group',
			Plugin::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [$this, 'sanitize_settings'],
				'default'           => [
					'core_base_url' => '',
					'core_api_key'  => '',
				],
			]
		);

		add_settings_section(
			'drenvex_funnels_section_core',
			'Conexión con el CORE',
			function () {
				echo '<p>Configura el acceso privado al CORE de Drenvex. El CORE decide todo; este sitio solo presenta y envía datos.</p>';
			},
			'drenvex-funnels'
		);

		add_settings_field(
			'drenvex_funnels_core_base_url',
			'CORE Base URL',
			[$this, 'render_field_core_base_url'],
			'drenvex-funnels',
			'drenvex_funnels_section_core'
		);

		add_settings_field(
			'drenvex_funnels_core_api_key',
			'CORE API Key (privada)',
			[$this, 'render_field_core_api_key'],
			'drenvex-funnels',
			'drenvex_funnels_section_core'
		);
	}

	/**
	 * Sanitiza settings (admin only).
	 */
	public function sanitize_settings($input): array {
		$out = [
			'core_base_url' => '',
			'core_api_key'  => '',
		];

		if (is_array($input)) {
			if (isset($input['core_base_url'])) {
				// Normaliza URL (sin asumir formato).
				$url = trim((string) $input['core_base_url']);
				$out['core_base_url'] = esc_url_raw($url);
			}

			if (isset($input['core_api_key'])) {
				// Mantenerlo como texto, sin mostrar en frontend.
				$out['core_api_key'] = sanitize_text_field((string) $input['core_api_key']);
			}
		}

		return $out;
	}

	public function render_field_core_base_url(): void {
		$settings = Plugin::instance()->get_settings();
		$value = isset($settings['core_base_url']) ? (string) $settings['core_base_url'] : '';

		echo '<input type="url" class="regular-text" name="' . esc_attr(Plugin::OPTION_KEY) . '[core_base_url]" value="' . esc_attr($value) . '" placeholder="https://drenvex.com" />';
		echo '<p class="description">Dominio base del CORE (ej: https://drenvex.com). No inventamos rutas aquí; se usarán en pasos posteriores.</p>';
	}

	public function render_field_core_api_key(): void {
		$settings = Plugin::instance()->get_settings();
		$value = isset($settings['core_api_key']) ? (string) $settings['core_api_key'] : '';

		echo '<input type="password" class="regular-text" name="' . esc_attr(Plugin::OPTION_KEY) . '[core_api_key]" value="' . esc_attr($value) . '" autocomplete="new-password" />';
		echo '<p class="description">Clave privada para autenticar este funnel site ante el CORE. Se guarda en options (admin) y no se expone al visitante.</p>';
	}

	public function render_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die('No tienes permisos para acceder a esta página.');
		}

		echo '<div class="wrap">';
		echo '<h1>Drenvex Funnels</h1>';
		echo '<form method="post" action="options.php">';

		settings_fields('drenvex_funnels_settings_group');
		do_settings_sections('drenvex-funnels');
		submit_button('Guardar configuración');

		echo '</form>';
		echo '</div>';
	}
}
