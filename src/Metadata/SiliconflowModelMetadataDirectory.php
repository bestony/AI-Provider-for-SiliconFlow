<?php

/**
 * SiliconFlow model metadata directory.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Metadata;

use SiliconFlow\AiProvider\Provider\SiliconflowProvider;
use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use SiliconFlow\AiProvider\Util\SiliconflowModelCatalog;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

class SiliconflowModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        $headers['User-Agent'] = SiliconflowConfig::getUserAgent();
        return new Request(
            $method,
            SiliconflowProvider::url($path),
            $headers,
            $data,
            SiliconflowConfig::createRequestOptions()
        );
    }

    protected function parseResponseToModelMetadataList(Response $response): array
    {
        $data = $response->getData();
        if (!is_array($data) || !isset($data['data']) || !is_array($data['data']) || $data['data'] === []) {
            throw ResponseException::fromMissingData('SiliconFlow', 'data');
        }

        $models = [];
        foreach ($data['data'] as $modelData) {
            if (!is_array($modelData) || !isset($modelData['id']) || !is_string($modelData['id']) || $modelData['id'] === '') {
                continue;
            }
            $id = $modelData['id'];
            if (SiliconflowModelCatalog::isImageModel($id)) {
                $capabilities = [CapabilityEnum::imageGeneration()];
                $options = $this->createImageOptions();
            } elseif (SiliconflowModelCatalog::isTextModel($id)) {
                $capabilities = [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()];
                $options = $this->createTextOptions($id);
            } else {
                $capabilities = [];
                $options = [];
            }
            $models[] = new ModelMetadata($id, $id, $capabilities, $options);
        }

        $preferred = SiliconflowConfig::getDefaultModelId();
        usort($models, static function (ModelMetadata $a, ModelMetadata $b) use ($preferred): int {
            if ($preferred !== '') {
                $aPreferred = $a->getId() === $preferred ? 0 : 1;
                $bPreferred = $b->getId() === $preferred ? 0 : 1;
                if ($aPreferred !== $bPreferred) {
                    return $aPreferred <=> $bPreferred;
                }
            }
            return SiliconflowModelCatalog::compareModelIds($a->getId(), $b->getId());
        });

        return $models;
    }

    private function createTextOptions(string $modelId): array
    {
        $inputModalities = [[ModalityEnum::text()]];
        if (SiliconflowModelCatalog::supportsImageInput($modelId) || SiliconflowConfig::declaresImageInput()) {
            $inputModalities[] = [ModalityEnum::text(), ModalityEnum::image()];
        }
        return [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::functionDeclarations()),
            new SupportedOption(OptionEnum::customOptions()),
            new SupportedOption(OptionEnum::inputModalities(), $inputModalities),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::frequencyPenalty()),
        ];
    }

    private function createImageOptions(): array
    {
        return [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::image()]]),
            new SupportedOption(OptionEnum::candidateCount(), [1]),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::remote()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(OptionEnum::customOptions()),
        ];
    }
}
