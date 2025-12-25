<?php
namespace Drenvex_Funnels\Routing;

use Drenvex_Funnels\Plugin;

if (!defined('ABSPATH')) exit;

final class Router {

	const QV_REF = 'dxf_ref';
	const QV_FUN = 'dxf_funnel';

	public function __construct(private Plugin $plugin) {}

	public function init(): void {
		add_action('init', [$this, 'rewrites']);
		add_filter('query_vars', [$this, 'vars']);
		add_action('template_redirect', [$this, 'handle']);
	}

	public function rewrites(): void {
		add_rewrite_rule(
			'^r/([^/]+)/([^/]+)/?$',
			'index.php?' . self::QV_REF . '=$matches[1]&' . self::QV_FUN . '=$matches[2]',
			'top'
		);
	}

	public function vars(array $vars): array {
		$vars[] = self::QV_REF;
		$vars[] = self::QV_FUN;
		return $vars;
	}

	public function handle(): void {
		$ref   = (string) get_query_var(self::QV_REF);
		$slug = (string) get_query_var(self::QV_FUN);

		if (!$slug) return;

		$routes = get_option('drenvex_funnels_routes', []);
		if (!isset($routes[$slug])) {
			wp_die('Funnel no configurado');
		}

		$target = $routes[$slug];
		$params = ['dx_funnel' => $slug];

		// Validar referral (NO bloquea redirect)
		$valid = false;
		if ($ref) {
			$res = $this->plugin->core()->validate_referral($ref);
			if ($res['ok'] && !empty($res['data']['valid'])) {
				$valid = true;
				$params['dx_ref'] = $ref;
			}
		}

		$url = add_query_arg($params, $target);

		wp_redirect($url, 302);
		exit;
	}
}
