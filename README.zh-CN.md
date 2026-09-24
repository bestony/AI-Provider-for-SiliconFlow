# Bestony AI Provider for SiliconFlow

这是一个独立的 WordPress AI Client Provider 插件，支持 SiliconFlow 的文本生成、视觉理解和文本生图。

## 安装

将目录复制到 `wp-content/plugins/bestony-ai-provider-for-siliconflow/`，激活插件，然后在 **设置 → Connectors** 中选择 SiliconFlow 并保存 API Key。API Key 由 AI Client Registry 管理，插件不会直接读取 Connectors 数据库选项。

## 配置

可使用环境变量或 PHP 常量覆盖默认配置：

- `SILICONFLOW_BASE_URL`：默认 `https://api.siliconflow.cn/v1`。
- `SILICONFLOW_DEFAULT_MODEL`：默认 `deepseek-ai/DeepSeek-V4-Flash`。
- `SILICONFLOW_IMAGE_MODEL`：默认 `Kwai-Kolors/Kolors`。
- `SILICONFLOW_STRUCTURED_OUTPUT`：`json_schema`、`json_object` 或 `none`。
- `SILICONFLOW_MODEL_INPUT_MODALITIES=text,image`：将所有聊天模型声明为支持图片输入。
- `SILICONFLOW_ENABLE_WATERMARK=0`：仅在下游会自行添加显式水印时使用。
- `SILICONFLOW_IMAGE_SIZE_SQUARE`、`SILICONFLOW_IMAGE_SIZE_LANDSCAPE`、`SILICONFLOW_IMAGE_SIZE_PORTRAIT`：覆盖生图尺寸。
- `SILICONFLOW_REQUEST_TIMEOUT` / `SILICONFLOW_CONNECT_TIMEOUT`：请求和连接超时，默认 `120` / `10` 秒。

`enable_thinking`、`thinking_budget`、`reasoning_effort`、`min_p`、`top_k` 等 SiliconFlow 专用聊天参数通过 `customOptions` 传递。

## 生图限制

生图接口使用 `image_size`，返回的图片 URL 仅保留一小时。插件只声明单张远程图片输出，不实现图像编辑、视频、音频、Embedding 和 Rerank。

## 开发检查

```sh
php scripts/selfcheck.php
php scripts/selfcheck.php --sdk=/path/to/php-ai-client/src
```
