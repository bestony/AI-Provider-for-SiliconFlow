<?php

// phpcs:ignoreFile -- dev-only CLI harness.

declare(strict_types=1);

$root = dirname(__DIR__);
if (!defined('ABSPATH')) {
    define('ABSPATH', $root . '/');
}
require $root . '/src/autoload.php';

use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use SiliconFlow\AiProvider\Util\SiliconflowModelCatalog;

$checks = 0;
$failures = 0;
function check(bool $condition, string $description): void
{
    global $checks, $failures;
    $checks++;
    if ($condition) {
        fwrite(STDOUT, "ok    {$description}\n");
    } else {
        $failures++;
        fwrite(STDERR, "FAIL  {$description}\n");
    }
}

check(SiliconflowConfig::getBaseUrl() === 'https://api.siliconflow.cn/v1', 'default base URL');
check(SiliconflowConfig::getDefaultModelId() === 'deepseek-ai/DeepSeek-V4-Flash', 'default chat model');
check(SiliconflowConfig::getImageModelId() === 'Kwai-Kolors/Kolors', 'default image model');
check(SiliconflowConfig::getStructuredOutputMode() === 'json_schema', 'structured output defaults to json_schema');
check(SiliconflowConfig::getRequestTimeout() === 120.0, 'request timeout defaults to 120 seconds');
check(SiliconflowConfig::getConnectTimeout() === 10.0, 'connect timeout defaults to 10 seconds');
check(SiliconflowConfig::isWatermarkEnabled(), 'watermark is enabled by default');
check(!SiliconflowConfig::declaresImageInput(), 'vision is not force-declared by default');

putenv('SILICONFLOW_BASE_URL=https://example.test/v1/');
putenv('SILICONFLOW_STRUCTURED_OUTPUT=json_object');
putenv('SILICONFLOW_ENABLE_WATERMARK=0');
putenv('SILICONFLOW_MODEL_INPUT_MODALITIES=text,image');
putenv('SILICONFLOW_IMAGE_SIZE_LANDSCAPE=1600x900');
check(SiliconflowConfig::getBaseUrl() === 'https://example.test/v1', 'base URL override trims a slash');
check(SiliconflowConfig::getStructuredOutputMode() === 'json_object', 'structured output override');
check(!SiliconflowConfig::isWatermarkEnabled(), 'watermark can be disabled explicitly');
check(SiliconflowConfig::declaresImageInput(), 'input modality override declares image input');
check(SiliconflowConfig::getImageSizeOverride('landscape') === '1600x900', 'image size override');
putenv('SILICONFLOW_BASE_URL');
putenv('SILICONFLOW_STRUCTURED_OUTPUT');
putenv('SILICONFLOW_ENABLE_WATERMARK');
putenv('SILICONFLOW_MODEL_INPUT_MODALITIES');
putenv('SILICONFLOW_IMAGE_SIZE_LANDSCAPE');

foreach (['Qwen/Qwen2.5-VL-72B-Instruct', 'Qwen/Qwen3-VL-30B-A3B-Instruct', 'THUDM/GLM-4.1V-9B-Thinking', 'deepseek-ai/deepseek-vl2'] as $id) {
    check(SiliconflowModelCatalog::supportsImageInput($id), "{$id} is a vision model");
}
foreach (['deepseek-ai/DeepSeek-V4-Flash', 'Qwen/Qwen3-32B', 'Qwen/Qwen2.5-72B-Instruct'] as $id) {
    check(SiliconflowModelCatalog::isTextModel($id), "{$id} is a chat model");
}
foreach (['Kwai-Kolors/Kolors', 'Qwen/Qwen-Image', 'black-forest-labs/FLUX.1-schnell'] as $id) {
    check(SiliconflowModelCatalog::isImageModel($id), "{$id} is an image model");
}
foreach (['BAAI/bge-m3', 'FunAudioLLM/SenseVoiceSmall', 'Wan-AI/Wan2.1-T2V-14B', 'future/unknown-model'] as $id) {
    check(SiliconflowModelCatalog::isUnsupported($id), "{$id} has no declared capability");
}
check(SiliconflowModelCatalog::sizeForOrientation('Kwai-Kolors/Kolors', 'square') === '1024x1024', 'Kolors square size');
check(SiliconflowModelCatalog::sizeForOrientation('Qwen/Qwen-Image', 'landscape') === '1664x928', 'Qwen image landscape size');
check(SiliconflowModelCatalog::compareModelIds('deepseek-ai/DeepSeek-V4-Flash', 'future/unknown-model') < 0, 'supported models sort before unknown models');

