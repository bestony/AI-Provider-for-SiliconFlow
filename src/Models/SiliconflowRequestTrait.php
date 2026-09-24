<?php

/**
 * Shared request construction for SiliconFlow models.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Models;

use SiliconFlow\AiProvider\Provider\SiliconflowProvider;
use SiliconFlow\AiProvider\Util\SiliconflowConfig;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

trait SiliconflowRequestTrait
{
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        $headers['User-Agent'] = SiliconflowConfig::getUserAgent();
        return new Request(
            $method,
            SiliconflowProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
