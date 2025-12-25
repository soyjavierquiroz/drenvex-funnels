<?php
namespace Drenvex_Funnels;

use Drenvex_Funnels\Http\Core_Client;
use Drenvex_Funnels\Admin\Settings_Page;
use Drenvex_Funnels\Admin\Funnels_Admin;
use Drenvex_Funnels\Routing\Router;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Clase principal del plugin Drenvex Funnels.
 *
 * Responsabilidades:
 * - Inicializar UI de administración
 * - Inicializar router /r/{referral}/{slug}
 * - Proveer acceso centralizado al cliente del CORE
 *
 * NO contiene lógica de negocio.
 */
final class Plugin {

	/**
	 * Instancia singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Option donde se guarda la configuración general del plugin
	 * (API Key, CORE Base URL).
	 */
	const OPTION_KEY = 'drenvex_funnels_settings';

	/**
	 * Cliente HTTP hacia el CORE.
	 *
	 * @var Core_Client|null
	 */
	private $core_client = null;

	/**
	 * Obtiene la instancia única del plugin.
	 */
	public static function instance(): Plugin {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor privado (singleton).
	 */
	private function __construct() {}

	/**
	 * Inicializa todos los componentes del plugin.
	 *
	 * Se ejecuta en `plugins_loaded`.
	 */
	public function init(): void {

		/**
		 * ==============================
		 * ADMINISTRACIÓN
		 * ==============================
		 */
		if (is_admin()) {

			// Settings generales (CORE Base URL + API Key)
			$settings = new Settings_Page();
			$settings->init();

			// UI del Router de Funnels (slug => URL destino)
			$funnels_admin = new Funnels_Admin();
			$funnels_admin->init();
		}

		/**
		 * ==============================
		 * CLIENTE DEL CORE
		 * ==============================
		 */
		$this->core_client = new Core_Client($this);

		/**
		 * ==============================
		 * ROUTER CANÓNICO /r/
		 * ==============================
		 */
		$router = new Router($this);
		$router->init();
	}

	/**
	 * Devuelve la configuración del plugin con defaults seguros.
	 */
	public function get_settings(): array {
		$defaults = [
			'core_base_url' => '', // Ej: https://drenvex.com
			'core_api_key'  => '', // API Key privada
		];

		$saved = get_option(self::OPTION_KEY, []);
		if (!is_array($saved)) {
			$saved = [];
		}

		return array_merge($defaults, $saved);
	}

	/**
	 * Actualiza configuración del plugin.
	 *
	 * @param array $settings
	 */
	public function update_settings(array $settings): void {
		update_option(self::OPTION_KEY, $settings, false);
	}

	/**
	 * Acceso centralizado al cliente del CORE.
	 */
	public function core(): Core_Client {
		// Defensa por si alguien llama antes de init()
		if ($this->core_client === null) {
			$this->core_client = new Core_Client($this);
		}
		return $this->core_client;
	}
}