$sdkPath = null;
foreach ($argv as $argument) {
    if (strpos($argument, '--sdk=') === 0) {
        $sdkPath = substr($argument, 6);
    }
}
if ($sdkPath === null || !is_file($sdkPath . '/polyfills.php')) {
    check(true, 'SDK-dependent checks skipped (pass --sdk=<path to php-ai-client/src> to run them)');
    fwrite(STDOUT, "\n{$checks} checks, {$failures} failure(s)\n");
    exit($failures === 0 ? 0 : 1);
}

require $sdkPath . '/polyfills.php';
spl_autoload_register(static function (string $class) use ($sdkPath): void {
    $prefix = 'WordPress\\AiClient\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $file = $sdkPath . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$GLOBALS['siliconflow_actions'] = [];
$GLOBALS['siliconflow_filters'] = [];
if (!function_exists('add_action')) {
    function add_action(string $hook, $callback, int $priority = 10): void
    {
        $GLOBALS['siliconflow_actions'][$hook][$priority][] = $callback;
    }
}
if (!function_exists('add_filter')) {
    function add_filter(string $hook, $callback, int $priority = 10): void
    {
        $GLOBALS['siliconflow_filters'][$hook][$priority][] = $callback;
    }
}
if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = ''): string
    {
        return $text;
    }
}
if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}
require $root . '/bestony-ai-provider-for-siliconflow.php';

use SiliconFlow\AiProvider\Metadata\SiliconflowModelMetadataDirectory;
use SiliconFlow\AiProvider\Provider\SiliconflowProvider;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

$registry = AiClient::defaultRegistry();
// A source-only SDK checkout may not have PSR discovery dependencies. Supplying a transporter keeps
// this self-check deterministic and prevents registration from attempting network discovery.
$registry->setHttpTransporter(new class implements HttpTransporterInterface {
    public function send(Request $request, ?RequestOptions $options = null): Response
    {
        return new Response(200, [], json_encode(['data' => []]));
    }
});
foreach (($GLOBALS['siliconflow_actions']['init'][5] ?? []) as $callback) {
    $callback();
}
check($registry->hasProvider(SiliconflowProvider::class), 'provider registers on init priority 5');
check(!SiliconflowConfig::hasCredentials(), 'registry has no credentials before injection');
$registry->setProviderRequestAuthentication(SiliconflowConfig::PROVIDER_ID, new ApiKeyRequestAuthentication('test-key'));
check(SiliconflowConfig::hasCredentials(), 'registry credential injection is detected');

$parser = new class extends SiliconflowModelMetadataDirectory {
    public function parse(Response $response): array
    {
        return $this->parseResponseToModelMetadataList($response);
    }
};
$models = $parser->parse(new Response(200, [], json_encode(['object' => 'list', 'data' => [
    ['id' => 'deepseek-ai/DeepSeek-V4-Flash'],
    ['id' => 'Qwen/Qwen2.5-VL-72B-Instruct'],
    ['id' => 'Kwai-Kolors/Kolors'],
    ['id' => 'BAAI/bge-m3'],
    ['id' => 'future/unknown-model'],
]])));
$byId = [];
foreach ($models as $model) {
    $byId[$model->getId()] = $model;
}
check(count($models) === 5, 'all model IDs are retained');
check($models[0]->getId() === 'deepseek-ai/DeepSeek-V4-Flash', 'configured model is sorted first');
check($byId['future/unknown-model']->getSupportedCapabilities() === [], 'unknown model has no capabilities');
check(count($byId['Qwen/Qwen2.5-VL-72B-Instruct']->getSupportedOptions()) > 0, 'vision model has declared options');
$names = static fn($model): array => array_map(static fn($option): string => $option->getName()->value, $model->getSupportedOptions());
check(in_array('temperature', $names($byId['deepseek-ai/DeepSeek-V4-Flash']), true), 'temperature is declared');
check(in_array('frequencyPenalty', $names($byId['deepseek-ai/DeepSeek-V4-Flash']), true), 'frequency penalty is declared');
check(!in_array('presencePenalty', $names($byId['deepseek-ai/DeepSeek-V4-Flash']), true), 'presence penalty is not over-declared');
check($names($byId['Kwai-Kolors/Kolors']) === ['inputModalities', 'outputModalities', 'candidateCount', 'outputFileType', 'outputMediaOrientation', 'customOptions'], 'image options are limited to supported controls');

