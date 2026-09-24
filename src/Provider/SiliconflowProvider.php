<?php

/**
 * SiliconFlow provider class.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Provider;

use SiliconFlow\AiProvider\Metadata\SiliconflowModelMetadataDirectory;
use SiliconFlow\AiProvider\Models\SiliconflowImageGenerationModel;
use SiliconFlow\AiProvider\Models\SiliconflowTextGenerationModel;
use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class SiliconflowProvider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        return SiliconflowConfig::getBaseUrl();
    }

    protected static function createModel(ModelMetadata $modelMetadata, ProviderMetadata $providerMetadata): ModelInterface
    {
        foreach ($modelMetadata->getSupportedCapabilities() as $capability) {
            if ($capability->isTextGeneration()) {
                $model = new SiliconflowTextGenerationModel($modelMetadata, $providerMetadata);
            } elseif ($capability->isImageGeneration()) {
                $model = new SiliconflowImageGenerationModel($modelMetadata, $providerMetadata);
            } else {
                continue;
            }
            $model->setRequestOptions(SiliconflowConfig::createRequestOptions());
            return $model;
        }

        throw new RuntimeException(
            sprintf(
                'The model "%s" has no supported capability for SiliconFlow.',
                $modelMetadata->getId()
            )
        );
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        $args = [
            SiliconflowConfig::PROVIDER_ID,
            'SiliconFlow',
            ProviderTypeEnum::cloud(),
            'https://cloud.siliconflow.cn/account/ak',
            RequestAuthenticationMethod::apiKey(),
        ];
        if (version_compare(AiClient::VERSION, '1.2.0', '>=')) {
            $args[] = function_exists('__')
                ? __('Text, vision and image generation with SiliconFlow models.', 'bestony-ai-provider-for-siliconflow')
                : 'Text, vision and image generation with SiliconFlow models.';
        }
        if (version_compare(AiClient::VERSION, '1.3.0', '>=')) {
            $args[] = dirname(__DIR__, 2) . '/assets/images/siliconflow.svg';
        }
        return new ProviderMetadata(...$args);
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(static::modelMetadataDirectory());
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new SiliconflowModelMetadataDirectory();
    }
}
