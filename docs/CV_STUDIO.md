# JobPilot AI — CV Studio & LaTeX Editor Specification

## 1. CV Architecture & Version Control
The CV Studio enables candidates to create, optimize, and version ATS-compliant resumes tailored to specific target roles.

### Core Capabilities
- **Multi-Format Ingestion**: Parse existing resumes from PDF, DOCX, and plain text.
- **Role-Based Targeting**: Link resumes to distinct role profiles (e.g. Laravel Backend, WordPress Specialist, Full Stack).
- **Non-Destructive Optimization**: Original candidate text is permanently preserved; AI-suggested improvements (ATS keywords, action verbs) are stored in separate version branches.
- **Truth Integrity Mandate**: The AI engine is strictly instructed to never hallucinate education, companies, dates, or non-existent metrics.

## 2. Standard Built-in Templates
1. **ATS Classic**: Single-column, minimalist, maximum parseability for Taleo/Workday.
2. **Modern Professional**: Clean typography with subtle structural accents.
3. **Backend Developer**: Highlights system architecture, APIs, databases, and throughput.
4. **Full Stack Developer**: Balanced presentation of frontend (Bootstrap/Tailwind) and backend engineering.
5. **WordPress Specialist**: Showcases custom theme/plugin development, WooCommerce, and PHP optimizations.
6. **Project Focused**: Highlights open-source contributions, repositories, and measurable impact.

## 3. Overleaf-Compatible LaTeX Compiler
- In-browser code editing with real-time compilation.
- **Security Isolation**:
  - `pdflatex` / `xelatex` commands invoked with `-no-shell-escape`.
  - Process run inside dedicated temporary directories with CPU timeouts (10s) and memory caps.
  - No access to parent directory or network sockets during compilation.
- Export options: Clean PDF, source `.tex`, and full `.zip` bundle.

