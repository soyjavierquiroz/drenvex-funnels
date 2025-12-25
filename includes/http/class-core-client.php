<?php
namespace Drenvex_Funnels\Http;

use Drenvex_Funnels\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cliente HTTP para comunicarse con el CORE (API-first).
 *
 * Principios:
 * - El CORE decide todo.
 * - Este cliente NO interpreta reglas de negocio.
 * - Maneja errores de red/HTTP y devuelve estructuras simples.
 *
 * En PASO 2 implementamos SOLO:
 * - Validación de referral:
 *   GET /wp-json/drenvex/v1/referral/{referral_code}
 *   Header: X-DX-API-KEY: ...
 */
final class Core_Client {

	/**
	 * Header oficial para auth.
	 */
	const AUTH_HEADER = 'X-DX-API-KEY';

	/**
	 * Timeout breve: funnels requieren velocidad.
	 */
	const HTTP_TIMEOUT_SECONDS = 4;

	/**
	 * Cache corto para validación (idempotente/cacheable en CORE).
	 * OJO: esto es cache “de UX”, NO fuente de verdad.
	 */
	const REFERRAL_CACHE_TTL_SECONDS = 45;

	/**
	 * Prefijo de transient.
	 */
	const TRANSIENT_PREFIX = 'dxf_ref_valid_';

	/**
	 * @var Plugin
	 */
	private $plugin;

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * Valida un referral code consultando al CORE.
	 *
	 * Retorno (array):
	 * - ok: bool (true si la llamada fue exitosa y el CORE respondió algo parseable)
	 * - http_status: int|null
	 * - data: array|null (respuesta JSON)
	 * - error_code: string|null (dx_unauthorized, dx_rate_limited, network_error, invalid_json, misconfigured, etc.)
	 * - error_message: string|null (mensaje para logs internos, no necesariamente para usuario)
	 *
	 * Nota: No interpreta motivos de invalidación. Solo respeta { valid: true/false }.
	 */
	public function validate_referral(string $referral_code): array {
		$referral_code = trim($referral_code);

		if ($referral_code === '') {
			return [
				'ok' => false,
				'http_status' => null,
				'data' => null,
				'error_code' => 'invalid_input',
				'error_message' => 'Referral code vacío.',
			];
		}

		$settings = $this->plugin->get_settings();
		$base_url = isset($settings['core_base_url']) ? trim((string) $settings['core_base_url']) : '';
		$api_key  = isset($settings['core_api_key']) ? trim((string) $settings['core_api_key']) : '';

		if ($base_url === '' || $api_key === '') {
			return [
				'ok' => false,
				'http_status' => null,
				'data' => null,
				'error_code' => 'misconfigured',
				'error_message' => 'Falta CORE Base URL o CORE API Key en settings.',
			];
		}

		// Cache corto por referral code (UX/performance).
		$cache_key = self::TRANSIENT_PREFIX . md5(strtolower($referral_code));
		$cached = get_transient($cache_key);
		if (is_array($cached) && isset($cached['ok'])) {
			return $cached;
		}

		$endpoint = rtrim($base_url, '/') . '/wp-json/drenvex/v1/referral/' . rawurlencode($referral_code);

		$args = [
			'timeout' => self::HTTP_TIMEOUT_SECONDS,
			'headers' => [
				self::AUTH_HEADER => $api_key,
				'Accept' => 'application/json',
				'User-Agent' => 'DrenvexFunnels/' . (defined('DRENVEX_FUNNELS_VERSION') ? DRENVEX_FUNNELS_VERSION : 'unknown'),
			],
		];

		$response = wp_remote_get($endpoint, $args);

		if (is_wp_error($response)) {
			$result = [
				'ok' => false,
				'http_status' => null,
				'data' => null,
				'error_code' => 'network_error',
				'error_message' => $response->get_error_message(),
			];

			// Cache muy breve incluso para errores de red (evita “thundering herd”).
			set_transient($cache_key, $result, 10);
			return $result;
		}

		$http_status = (int) wp_remote_retrieve_response_code($response);
		$body        = (string) wp_remote_retrieve_body($response);

		// Manejo explícito de códigos contractuales.
		if ($http_status === 401) {
			$result = [
				'ok' => false,
				'http_status' => 401,
				'data' => $this->safe_decode_json($body),
				'error_code' => 'dx_unauthorized',
				'error_message' => 'Unauthorized desde CORE (API key inválida o faltante).',
			];
			set_transient($cache_key, $result, 10);
			return $result;
		}

		if ($http_status === 429) {
			$result = [
				'ok' => false,
				'http_status' => 429,
				'data' => $this->safe_decode_json($body),
				'error_code' => 'dx_rate_limited',
				'error_message' => 'Rate limited desde CORE.',
			];
			set_transient($cache_key, $result, 15);
			return $result;
		}

		// Para este endpoint, el contrato indica HTTP 200 incluso para valid:false
		if ($http_status !== 200) {
			$result = [
				'ok' => false,
				'http_status' => $http_status,
				'data' => $this->safe_decode_json($body),
				'error_code' => 'unexpected_http_status',
				'error_message' => 'HTTP status inesperado desde CORE: ' . $http_status,
			];
			set_transient($cache_key, $result, 10);
			return $result;
		}

		$data = $this->safe_decode_json($body);
		if ($data === null || !is_array($data) || !array_key_exists('valid', $data)) {
			$result = [
				'ok' => false,
				'http_status' => 200,
				'data' => is_array($data) ? $data : null,
				'error_code' => 'invalid_json',
				'error_message' => 'Respuesta JSON inválida o sin clave "valid".',
			];
			set_transient($cache_key, $result, 10);
			return $result;
		}

		$result = [
			'ok' => true,
			'http_status' => 200,
			'data' => $data,
			'error_code' => null,
			'error_message' => null,
		];

		// Cache corto (UX). No es verdad: si el CORE cambia, expira rápido.
		set_transient($cache_key, $result, self::REFERRAL_CACHE_TTL_SECONDS);

		return $result;
	}

	/**
	 * Decodifica JSON de forma segura.
	 * Devuelve array|null.
	 */
	private function safe_decode_json(string $body): ?array {
		$body = trim($body);
		if ($body === '') {
			return null;
		}

		$decoded = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}

		return is_array($decoded) ? $decoded : null;
	}
}
