=== Bestony AI Provider for SiliconFlow ===
Contributors:      bestony
Tags:              ai, connector, siliconflow, artificial-intelligence, vision, image-generation
Requires at least: 7.0
Tested up to:      7.1
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

SiliconFlow provider for the WordPress AI Client: text, vision and text-to-image generation.

== Description ==

Adds SiliconFlow as a provider for the WordPress AI Client. The model list is fetched from `/v1/models`; because that response contains model IDs but not capability metadata, this plugin uses a strict maintained catalog. Unknown IDs stay visible without capabilities.

* Text generation and chat history through `/chat/completions`.
* Vision input for maintained VLM families.
* Structured output with SiliconFlow's named JSON Schema wrapper.
* Text-to-image generation through `/images/generations`, returning one remote image URL.
* Bearer authentication supplied by the AI Client registry.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/bestony-ai-provider-for-siliconflow/`.
2. Activate it from the Plugins screen.
3. Open Settings → Connectors, select SiliconFlow, and save an API key.

== Configuration ==

Set `SILICONFLOW_BASE_URL`, `SILICONFLOW_DEFAULT_MODEL`, `SILICONFLOW_IMAGE_MODEL`, `SILICONFLOW_STRUCTURED_OUTPUT`, `SILICONFLOW_MODEL_INPUT_MODALITIES`, `SILICONFLOW_ENABLE_WATERMARK`, the three `SILICONFLOW_IMAGE_SIZE_*` variables, `SILICONFLOW_REQUEST_TIMEOUT`, or `SILICONFLOW_CONNECT_TIMEOUT` as needed. See `README.md` for the defaults and custom options.

== External services ==

This plugin sends requests to SiliconFlow. `GET /v1/models` sends only the registry-provided API key. `POST /v1/chat/completions` sends the prompts, conversation history, tools, structured-output schema, and attached media supplied by the calling feature. `POST /v1/images/generations` sends the image prompt and configured generation options. Generated image URLs expire after one hour.

SiliconFlow documentation: https://docs.siliconflow.cn/

== Changelog ==

= 1.0.0 =
* Initial release with text, vision and text-to-image generation.

== License ==

GPL-2.0-or-later. See LICENSE.
