<?php

/**
 * SiliconFlow OpenAI-compatible text generation model.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Models;

use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

class SiliconflowTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    use SiliconflowRequestTrait;

    protected function prepareGenerateTextParams(array $prompt): array
    {
        $params = parent::prepareGenerateTextParams($prompt);
        if (isset($params['response_format']) && $params['response_format'] === []) {
            unset($params['response_format']);
        }
        return $params;
    }

    protected function prepareResponseFormatParam(?array $outputSchema): array
    {
        $mode = SiliconflowConfig::getStructuredOutputMode();
        if ($mode === 'none') {
            return [];
        }
        if ($mode === 'json_object' || !is_array($outputSchema)) {
            return ['type' => 'json_object'];
        }
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'siliconflow_response',
                'schema' => $outputSchema,
            ],
        ];
    }
}
