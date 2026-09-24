<?php

/**
 * SiliconFlow image generation model.
 *
 * SiliconFlow uses `image_size` in the request and returns `images[].url`; this cannot use the
 * OpenAI-compatible image base class without sending unsupported fields.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Models;

use SiliconFlow\AiProvider\Provider\SiliconflowProvider;
use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use SiliconFlow\AiProvider\Util\SiliconflowModelCatalog;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

class SiliconflowImageGenerationModel extends AbstractApiBasedModel implements ImageGenerationModelInterface
{
    use SiliconflowRequestTrait;

    public function generateImageResult(array $prompt): GenerativeAiResult
    {
        $params = $this->prepareGenerateImageParams($prompt);
        $customOptions = $this->getConfig()->getCustomOptions();
        $watermark = SiliconflowConfig::isWatermarkEnabled();
        foreach (['enable_watermark', 'watermark', 'X-Enable-Watermark'] as $key) {
            if (array_key_exists($key, $customOptions)) {
                $watermark = $this->toBool($customOptions[$key]);
                unset($customOptions[$key]);
            }
        }

        $request = new Request(
            HttpMethodEnum::POST(),
            SiliconflowProvider::url('images/generations'),
            [
                'Content-Type' => 'application/json',
                'User-Agent' => SiliconflowConfig::getUserAgent(),
                'X-Enable-Watermark' => $watermark ? '1' : '0',
            ],
            $params,
            $this->getRequestOptions()
        );
        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $this->getHttpTransporter()->send($request);
        ResponseUtil::throwIfNotSuccessful($response);
        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * @param list<Message> $prompt Prompt messages.
     * @return array<string, mixed> SiliconFlow image request body.
     */
    protected function prepareGenerateImageParams(array $prompt): array
    {
        $config = $this->getConfig();
        $outputFileType = $config->getOutputFileType();
        if ($outputFileType !== null && !$outputFileType->isRemote()) {
            throw new InvalidArgumentException('SiliconFlow image generation only supports remote output files.');
        }

        $candidateCount = $config->getCandidateCount();
        if ($candidateCount !== null && $candidateCount !== 1) {
            throw new InvalidArgumentException('SiliconFlow image generation supports exactly one candidate.');
        }

        $body = [
            'model' => $this->metadata()->getId(),
            'prompt' => $this->preparePromptParam($prompt),
            'image_size' => $this->prepareImageSizeParam(
                $config->getOutputMediaOrientation(),
                $config->getOutputMediaAspectRatio()
            ),
        ];

        foreach ($config->getCustomOptions() as $key => $value) {
            if (in_array($key, ['enable_watermark', 'watermark', 'X-Enable-Watermark'], true)) {
                continue;
            }
            if (array_key_exists($key, $body)) {
                throw new InvalidArgumentException(sprintf('The custom option "%s" conflicts with an existing parameter.', $key));
            }
            $body[$key] = $value;
        }

        return $body;
    }

    /**
     * @param list<Message> $messages Prompt messages.
     * @return string Prompt text.
     */
    protected function preparePromptParam(array $messages): string
    {
        if (count($messages) !== 1 || !$messages[0] instanceof Message || !$messages[0]->getRole()->isUser()) {
            throw new InvalidArgumentException('SiliconFlow image generation requires one user message.');
        }
        foreach ($messages[0]->getParts() as $part) {
            $text = $part->getText();
            if ($text !== null && $text !== '') {
                return $text;
            }
        }
        throw new InvalidArgumentException('SiliconFlow image generation requires a text prompt.');
    }

    protected function prepareImageSizeParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        $value = null;
        if ($aspectRatio !== null) {
            $parts = explode(':', $aspectRatio);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $left = (float) $parts[0];
                $right = (float) $parts[1];
                $value = $left > $right ? 'landscape' : ($left < $right ? 'portrait' : 'square');
            }
        }
        if ($value === null && $orientation !== null) {
            $value = $orientation->isLandscape() ? 'landscape' : ($orientation->isPortrait() ? 'portrait' : 'square');
        }
        return SiliconflowModelCatalog::sizeForOrientation($this->metadata()->getId(), $value);
    }

    /**
     * @param Response $response SiliconFlow response.
     * @return GenerativeAiResult Parsed result.
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        $data = $response->getData();
        if (!is_array($data) || !isset($data['images']) || !is_array($data['images']) || $data['images'] === []) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'images');
        }

        $candidates = [];
        foreach ($data['images'] as $index => $image) {
            if (
                !is_array($image)
                || !isset($image['url'])
                || !is_string($image['url'])
                || $image['url'] === ''
                || filter_var($image['url'], FILTER_VALIDATE_URL) === false
                || !preg_match('/^https?:\/\//i', $image['url'])
            ) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "images[{$index}].url",
                    'The value must be an absolute HTTP(S) URL.'
                );
            }
            $file = new File($image['url'], 'image/png');
            $message = new Message(MessageRoleEnum::model(), [new MessagePart($file)]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop());
        }

        $id = isset($data['seed']) && is_int($data['seed']) ? 'seed-' . $data['seed'] : '';
        $additionalData = $data;
        unset($additionalData['images']);

        return new GenerativeAiResult(
            $id,
            $candidates,
            new TokenUsage(0, 0, 0),
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }

    /**
     * @param mixed $value Custom watermark option.
     * @return bool Parsed boolean.
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }
        return !in_array(strtolower((string) $value), ['0', 'false', 'no', 'off'], true);
    }
}
