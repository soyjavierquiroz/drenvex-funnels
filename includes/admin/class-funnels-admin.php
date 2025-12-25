<?php
namespace Drenvex_Funnels\Admin;

if (!defined('ABSPATH')) exit;

/**
 * UI de administración para mapear:
 * funnel_slug => URL destino
 *
 * Persistencia simple en wp_options.
 */
final class Funnels_Admin {

	const OPTION_KEY = 'drenvex_funnels_routes';

	public function init(): void {
		add_action('admin_menu', [$this, 'menu']);
		add_action('admin_post_dxf_save_routes', [$this, 'save']);
	}

	public function menu(): void {
		add_menu_page(
			'Drenvex Funnels',
			'Drenvex',
			'manage_options',
			'drenvex-funnels',
			[$this, 'render'],
			'dashicons-randomize'
		);
	}

	public function render(): void {
		if (!current_user_can('manage_options')) {
			wp_die('Sin permisos');
		}

		$routes = get_option(self::OPTION_KEY, []);

		echo '<div class="wrap">';
		echo '<h1>Router de Funnels</h1>';
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		echo '<input type="hidden" name="action" value="dxf_save_routes">';
		wp_nonce_field('dxf_routes_nonce');

		echo '<table class="widefat striped">';
		echo '<thead><tr><th>Funnel Slug</th><th>URL Destino</th></tr></thead><tbody>';

		foreach ($routes as $slug => $url) {
			echo '<tr>';
			echo '<td><input name="slug[]" value="' . esc_attr($slug) . '" /></td>';
			echo '<td><input name="url[]" value="' . esc_url($url) . '" size="60" /></td>';
			echo '</tr>';
		}

		// fila vacía
		echo '<tr>';
		echo '<td><input name="slug[]" placeholder="webinar" /></td>';
		echo '<td><input name="url[]" placeholder="https://funnel.kuruk.in/inicio" size="60" /></td>';
		echo '</tr>';

		echo '</tbody></table>';
		submit_button('Guardar rutas');
		echo '</form></div>';
	}

	public function save(): void {
		check_admin_referer('dxf_routes_nonce');

		$slugs = $_POST['slug'] ?? [];
		$urls  = $_POST['url'] ?? [];

		$data = [];

		foreach ($slugs as $i => $slug) {
			$slug = sanitize_title($slug);
			$url  = esc_url_raw($urls[$i] ?? '');

			if ($slug && $url) {
				$data[$slug] = $url;
			}
		}

		update_option(self::OPTION_KEY, $data, false);
		wp_redirect(admin_url('admin.php?page=drenvex-funnels&saved=1'));
		exit;
	}
}
