# JobPilot AI — LinkedIn Optimizer & Content Studio

## 1. Profile Optimization Engine
The LinkedIn Profile Optimizer performs automated gap analysis between the candidate's master CV and their current LinkedIn copy.

### Capabilities
- **Headline Generation**: Craft high-CTR, recruiter-targeted headlines incorporating target keywords (e.g., `Senior Laravel / PHP Architect | Microservices | High-Throughput APIs`).
- **About / Summary Refinement**: First-person professional narrative emphasizing achievements, technical stack, and career philosophy.
- **Experience Bullets**: Restructure accomplishments using the Google XYZ framework (*Accomplished [X], as measured by [Y], by doing [Z]*).
- **Zero-Password Safety**: Does not require user LinkedIn credentials or unauthorized scraping bots. Input is provided via candidate paste or authorized public exports.

## 2. LinkedIn Content Studio
- **Topic Generator**: Ideates technical discussion topics across Laravel, PHP internals, database indexing, and software craftsmanship.
- **Post Formatter**: Formats posts with spacing, hooks, and curated hashtags.
- **Visual Assets**: Prompt generator for free-tier image engines (e.g. Cloudflare Workers AI SDXL).
- **Publishing Safeguards**:
  - Requires official LinkedIn OAuth2 permissions.
  - Defaults to human review before any dispatch.
  - Strictly prohibits automated spam messaging or connection request automation.

