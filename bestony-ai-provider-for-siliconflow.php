<?php

/**
 * Plugin Name:       Bestony AI Provider for SiliconFlow
 * Plugin URI:        https://github.com/bestony/AI-Provider-for-SiliconFlow
 * Description:       SiliconFlow provider for the WordPress AI Client.
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Bestony
 * Author URI:        https://github.com/bestony
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       bestony-ai-provider-for-siliconflow
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider;

use SiliconFlow\AiProvider\Provider\SiliconflowProvider;
use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use WordPress\AiClient\AiClient;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Register the SiliconFlow provider before the Connectors screen builds its cards.
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();
    if ($registry->hasProvider(SiliconflowProvider::class)) {
        return;
    }

    $registry->registerProvider(SiliconflowProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);

/**
 * Put a configured model first while retaining entries supplied by other providers.
 *
 * @param mixed  $preferredModels Existing [provider, model] tuples.
 * @param string $modelId          The model to prefer.
 * @return array<int, array{string, string}> Filtered preference tuples.
 */
function prefer_model($preferredModels, string $modelId): array
{
    $preferred = is_array($preferredModels) ? array_values($preferredModels) : [];
    if (!SiliconflowConfig::hasCredentials() || $modelId === '') {
        return $preferred;
    }

    $result = [[SiliconflowConfig::PROVIDER_ID, $modelId]];
    foreach ($preferred as $entry) {
        if (!is_array($entry) || count($entry) < 2) {
            continue;
        }
        $entry = array_values($entry);
        if (!is_scalar($entry[0]) || !is_scalar($entry[1])) {
            continue;
        }
        if ($entry[0] === SiliconflowConfig::PROVIDER_ID && $entry[1] === $modelId) {
            continue;
        }
        $result[] = [(string) $entry[0], (string) $entry[1]];
    }

    return $result;
}

/**
 * @param mixed $preferredModels Existing preference tuples.
 * @return array<int, array{string, string}> Updated tuples.
 */
function prefer_text_models($preferredModels): array
{
    return prefer_model($preferredModels, SiliconflowConfig::getDefaultModelId());
}

/**
 * @param mixed $preferredModels Existing preference tuples.
 * @return array<int, array{string, string}> Updated tuples.
 */
function prefer_vision_models($preferredModels): array
{
    return prefer_model($preferredModels, SiliconflowConfig::getDefaultModelId());
}

/**
 * @param mixed $preferredModels Existing preference tuples.
 * @return array<int, array{string, string}> Updated tuples.
 */
function prefer_image_models($preferredModels): array
{
    return prefer_model($preferredModels, SiliconflowConfig::getImageModelId());
}

add_filter('wpai_preferred_text_models', __NAMESPACE__ . '\\prefer_text_models');
add_filter('wpai_preferred_vision_models', __NAMESPACE__ . '\\prefer_vision_models');
add_filter('wpai_preferred_image_models', __NAMESPACE__ . '\\prefer_image_models');
