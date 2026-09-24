<?php

/**
 * Explicit SiliconFlow model capability catalog.
 *
 * SiliconFlow's model list contains IDs but not the capability metadata required by the AI Client.
 * New or unsupported IDs stay visible in the picker and intentionally receive no capability.
 *
 * @package SiliconFlow\AiProvider
 */

declare(strict_types=1);

namespace SiliconFlow\AiProvider\Util;

final class SiliconflowModelCatalog
{
    private const VISION_PATTERNS = [
        '#^(?:Pro/)?Qwen/Qwen(?:2(?:\.5)?-VL|3-VL|3\.5-)#i',
        '#^(?:Pro/)?Qwen/Qwen3-Omni-#i',
        '#^(?:Pro/)?deepseek-ai/(?:deepseek-vl2|DeepSeek-OCR)#i',
        '#^(?:Pro/)?THUDM/GLM-4\.1V#i',
        '#^(?:Pro/)?stepfun-ai/step3#i',
        '#^PaddlePaddle/PaddleOCR-VL#i',
        '#^OpenGVLab/InternVL#i',
        '#^(?:Pro/)?moonshotai/Kimi-K2\.(?:5|6|7)#i',
    ];

    private const IMAGE_PATTERNS = [
        '#^Kwai-Kolors/Kolors(?:$|-|_)#i',
        '#^(?:Pro/)?Qwen/Qwen-Image(?:$|-|_)#i',
        '#^black-forest-labs/FLUX(?:\.|-|_)#i',
        '#^stabilityai/(?:stable-diffusion|sd3|sdxl)#i',
        '#^Tongyi-MAI/Z-Image(?:$|-|_)#i',
    ];

    private const NON_TEXT_PATTERNS = [
        '#^(?:Pro/)?Qwen/Qwen-Image-Edit#i',
        '#(?:embedding|bge-|gte-|e5-|rerank|bce-)#i',
        '#(?:speech|audio|tts|asr|sensevoice|cosyvoice)#i',
        '#(?:video|wan2|hunyuanvideo|text-to-video)#i',
    ];

    private const CHAT_PATTERNS = [
        '#^(?:Pro/)?deepseek-ai/#i',
        '#^(?:Pro/)?Qwen/#i',
        '#^(?:Pro/)?THUDM/#i',
        '#^(?:Pro/)?zai-org/#i',
        '#^(?:Pro/)?moonshotai/#i',
        '#^(?:Pro/)?MiniMaxAI/#i',
        '#^(?:Pro/)?stepfun-ai/#i',
        '#^(?:Pro/)?meta-llama/#i',
        '#^(?:Pro/)?internlm/#i',
        '#^(?:Pro/)?TeleAI/#i',
        '#^PaddlePaddle/#i',
        '#^OpenGVLab/#i',
        '#^(?:Pro/)?openai/#i',
    ];

    private const IMAGE_SIZES = [
        'KOLORS' => [
            'square' => '1024x1024',
            'landscape' => '1280x768',
            'portrait' => '768x1280',
        ],
        'QWEN' => [
            'square' => '1328x1328',
            'landscape' => '1664x928',
            'portrait' => '928x1664',
        ],
        'DEFAULT' => [
            'square' => '1024x1024',
            'landscape' => '1024x576',
            'portrait' => '576x1024',
        ],
    ];

    public static function supportsImageInput(string $modelId): bool
    {
        foreach (self::VISION_PATTERNS as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return true;
            }
        }
        return false;
    }

    public static function isImageModel(string $modelId): bool
    {
        // Image-edit models require reference-image fields and are outside this text-to-image release.
        if (stripos($modelId, 'Qwen/Qwen-Image-Edit') !== false) {
            return false;
        }
        foreach (self::IMAGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return true;
            }
        }
        return false;
    }

    public static function isEmbeddingModel(string $modelId): bool
    {
        return preg_match('/(?:embedding|bge-|gte-|e5-|bce-)/i', $modelId) === 1;
    }

    public static function isAudioModel(string $modelId): bool
    {
        return preg_match('/(?:speech|audio|tts|asr|sensevoice|cosyvoice)/i', $modelId) === 1;
    }

    public static function isVideoModel(string $modelId): bool
    {
        return preg_match('/(?:video|wan2|hunyuanvideo|text-to-video)/i', $modelId) === 1;
    }

    public static function isTextModel(string $modelId): bool
    {
        if (self::isImageModel($modelId) || self::isEmbeddingModel($modelId) || self::isAudioModel($modelId) || self::isVideoModel($modelId)) {
            return false;
        }
        foreach (self::NON_TEXT_PATTERNS as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return false;
            }
        }
        foreach (self::CHAT_PATTERNS as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return true;
            }
        }
        return false;
    }

    public static function isUnsupported(string $modelId): bool
    {
        return !self::isTextModel($modelId) && !self::isImageModel($modelId);
    }

    public static function sizeForOrientation(string $modelId, ?string $orientation): string
    {
        $orientation = $orientation === null ? 'square' : strtolower($orientation);
        $override = SiliconflowConfig::getImageSizeOverride($orientation);
        if ($override !== null) {
            return $override;
        }

        $family = 'DEFAULT';
        if (stripos($modelId, 'Kolors') !== false) {
            $family = 'KOLORS';
        } elseif (stripos($modelId, 'Qwen-Image') !== false) {
            $family = 'QWEN';
        }
        return self::IMAGE_SIZES[$family][$orientation] ?? self::IMAGE_SIZES[$family]['square'];
    }

    public static function compareModelIds(string $modelIdA, string $modelIdB): int
    {
        $flags = static function (string $id): array {
            return [
                self::isPreview($id) ? 1 : 0,
                self::isUnsupported($id) ? 1 : 0,
                self::isImageModel($id) ? 1 : 0,
            ];
        };
        $a = $flags($modelIdA);
        $b = $flags($modelIdB);
        if ($a !== $b) {
            return $a <=> $b;
        }
        return strnatcasecmp($modelIdA, $modelIdB);
    }

    public static function isPreview(string $modelId): bool
    {
        return stripos($modelId, 'preview') !== false
            || stripos($modelId, 'beta') !== false
            || stripos($modelId, '-exp') !== false;
    }
}
