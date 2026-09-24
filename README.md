# Bestony AI Provider for SiliconFlow

A sibling WordPress plugin that registers [SiliconFlow](https://www.siliconflow.cn/) with the WordPress AI Client. It supports text generation, documented vision models, and synchronous text-to-image generation.

## Installation

1. Copy this directory to `wp-content/plugins/bestony-ai-provider-for-siliconflow/`.
2. Activate the plugin.
3. Open **Settings → Connectors**, select SiliconFlow, and save an API key.

The SDK is supplied by WordPress 7.0 or the AI plugin. The provider is silent when the SDK is missing.

## Configuration

The API key is owned by the AI Client registry. This plugin does not read the connectors option directly. Optional environment variables or PHP constants are:

| Setting | Default | Purpose |
| --- | --- | --- |
| `SILICONFLOW_BASE_URL` | `https://api.siliconflow.cn/v1` | OpenAI-compatible API base URL |
| `SILICONFLOW_DEFAULT_MODEL` | `deepseek-ai/DeepSeek-V4-Flash` | Preferred text and vision model |
| `SILICONFLOW_IMAGE_MODEL` | `Kwai-Kolors/Kolors` | Preferred image model |
| `SILICONFLOW_STRUCTURED_OUTPUT` | `json_schema` | `json_schema`, `json_object`, or `none` |
| `SILICONFLOW_MODEL_INPUT_MODALITIES` | catalog | Set to `text,image` to force vision for chat models |
| `SILICONFLOW_ENABLE_WATERMARK` | enabled | Set to `0` only when downstream processing adds the required watermark |
| `SILICONFLOW_IMAGE_SIZE_SQUARE` | model default | Override image size for square requests |
| `SILICONFLOW_IMAGE_SIZE_LANDSCAPE` | model default | Override image size for landscape requests |
| `SILICONFLOW_IMAGE_SIZE_PORTRAIT` | model default | Override image size for portrait requests |
| `SILICONFLOW_REQUEST_TIMEOUT` | `120` | Request timeout in seconds |
| `SILICONFLOW_CONNECT_TIMEOUT` | `10` | Connection timeout in seconds |

SiliconFlow-specific chat parameters such as `enable_thinking`, `thinking_budget`, `reasoning_effort`, `min_p`, and `top_k` can be passed through `customOptions`. The provider only declares standard options documented for all compatible chat models.

## Image generation

The image endpoint receives `prompt` and `image_size`, and returns a remote image URL. The URL expires after one hour, so the calling feature must download it promptly. Only one remote image candidate is declared. Supported image custom options include `negative_prompt`, `seed`, `num_inference_steps`, `guidance_scale`, and `cfg`.

The provider sends `X-Enable-Watermark: 1` by default. Set `SILICONFLOW_ENABLE_WATERMARK=0` only when the downstream workflow adds the explicit watermark required by SiliconFlow.

Image editing, video, audio, embedding, reranking, and asynchronous jobs are not implemented. Those model IDs remain visible without capabilities.

## Development

```sh
php scripts/selfcheck.php
php scripts/selfcheck.php --sdk=/path/to/php-ai-client/src
for file in $(find . -name '*.php' -not -path './.git/*'); do php -l "$file"; done
```

The self-check never makes a network request and does not require an API key.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