class SiliconflowSelfcheckTransporter implements HttpTransporterInterface
{
    public ?Request $request = null;
    public array $responseData = [];
    public function send(Request $request, ?RequestOptions $options = null): Response
    {
        $this->request = $request;
        return new Response(200, [], json_encode($this->responseData));
    }
}

$transporter = new SiliconflowSelfcheckTransporter();
$providerMetadata = SiliconflowProvider::metadata();
$message = new Message(MessageRoleEnum::user(), [new MessagePart('hello')]);
$chat = new \SiliconFlow\AiProvider\Models\SiliconflowTextGenerationModel($byId['deepseek-ai/DeepSeek-V4-Flash'], $providerMetadata);
$chat->setHttpTransporter($transporter);
$chat->setRequestAuthentication(new ApiKeyRequestAuthentication('test-key'));
$chat->setRequestOptions(SiliconflowConfig::createRequestOptions());
$chat->setConfig(ModelConfig::fromArray(['outputMimeType' => 'application/json', 'outputSchema' => ['type' => 'object'], 'customOptions' => ['enable_thinking' => true]]));
$transporter->responseData = ['id' => 'chat-1', 'choices' => [['message' => ['role' => 'assistant', 'content' => '{"ok":true}', 'reasoning_content' => 'thinking'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3]];
$chat->generateTextResult([$message]);
check($transporter->request->getUri() === 'https://api.siliconflow.cn/v1/chat/completions', 'chat request URL');
check($transporter->request->getHeaderAsString('Authorization') === 'Bearer test-key', 'chat request uses Bearer authentication');
$chatBody = $transporter->request->getData();
check(($chatBody['response_format']['json_schema']['name'] ?? null) === 'siliconflow_response', 'structured output uses the SiliconFlow wrapper');
check(($chatBody['enable_thinking'] ?? null) === true, 'custom chat options pass through');
check($transporter->request->getHeaderAsString('User-Agent') === SiliconflowConfig::getUserAgent(), 'chat request User-Agent');

$image = new \SiliconFlow\AiProvider\Models\SiliconflowImageGenerationModel($byId['Kwai-Kolors/Kolors'], $providerMetadata);
$image->setHttpTransporter($transporter);
$image->setRequestAuthentication(new ApiKeyRequestAuthentication('test-key'));
$image->setRequestOptions(SiliconflowConfig::createRequestOptions());
$image->setConfig(ModelConfig::fromArray(['outputMediaOrientation' => 'landscape', 'customOptions' => ['seed' => 7]]));
$transporter->responseData = ['images' => [['url' => 'https://cdn.example.test/image.png']], 'seed' => 7, 'timings' => ['inference' => 0.1]];
$result = $image->generateImageResult([$message]);
check($transporter->request->getUri() === 'https://api.siliconflow.cn/v1/images/generations', 'image request URL');
check($transporter->request->getHeaderAsString('X-Enable-Watermark') === '1', 'image watermark header defaults to enabled');
$imageBody = $transporter->request->getData();
check(($imageBody['image_size'] ?? null) === '1280x768', 'image request uses image_size');
check(!array_key_exists('n', $imageBody) && !array_key_exists('response_format', $imageBody), 'image request omits unsupported OpenAI fields');
$part = $result->getCandidates()[0]->getMessage()->getParts()[0];
check($part->getFile() !== null && $part->getFile()->isRemote(), 'image URL becomes a remote File');
check(($result->getAdditionalData()['seed'] ?? null) === 7 && isset($result->getAdditionalData()['timings']), 'seed and timings are preserved as metadata');

$filters = static function (string $hook, array $value): array {
    $priorities = $GLOBALS['siliconflow_filters'][$hook] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as $callback) {
            $value = $callback($value);
        }
    }
    return $value;
};
check($filters('wpai_preferred_text_models', [['openai', 'gpt-5']])[0] === ['siliconflow', SiliconflowConfig::DEFAULT_MODEL], 'text preference filter prioritises SiliconFlow');
check($filters('wpai_preferred_image_models', [['openai', 'gpt-image-1']])[0] === ['siliconflow', SiliconflowConfig::DEFAULT_IMAGE_MODEL], 'image preference filter prioritises SiliconFlow');

fwrite(STDOUT, "\n{$checks} checks, {$failures} failure(s)\n");
exit($failures === 0 ? 0 : 1);
