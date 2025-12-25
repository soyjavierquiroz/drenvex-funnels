<?php
namespace Drenvex_Funnels\Funnels;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resuelve qué página Thrive cargar según:
 * - funnel_slug
 * - contexto (con referral / genérico)
 *
 * NOTA:
 * - No decide negocio.
 * - Solo mapea slugs a Page IDs.
 * - Preparado para múltiples funnels.
 */
final class Funnel_Resolver {

	/**
	 * Devuelve el Page ID de Thrive a renderizar.
	 *
	 * @param string $funnel_slug
	 * @param bool   $has_referral
	 * @return int|null
	 */
	public function resolve_page_id(string $funnel_slug, bool $has_referral): ?int {

		/**
		 * MAPEO CENTRAL DE FUNNELS
		 * ------------------------------------------------
		 * Reemplaza los IDs por los reales de tus páginas Thrive.
		 *
		 * Estructura:
		 * [
		 *   'funnel-slug' => [
		 *       'with_referral' => PAGE_ID,
		 *       'generic'       => PAGE_ID,
		 *   ],
		 * ]
		 */
		$map = [
			'funnel-demo' => [
				'with_referral' => 123, // <-- ID Thrive con personalización
				'generic'       => 456, // <-- ID Thrive genérico
			],
			// Agrega más funnels aquí
		];

		if (!isset($map[$funnel_slug])) {
			return null;
		}

		$key = $has_referral ? 'with_referral' : 'generic';

		return isset($map[$funnel_slug][$key])
			? (int) $map[$funnel_slug][$key]
			: null;
	}
}
