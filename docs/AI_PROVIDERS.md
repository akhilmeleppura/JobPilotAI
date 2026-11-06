# JobPilot AI — AI Providers & Free-First Gateway

## 1. Free-Only Execution Policy (`FREE_RESOURCES_ONLY=true`)
When `FREE_RESOURCES_ONLY` is enabled (default `true`):
- All API requests pass through the `AIGatewayService`.
- Requests inspect provider metadata, model rate limits, and pricing tags.
- Paid endpoints or credit-consuming models are strictly blocked.
- Unknown pricing triggers an immediate abort.

## 2. Integrated Free Providers

### A. Google AI Studio (Gemini API)
- **Key Separation**: Gemini Pro web subscription and Google AI Studio API keys are separate. JobPilot AI requires a dedicated Google AI Studio API key.
- **Model Tiers**:
  - `gemini-2.5-flash` / `gemini-1.5-flash` (Generous free RPM / RPD tier).
  - Dynamic discovery via `GET /v1beta/models`.
  - Rate-limiting: tracks 15 RPM / 1,500 RPD ceilings.

### B. Groq API
- High-speed inference for Llama 3 / Mixtral open-weights models.
- Uses standard OpenAI-compatible REST schema.
- Free developer tier monitored for token limits.

### C. OpenRouter Free Tier
- Dynamically queries models tagged `:free` (e.g. `meta-llama/llama-3-8b-instruct:free`).
- Zero credit consumption guaranteed.

### D. Local Ollama
- Privacy-first inference on `http://127.0.0.1:11434`.
- Recommended for sensitive CV details, personal contact information, and local drafting.
- Zero network reliance, infinite free quota.

### E. Cloudflare Workers AI
- Free daily allocation (10,000 neurons/day) suitable for text embeddings and lightweight generation.

### F. Alpme Policy
- Verified documentation check required before connection.
- Must verify Base URL, authentication, data retention, and terms.
- Synthetic testing with dummy candidate profiles before enabling personal data transmission.

