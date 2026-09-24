<?php

/**
 * SiliconFlow configuration reader.
 *
 * Configuration is deliberately environment/constant based. Credentials remain owned by the AI
 * Client registry; this provider never reads the connectors option directly.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Util;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

final class SiliconflowConfig
{
    public const VERSION = '1.0.0';
    public const PROVIDER_ID = 'siliconflow';
    public const DEFAULT_BASE_URL = 'https://api.siliconflow.cn/v1';
    public const DEFAULT_MODEL = 'deepseek-ai/DeepSeek-V4-Flash';
    public const DEFAULT_IMAGE_MODEL = 'Kwai-Kolors/Kolors';

    /**
     * Resolve an environment variable or PHP constant.
     *
     * @param string $name Configuration name.
     * @return string Resolved scalar value, or an empty string.
     */
    public static function env(string $name): string
    {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (defined($name)) {
            $constant = constant($name);
            if (is_scalar($constant)) {
                return (string) $constant;
            }
        }

        return '';
    }

    public static function getBaseUrl(): string
    {
        $url = self::env('SILICONFLOW_BASE_URL');
        return $url === '' ? self::DEFAULT_BASE_URL : rtrim($url, '/');
    }

    public static function getDefaultModelId(): string
    {
        $model = self::env('SILICONFLOW_DEFAULT_MODEL');
        return $model === '' ? self::DEFAULT_MODEL : $model;
    }

    public static function getImageModelId(): string
    {
        $model = self::env('SILICONFLOW_IMAGE_MODEL');
        return $model === '' ? self::DEFAULT_IMAGE_MODEL : $model;
    }

    public static function getStructuredOutputMode(): string
    {
        $mode = strtolower(self::env('SILICONFLOW_STRUCTURED_OUTPUT'));
        return in_array($mode, ['json_schema', 'json_object', 'none'], true) ? $mode : 'json_schema';
    }

    public static function declaresImageInput(): bool
    {
        $modalities = strtolower(self::env('SILICONFLOW_MODEL_INPUT_MODALITIES'));
        if ($modalities === '') {
            return false;
        }

        return in_array('image', array_map('trim', explode(',', $modalities)), true);
    }

    public static function isWatermarkEnabled(): bool
    {
        $value = strtolower(self::env('SILICONFLOW_ENABLE_WATERMARK'));
        if ($value === '') {
            return true;
        }

        return !in_array($value, ['0', 'false', 'no', 'off'], true);
    }

    public static function getRequestTimeout(): float
    {
        $value = self::env('SILICONFLOW_REQUEST_TIMEOUT');
        return $value === '' ? 120.0 : max(1.0, (float) $value);
    }

    public static function getConnectTimeout(): float
    {
        $value = self::env('SILICONFLOW_CONNECT_TIMEOUT');
        return $value === '' ? 10.0 : max(1.0, (float) $value);
    }

    /**
     * Resolve a documented image size override.
     *
     * @param string $orientation square, landscape, or portrait.
     * @return string|null Override, or null when no override exists.
     */
    public static function getImageSizeOverride(string $orientation): ?string
    {
        $suffix = strtoupper($orientation);
        foreach (['SILICONFLOW_IMAGE_SIZE_' . $suffix, 'SILICONFLOW_SIZE_' . $suffix] as $name) {
            $value = self::env($name);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public static function hasCredentials(): bool
    {
        if (!class_exists(AiClient::class)) {
            return false;
        }

        $registry = AiClient::defaultRegistry();
        if (!$registry->hasProvider(self::PROVIDER_ID)) {
            return false;
        }

        return $registry->getProviderRequestAuthentication(self::PROVIDER_ID) !== null;
    }

    public static function createRequestOptions(): RequestOptions
    {
        $options = new RequestOptions();
        $options->setTimeout(self::getRequestTimeout());
        $options->setConnectTimeout(self::getConnectTimeout());
        return $options;
    }

    public static function getUserAgent(): string
    {
        return 'bestony-ai-provider-for-siliconflow/' . self::VERSION;
    }
}
