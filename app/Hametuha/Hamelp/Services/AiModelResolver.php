<?php
/**
 * AI model resolution and enumeration.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Services;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Bridges the WordPress 7.0 AI client registry with Hamelp settings.
 *
 * Everything here reads the *live* registry so that connectors toggled on or
 * off outside Hamelp (Settings → Connectors) are always reflected. A stored
 * model selection is never trusted blindly: it is validated against the
 * currently configured providers on every request, and a selection that no
 * longer exists silently falls back to auto-selection rather than pinning a
 * dead model endpoint.
 */
class AiModelResolver {

	/**
	 * Option holding the preferred model, formatted `provider_id|model_id`.
	 *
	 * Empty string means "auto-select".
	 *
	 * @var string
	 */
	const OPTION_MODEL = 'hamelp_ai_model';

	/**
	 * Option holding the sampling temperature.
	 *
	 * Empty string means "omit the parameter" and is the default, since omitting
	 * is safe for every model while some (e.g. Claude Opus) reject a temperature.
	 *
	 * @var string
	 */
	const OPTION_TEMPERATURE = 'hamelp_ai_temperature';

	/**
	 * Separator between provider id and model id in the stored option value.
	 *
	 * @var string
	 */
	const SEPARATOR = '|';

	/**
	 * Whether the WordPress AI client is available in this environment.
	 *
	 * @return bool
	 */
	public static function is_ai_available(): bool {
		return function_exists( 'wp_ai_client_prompt' )
			&& function_exists( 'wp_supports_ai' )
			&& wp_supports_ai()
			&& class_exists( '\WordPress\AiClient\AiClient' );
	}

	/**
	 * Enumerate configured providers/models capable of text generation.
	 *
	 * Only providers that are currently configured (connector enabled and
	 * authenticated) are returned, so the list mirrors the live registry.
	 *
	 * @return array<string, string> Map of `provider_id|model_id` => `Provider / Model` label.
	 */
	public static function get_available_models(): array {
		if ( ! self::is_ai_available() ) {
			return [];
		}

		$models = [];
		try {
			$registry     = AiClient::defaultRegistry();
			$requirements = new ModelRequirements( [ CapabilityEnum::textGeneration() ], [] );
			foreach ( $registry->findModelsMetadataForSupport( $requirements ) as $provider_models ) {
				$provider      = $provider_models->getProvider();
				$provider_id   = $provider->getId();
				$provider_name = $provider->getName();
				foreach ( $provider_models->getModels() as $model ) {
					$key            = $provider_id . self::SEPARATOR . $model->getId();
					$models[ $key ] = sprintf( '%s / %s', $provider_name, $model->getName() );
				}
			}
		} catch ( \Throwable $e ) {
			// Registry not ready or provider misbehaving: degrade to auto-select.
			return [];
		}

		return $models;
	}

	/**
	 * Resolve the effective model preference for a generation request.
	 *
	 * Returns a `[ provider_id, model_id ]` pair suitable for the AI client's
	 * model preference API, or null to let the client auto-select. A stored
	 * selection that is no longer available resolves to null.
	 *
	 * @return array{0: string, 1: string}|null
	 */
	public static function get_effective_model_preference(): ?array {
		$stored = (string) get_option( self::OPTION_MODEL, '' );
		if ( '' === $stored ) {
			return null;
		}

		// Validate against the live registry so a removed/disabled model is dropped.
		$available = self::get_available_models();
		if ( ! isset( $available[ $stored ] ) ) {
			return null;
		}

		$parts = explode( self::SEPARATOR, $stored, 2 );
		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
			return null;
		}

		return [ $parts[0], $parts[1] ];
	}

	/**
	 * Resolve the effective temperature for a generation request.
	 *
	 * @return float|null Temperature, or null to omit the parameter entirely.
	 */
	public static function get_effective_temperature(): ?float {
		$stored = get_option( self::OPTION_TEMPERATURE, '' );
		if ( '' === $stored || null === $stored ) {
			return null;
		}
		return (float) $stored;
	}

	/**
	 * Whether a stored model selection has become unavailable.
	 *
	 * Used by the settings screen to warn that a previously chosen model is no
	 * longer offered (e.g. its connector was disabled outside Hamelp).
	 *
	 * @return bool
	 */
	public static function is_stored_model_stale(): bool {
		$stored = (string) get_option( self::OPTION_MODEL, '' );
		if ( '' === $stored ) {
			return false;
		}
		$available = self::get_available_models();
		return ! isset( $available[ $stored ] );
	}
}
