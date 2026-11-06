<?php

/**
 * JobPilot AI — Complete MS Word Guide Generator (.docx)
 * Generates an authoritative, beautifully structured Microsoft Word document
 * covering features, free cloud hosting, free mail setup, and live testing.
 */

$outputPath = dirname(__DIR__) . '/JobPilotAI_Complete_Guide_And_Hosting_Setup.docx';

class DocxBuilder
{
    private array $paragraphs = [];

    public function addTitle(string $text, string $subtitle = ''): self
    {
        $xml = '<w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:before="360" w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:sz w:val="56"/>
                    <w:color w:val="1E40AF"/>
                </w:rPr>
                <w:t>' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';

        if ($subtitle) {
            $xml .= '<w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                    <w:spacing w:before="60" w:after="360"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                        <w:sz w:val="26"/>
                        <w:color w:val="475569"/>
                    </w:rPr>
                    <w:t>' . htmlspecialchars($subtitle) . '</w:t>
                </w:r>
            </w:p>';
        }

        $this->paragraphs[] = $xml;
        return $this;
    }

    public function addHeading1(string $text): self
    {
        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:spacing w:before="360" w:after="120"/>
                <w:pBdr>
                    <w:bottom w:val="single" w:sz="12" w:space="4" w:color="1E40AF"/>
                </w:pBdr>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:sz w:val="36"/>
                    <w:color w:val="1E40AF"/>
                </w:rPr>
                <w:t>' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addHeading2(string $text): self
    {
        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="80"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:sz w:val="28"/>
                    <w:color w:val="0F172A"/>
                </w:rPr>
                <w:t>' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addHeading3(string $text): self
    {
        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:spacing w:before="180" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:sz w:val="24"/>
                    <w:color w:val="334155"/>
                </w:rPr>
                <w:t>' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addParagraph(string $text, bool $bold = false, string $color = '1E293B'): self
    {
        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:spacing w:before="60" w:after="80" w:line="276" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    ' . ($bold ? '<w:b/>' : '') . '
                    <w:sz w:val="22"/>
                    <w:color w:val="' . $color . '"/>
                </w:rPr>
                <w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addBullet(string $text, string $prefix = ''): self
    {
        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:ind w:left="480" w:hanging="240"/>
                <w:spacing w:before="40" w:after="60"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:color w:val="1E40AF"/>
                </w:rPr>
                <w:t>•  </w:t>
            </w:r>
            ' . ($prefix ? '<w:r><w:rPr><w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/><w:b/><w:sz w:val="22"/><w:color w:val="0F172A"/></w:rPr><w:t xml:space="preserve">' . htmlspecialchars($prefix) . ' </w:t></w:r>' : '') . '
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:sz w:val="22"/>
                    <w:color w:val="334155"/>
                </w:rPr>
                <w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addCallout(string $text, string $type = 'NOTE'): self
    {
        $borderColor = match ($type) {
            'WARNING' => 'DC2626',
            'SUCCESS' => '059669',
            'IMPORTANT' => 'D97706',
            default => '2563EB',
        };
        $bgColor = match ($type) {
            'WARNING' => 'FEF2F2',
            'SUCCESS' => 'ECFDF5',
            'IMPORTANT' => 'FFFBEB',
            default => 'EFF6FF',
        };

        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:pBdr>
                    <w:left w:val="single" w:sz="36" w:space="12" w:color="' . $borderColor . '"/>
                </w:pBdr>
                <w:shd w:val="clear" w:color="auto" w:fill="' . $bgColor . '"/>
                <w:spacing w:before="120" w:after="120"/>
                <w:ind w:left="240" w:right="240"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:b/>
                    <w:sz w:val="20"/>
                    <w:color w:val="' . $borderColor . '"/>
                </w:rPr>
                <w:t>[' . $type . '] </w:t>
            </w:r>
            <w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>
                    <w:sz w:val="20"/>
                    <w:color w:val="1E293B"/>
                </w:rPr>
                <w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>
            </w:r>
        </w:p>';
        return $this;
    }

    public function addCodeBlock(string $code): self
    {
        $lines = explode("\n", $code);
        $runs = '';
        foreach ($lines as $i => $line) {
            $runs .= '<w:r>
                <w:rPr>
                    <w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/>
                    <w:sz w:val="19"/>
                    <w:color w:val="0F172A"/>
                </w:rPr>
                <w:t xml:space="preserve">' . htmlspecialchars($line) . '</w:t>
            </w:r>';
            if ($i < count($lines) - 1) {
                $runs .= '<w:r><w:br/></w:r>';
            }
        }

        $this->paragraphs[] = '<w:p>
            <w:pPr>
                <w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/>
                <w:spacing w:before="100" w:after="100"/>
                <w:ind w:left="240" w:right="240"/>
            </w:pPr>
            ' . $runs . '
        </w:p>';
        return $this;
    }

    public function addTable(array $headers, array $rows): self
    {
        $colWidth = (int)(9000 / count($headers));
        $xml = '<w:tbl>
            <w:tblPr>
                <w:tblW w:w="9000" w:type="dxa"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                </w:tblBorders>
            </w:tblPr>';

        // Header Row
        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($headers as $h) {
            $xml .= '<w:tc>
                <w:tcPr>
                    <w:tcW w:w="' . $colWidth . '" w:type="dxa"/>
                    <w:shd w:val="clear" w:color="auto" w:fill="1E40AF"/>
                </w:tcPr>
                <w:p>
                    <w:pPr><w:spacing w:before="80" w:after="80"/><w:jc w:val="left"/></w:pPr>
                    <w:r><w:rPr><w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/><w:b/><w:sz w:val="20"/><w:color w:val="FFFFFF"/></w:rPr><w:t>' . htmlspecialchars($h) . '</w:t></w:r>
                </w:p>
            </w:tc>';
        }
        $xml .= '</w:tr>';

        // Data Rows
        foreach ($rows as $rIndex => $row) {
            $bg = ($rIndex % 2 === 1) ? 'F8FAFC' : 'FFFFFF';
            $xml .= '<w:tr>';
            foreach ($row as $cell) {
                $xml .= '<w:tc>
                    <w:tcPr>
                        <w:tcW w:w="' . $colWidth . '" w:type="dxa"/>
                        <w:shd w:val="clear" w:color="auto" w:fill="' . $bg . '"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr><w:spacing w:before="60" w:after="60"/></w:pPr>
                        <w:r><w:rPr><w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/><w:sz w:val="20"/><w:color w:val="1E293B"/></w:rPr><w:t>' . htmlspecialchars((string)$cell) . '</w:t></w:r>
                    </w:p>
                </w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';
        $this->paragraphs[] = $xml;
        return $this;
    }

    public function build(string $targetFile): bool
    {
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    ' . implode("\n", $this->paragraphs) . '
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';

        $packageRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

        $zip = new ZipArchive();
        if ($zip->open($targetFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $packageRelsXml);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return file_exists($targetFile);
    }
}

// ----------------------------------------------------------------------------
// BUILD COMPLETE DOCUMENT
// ----------------------------------------------------------------------------

$doc = new DocxBuilder();

$doc->addTitle('JOBPILOT AI — MASTER OPERATIONS MANUAL', 'Complete Feature Catalog, 100% Free Cloud Hosting, Free Mail Setup & Live Testing Guide')
    ->addParagraph('Version: 4.0 (Strict Free-Only Edition)  |  Architecture: Laravel 13 Modular Monolith  |  Database: TiDB Serverless & SQLite')
    ->addCallout('CRITICAL POLICY: JobPilot AI enforces strict zero-spend resource governance. No credit cards required for core operation, no paid API auto-upgrades, and no unverified external charges. If a free tier limit is reached, the system fails closed and preserves all candidate data.', 'IMPORTANT');

// ----------------------------------------------------------------------------
// SECTION 1: SYSTEM OVERVIEW & ARCHITECTURE
// ----------------------------------------------------------------------------
$doc->addHeading1('1. System Overview & Architecture Philosophy')
    ->addParagraph('JobPilot AI is an enterprise-grade AI-powered career automation engine. It empowers candidates to discover job opportunities across zero-cost global aggregators, manage multi-role career profiles, generate tailored LaTeX CVs, automate hyper-personalized email outreach with Human-in-the-Loop review, optimize LinkedIn profiles, and track all applications in a permanent deduplicated registry.')
    ->addParagraph('Key System Principles:')
    ->addBullet('100% Free-Cost Policy: Configured via FREE_MODE=true and FREE_RESOURCES_ONLY=true. Paid models, paid endpoints, and paid API tiers are blocked automatically.', 'Zero-Spend Enforced:')
    ->addBullet('Anti-Abuse & Anti-Rotation: The application strictly respects provider Terms of Service. It forbids rotating throwaway accounts on the same provider to evade rate limits.', 'ToS Compliant:')
    ->addBullet('Zero-Duplicate Guarantee: Employs SHA-256 idempotency hashing, pessimistic database row locking, and mid-flight timeout reconciliation to ensure no recruiter ever receives duplicate emails.', 'Sending Safety:')
    ->addBullet('Local-First & Cloud-Ready: Operates 100% locally on XAMPP/PHP 8.4 or deploys seamlessly to permanently free cloud hosts (Oracle Always Free + TiDB Cloud Serverless).', 'Deployment Flexibility:');

// ----------------------------------------------------------------------------
// SECTION 2: COMPLETE FEATURE INVENTORY (13 MODULES)
// ----------------------------------------------------------------------------
$doc->addHeading1('2. Complete Feature Inventory & Module Guide')
    ->addParagraph('JobPilot AI is structured into 13 high-cohesion, low-coupling domain modules located in laravel/app/Modules/:');

$modules = [
    ['Identity & Auth', 'Custom session authentication, rate-limiting, CSRF protection, multi-tenant data isolation, and profile management.'],
    ['Dashboard Cockpit', 'Executive stats, Dynamic Free Email Allowance Monitor, Automatic Provider Switching Monitor, and system health status.'],
    ['UI/UX Navigation Shell', '3-zone app shell: Fixed top header, independently scrollable navigation container (overflow-y: auto), pinned settings footer, and mobile drawer.'],
    ['Role Profiles Manager', '5 pre-seeded role profiles (Laravel/PHP, WordPress, Full Stack, General, Freelance) with role-specific skills, salary preferences, and assignment rules.'],
    ['CV Studio & LaTeX Editor', 'LaTeX CV template compiler (-no-shell-escape sandbox), ATS keyword scoring, version preservation, and 4 CV assignment modes.'],
    ['Email Studio', 'Outbound outreach engine with 6 initial templates, dynamic placeholders, plain-text fallback, and Human-in-the-Loop review queue.'],
    ['Dynamic Quota Engine', 'Eliminates hardcoded 200/day limits. Dynamically measures provider allowances (PROVIDER_REPORTED, LOCALLY_MEASURED, DOCUMENTED_PLAN).'],
    ['Automatic Switching Engine', 'Evaluates 8 standardized provider health statuses, manages priority failovers (Gmail -> Outlook -> Zoho), and blocks paid fallbacks.'],
    ['Job Discovery & AI Search', 'Connectors for zero-cost job sources (Remotive, RemoteOK, Himalayas, Jobicy) and conversational Natural Language search parser.'],
    ['Applications Registry', 'Permanent application database with SHA-256 collision hashing and strict 60-day duplicate application prevention.'],
    ['Spreadsheet & Doc Import', 'CSV, XLSX, PDF, and raw text importer with auto-schema column mapping, preview, and error reconciliation.'],
    ['AI Gateway & Workspace', 'Unified multi-provider AI gateway (Gemini, Groq, OpenRouter, Cloudflare) with zero-cost enforcement and grounded prompt instructions.'],
    ['LinkedIn & Content Studio', 'Profile audit scoring, bullet point transformer, technical post ideator, caption generator, and manual approval scheduling queue.'],
    ['Archives & Disaster Recovery', 'Generates monthly compressed JSONL.gz and CSV exports with SHA-256 integrity checksums and automated database snapshot engine.'],
    ['System Health Monitoring', 'Subsystem health scorecard, security headers middleware, encrypted credential vault, and database backup engine.']
];

$doc->addTable(['Module Name', 'Capabilities & Core Functions'], $modules);

// ----------------------------------------------------------------------------
// SECTION 3: FREE CLOUD HOSTING GUIDE
// ----------------------------------------------------------------------------
$doc->addHeading1('3. 100% Free Cloud Hosting Guide (Permanent Zero-Cost)')
    ->addParagraph('You can host JobPilot AI permanently in the cloud without paying a single dollar using official, verified free tiers.')
    ->addHeading2('Option A: Oracle Cloud Always Free (Recommended Production Host)')
    ->addParagraph('Oracle Cloud Infrastructure (OCI) provides the most generous permanently free compute tier in the cloud industry.')
    ->addBullet('4 ARM64 OCPUs (Ampere Altra processor)', 'Compute:')
    ->addBullet('24 GB RAM (Can be split into 1 to 4 VMs; recommended: 1 VM with 4 OCPUs and 24GB RAM)', 'Memory:')
    ->addBullet('200 GB Always Free Block Storage (NVMe)', 'Storage:')
    ->addBullet('10 TB outbound bandwidth per month', 'Network:')
    ->addBullet('1 Free Public IPv4 address included', 'Public IP:')
    ->addHeading3('Step-by-Step Oracle Cloud Registration:')
    ->addBullet('Visit: https://signup.cloud.oracle.com/', 'Step 1:')
    ->addBullet('Enter your country, name, and email. Select your permanent "Home Region" (choose a region near you or us-ashburn-1/us-phoenix-1 with ample Ampere capacity).', 'Step 2:')
    ->addBullet('Identity Verification: A valid credit/debit card is required to verify identity. Oracle places a temporary $1 authorization hold which is immediately refunded. You will NEVER be charged unless you manually upgrade to Pay-As-You-Go.', 'Step 3:')
    ->addBullet('Log in to OCI Console -> Compute -> Instances -> Create Instance.', 'Step 4:')
    ->addBullet('Choose Image: Ubuntu 24.04 LTS (Canonical).', 'Step 5:')
    ->addBullet('Choose Shape: Change Shape -> Ampere (VM.Standard.A1.Flex) -> 4 OCPUs, 24 GB RAM.', 'Step 6:')
    ->addBullet('Save Private SSH Key: Download the generated private key to your computer (e.g., id_rsa.key).', 'Step 7:')
    ->addBullet('Click Create. In 2 minutes, your VM is running with a dedicated public IP address.', 'Step 8:')
    ->addHeading3('Configuring the VM & Running Automated Deploy Script:')
    ->addParagraph('Connect via SSH and run the pre-built setup script provided in your JobPilot AI repository:')
    ->addCodeBlock("ssh -i id_rsa.key ubuntu@<YOUR_VM_PUBLIC_IP>\n\n# Clone your repo or copy project files to /var/www/jobpilot\ngit clone <YOUR_GIT_REPO_URL> /var/www/jobpilot\n\n# Make deploy script executable and run\ncd /var/www/jobpilot\nchmod +x deploy/oracle_cloud_setup.sh\nsudo ./deploy/oracle_cloud_setup.sh")
    ->addParagraph('The automated script installs PHP 8.4-FPM, Nginx, Composer, Supervisor, UFW firewall, and Certbot SSL certificates automatically.')
    ->addHeading2('Option B: TiDB Cloud Serverless Starter (Permanent Free MySQL Database)')
    ->addParagraph('TiDB Cloud Serverless provides a fully managed, MySQL-compatible distributed database with automated failover and zero maintenance.')
    ->addBullet('5 GiB Row Storage (Forever Free)', 'Free Capacity:')
    ->addBullet('50 Million Request Units (RU) per month (ample for 500,000+ candidate actions)', 'Throughput:')
    ->addBullet('Zero credit card required at sign-up', 'Billing:')
    ->addHeading3('Step-by-Step TiDB Cloud Setup:')
    ->addBullet('Go to: https://tidbcloud.com/ and sign in with Google or GitHub (no credit card needed).', 'Step 1:')
    ->addBullet('Click "Create Cluster" -> Select "Serverless" (Free Tier) -> Select your cloud provider and region.', 'Step 2:')
    ->addBullet('Click "Create". Your database cluster is provisioned in under 30 seconds.', 'Step 3:')
    ->addBullet('Click "Connect" -> choose "General Connection" -> generate a secure root password.', 'Step 4:')
    ->addBullet('Download the CA certificate bundle (isrgrootx1.pem) and place it in laravel/storage/certs/ca.pem.', 'Step 5:')
    ->addBullet('Update your laravel/.env file with the connection parameters:', 'Step 6:')
    ->addCodeBlock("DB_CONNECTION=tidb\nDB_HOST=gateway01.us-east-1.prod.aws.tidbcloud.com\nDB_PORT=4000\nDB_DATABASE=jobpilot\nDB_USERNAME=xxxxxx.root\nDB_PASSWORD=your_secure_password\nTIDB_SSL=true\nMYSQL_ATTR_SSL_CA=storage/certs/ca.pem")
    ->addHeading2('Option C: Instant Live Tunnel (Test Publicly Without Any Cloud VM)')
    ->addParagraph('If you want to test live on your phone or share with recruiters immediately from your existing Windows PC without configuring a cloud server, use Cloudflare Tunnels (100% Free):')
    ->addCodeBlock("# 1. Install Cloudflare Tunnel (Windows Powershell)\nwinget install Cloudflare.cloudflared\n\n# 2. Expose your running local server (port 8000) to a live public HTTPS URL\ncloudflared tunnel --url http://127.0.0.1:8000")
    ->addCallout('Cloudflare outputs a public HTTPS URL (e.g., https://random-name.trycloudflare.com). Open this URL on any phone, tablet, or external computer to test the live application immediately!', 'SUCCESS')
    ->addHeading2('Option D: Render.com Free Web Service (Zero Credit Card Required)')
    ->addParagraph('Render.com offers a free Docker-based Web Service plan with automated SSL and GitHub deployment. It requires zero credit card at signup.')
    ->addBullet('512 MB RAM, 0.1 CPU (Sufficient for personal candidate use)', 'Specs:')
    ->addBullet('Automated Let\'s Encrypt SSL and *.onrender.com subdomain', 'SSL:')
    ->addBullet('Zero Credit Card required at signup', 'Billing:')
    ->addHeading3('Render Free-Tier Gotchas & How JobPilot AI Overcomes Them:')
    ->addBullet('Ephemeral Disks: Render wipes local disks on restarts. You MUST use TiDB Cloud Serverless (Free) as your database, not local SQLite.', 'Gotcha 1:')
    ->addBullet('15-Minute Sleep on Inactivity: Free Render web services spin down after 15 minutes of idle time. Cold start takes ~40 seconds.', 'Gotcha 2:')
    ->addCallout('KEEP-AWAKE PRO TIP: Use a free uptime monitoring service like UptimeRobot (https://uptimerobot.com) or Cron-job.org to ping your Render URL (/up health endpoint) every 10 minutes. This prevents your Render app from ever going to sleep, keeping it 100% fast and responsive 24/7 at zero cost!', 'SUCCESS')
    ->addHeading3('Step-by-Step Render Deployment:')
    ->addBullet('Push your JobPilot AI repository to GitHub or GitLab.', 'Step 1:')
    ->addBullet('Log in to https://render.com/ with GitHub (no credit card needed).', 'Step 2:')
    ->addBullet('Click "New +" -> "Web Service" -> Connect your JobPilot AI GitHub repository.', 'Step 3:')
    ->addBullet('Configuration: Select "Docker" runtime, Root Directory: "laravel", Instance Type: "Free".', 'Step 4:')
    ->addBullet('Or use the pre-built Blueprint: Click "New +" -> "Blueprint" and connect your repo. Render will automatically read the root render.yaml file.', 'Step 5:')
    ->addBullet('Add your TiDB Cloud Database Environment Variables in Render Dashboard -> Environment (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).', 'Step 6:')
    ->addBullet('Click "Create Web Service". Render builds the Docker image and gives you a live HTTPS URL (e.g., https://jobpilot-ai.onrender.com) in 3 minutes!', 'Step 7:');

// ----------------------------------------------------------------------------
// SECTION 4: FREE EMAIL SERVICE PROVIDERS & CONNECTION SETUP
// ----------------------------------------------------------------------------
$doc->addHeading1('4. Free Email Service Providers & Step-by-Step Connection Setup')
    ->addParagraph('JobPilot AI eliminates application-side sending caps and integrates directly with verified free email providers:')

    ->addHeading2('Provider 1: Gmail API (Official OAuth 2.0 REST Protocol)')
    ->addParagraph('Gmail API is the most reliable sender for personal candidate job applications with excellent deliverability.')
    ->addBullet('Free Plan Guidance: ~500 recipients per rolling 24-hour window (Personal Gmail) or 2,000/day (Google Workspace free trial).', 'Allowance:')
    ->addBullet('Classification: LOCALLY_MEASURED (Google does not expose live remaining quota API).', 'Quota Source:')
    ->addBullet('Acceptable Use: VERIFIED_ALLOWED for 1-to-1 professional career outreach.', 'Policy Status:')
    ->addHeading3('Step-by-Step Gmail API Setup:')
    ->addBullet('Navigate to Google Cloud Console: https://console.cloud.google.com/', 'Step 1:')
    ->addBullet('Create a new project named "JobPilot AI".', 'Step 2:')
    ->addBullet('Go to APIs & Services -> Library -> Search "Gmail API" -> Click "Enable".', 'Step 3:')
    ->addBullet('Configure OAuth Consent Screen -> Select "External" -> Fill App Name ("JobPilot AI") and your email.', 'Step 4:')
    ->addBullet('Add Scope: Select ".../auth/gmail.send" (allows sending emails on your behalf).', 'Step 5:')
    ->addBullet('Add Test Users: Add your personal Gmail address to the test users list.', 'Step 6:')
    ->addBullet('Go to Credentials -> Create Credentials -> "OAuth Client ID" -> Application Type: "Web application".', 'Step 7:')
    ->addBullet('Add Authorized Redirect URI: http://127.0.0.1:8000/settings/oauth/gmail/callback (or your live domain).', 'Step 8:')
    ->addBullet('Copy Client ID and Client Secret into JobPilot AI Settings -> Credential Vault.', 'Step 9:')

    ->addHeading2('Provider 2: Google Apps Script Web App (Live Quota Connector)')
    ->addParagraph('Google Apps Script runs serverless JavaScript directly inside Google infrastructure and reports exact live quota.')
    ->addBullet('Free Plan Allowance: 100 recipients / calendar day (Consumer accounts) or 1,500/day (Workspace).', 'Allowance:')
    ->addBullet('Classification: PROVIDER_REPORTED via MailApp.getRemainingDailyQuota().', 'Quota Source:')
    ->addBullet('Acceptable Use: VERIFIED_ALLOWED for direct recruitment outreach.', 'Policy Status:')
    ->addHeading3('Step-by-Step Google Apps Script Deployment:')
    ->addBullet('Open Google Apps Script: https://script.google.com/ -> Click "New Project".', 'Step 1:')
    ->addBullet('Paste the following script into Code.gs:', 'Step 2:')
    ->addCodeBlock("function doPost(e) {\n  try {\n    var data = JSON.parse(e.postData.contents);\n    if (data.action === 'getQuota') {\n      var remaining = MailApp.getRemainingDailyQuota();\n      return ContentService.createTextOutput(JSON.stringify({success: true, remaining: remaining})).setMimeType(ContentService.MimeType.JSON);\n    }\n    if (data.action === 'sendEmail') {\n      MailApp.sendEmail({\n        to: data.to,\n        cc: data.cc || '',\n        bcc: data.bcc || '',\n        subject: data.subject,\n        htmlBody: data.htmlBody\n      });\n      return ContentService.createTextOutput(JSON.stringify({success: true, remaining: MailApp.getRemainingDailyQuota()})).setMimeType(ContentService.MimeType.JSON);\n    }\n  } catch (err) {\n    return ContentService.createTextOutput(JSON.stringify({success: false, error: err.toString()})).setMimeType(ContentService.MimeType.JSON);\n  }\n}")
    ->addBullet('Click Deploy -> New Deployment -> Select Type: "Web App".', 'Step 3:')
    ->addBullet('Set "Execute as": "Me (your email)" and "Who has access": "Anyone".', 'Step 4:')
    ->addBullet('Click Deploy -> Authorize Access -> Copy the generated Web App URL.', 'Step 5:')
    ->addBullet('Paste the Web App URL into JobPilot AI Settings -> Credential Vault -> Google Apps Script URL.', 'Step 6:')

    ->addHeading2('Provider 3: Microsoft Graph API (Outlook.com Free Personal Accounts)')
    ->addParagraph('Microsoft Graph provides official REST dispatch for `@outlook.com`, `@hotmail.com`, and `@live.com` personal accounts.')
    ->addBullet('Free Plan Guidance: ~300 recipients per rolling 24-hour window.', 'Allowance:')
    ->addBullet('Classification: LOCALLY_MEASURED.', 'Quota Source:')
    ->addBullet('Acceptable Use: VERIFIED_ALLOWED for 1-to-1 candidate job applications.', 'Policy Status:')
    ->addHeading3('Step-by-Step Outlook Graph Setup:')
    ->addBullet('Navigate to Microsoft Entra / Azure Portal: https://portal.azure.com/ -> App Registrations.', 'Step 1:')
    ->addBullet('Click "New Registration" -> Name: "JobPilot AI".', 'Step 2:')
    ->addBullet('Supported Account Types: Select "Accounts in any organizational directory and personal Microsoft accounts".', 'Step 3:')
    ->addBullet('Redirect URI: Web -> http://127.0.0.1:8000/settings/oauth/microsoft/callback.', 'Step 4:')
    ->addBullet('API Permissions -> Add a permission -> Microsoft Graph -> Delegated -> Select "Mail.Send" and "User.Read".', 'Step 5:')
    ->addBullet('Certificates & Secrets -> New client secret -> Copy Secret Value and Application (Client) ID into JobPilot AI Settings.', 'Step 6:')

    ->addHeading2('Provider 4: Zoho Mail (Forever Free Plan - 5 Users)')
    ->addParagraph('Zoho Mail provides a completely free business email suite for custom domains.')
    ->addBullet('Free Plan Allowance: 150 webmail recipients / calendar day (5 accounts, 5GB/user).', 'Allowance:')
    ->addBullet('Classification: LOCALLY_MEASURED.', 'Quota Source:')
    ->addBullet('Policy Status: REQUIRES_REVIEW (Free accounts restrict direct SMTP relay; must use Zoho REST API endpoint).', 'Policy Status:')
    ->addHeading3('Setup:')
    ->addParagraph('Register at https://www.zoho.com/mail/zohomail-pricing.html (Forever Free Plan) -> create OAuth Client at https://api-console.zoho.com/ with scope ZohoMail.messages.CREATE.')

    ->addHeading2('Transactional Providers Policy Warning (Mailjet, SMTP2GO, SendGrid)')
    ->addParagraph('While transactional services offer free monthly tiers (Mailjet: 200/day; SMTP2GO: 1,000/month), JobPilot AI classifies them as POLICY_NOT_ALLOWED for direct cold job applications.')
    ->addCallout('WHY BLOCKED: The Acceptable Use Policies of Mailjet and SMTP2GO explicitly require opt-in verification or existing business relationships. Sending unsolicited 1-to-1 job outreach through transactional relays risks account suspension and spam-folder placement. JobPilot AI protects your sender reputation by prioritizing authenticated personal mailboxes (Gmail, Outlook, Zoho).', 'WARNING');

// ----------------------------------------------------------------------------
// SECTION 5: LIVE TESTING GUIDE WITH REAL DATA
// ----------------------------------------------------------------------------
$doc->addHeading1('5. Step-by-Step Live Testing Guide With Your Real Data')
    ->addParagraph('Follow these steps to conduct end-to-end testing with your authentic resume, email accounts, and live job vacancies:')

    ->addHeading2('Step 1: Set Up Your Candidate Profile & Target Roles')
    ->addBullet('Log in at http://127.0.0.1:8000/login with candidate@jobpilot.test / Password123!.', 'Action 1:')
    ->addBullet('Navigate to Role Profiles (Sidebar -> 2. Profiles & CV -> Target Role Profiles).', 'Action 2:')
    ->addBullet('Edit the pre-seeded "Laravel / PHP Senior Developer" profile or create your own custom role.', 'Action 3:')
    ->addBullet('Define your real primary skills (e.g., PHP 8.4, Laravel 13, Vue.js, MySQL, Docker), target salary, and remote preference.', 'Action 4:')

    ->addHeading2('Step 2: Upload Your Real CV & Select an Assignment Mode')
    ->addBullet('Navigate to CV Studio -> CV Assignment Manager (http://127.0.0.1:8000/cv-studio/assignment).', 'Action 1:')
    ->addBullet('Upload your real PDF/DOCX resume or edit using the built-in LaTeX Studio editor.', 'Action 2:')
    ->addBullet('Choose one of the 4 assignment modes: Global Default CV, Role Profile Mapped CV, Individual Application CV, or AI-Recommended CV.', 'Action 3:')

    ->addHeading2('Step 3: Discover Real Job Opportunities or Import a Spreadsheet')
    ->addBullet('Navigate to Job Discovery -> Live Aggregators (http://127.0.0.1:8000/jobs).', 'Action 1:')
    ->addBullet('Use Natural Language Search: Type "Remote senior Laravel backend developer in Europe or US".', 'Action 2:')
    ->addBullet('Alternative: Import a spreadsheet of job listings via Applications -> Spreadsheet Import using CSV or XLSX format.', 'Action 3:')
    ->addBullet('Click "Stage Application" on any job vacancy. Notice that the system computes a multi-factor match score and generates a unique SHA-256 identity key.', 'Action 4:')

    ->addHeading2('Step 4: Connect Your Real Email Account in Credential Vault')
    ->addBullet('Navigate to Settings -> Credential Vault (http://127.0.0.1:8000/settings).', 'Action 1:')
    ->addBullet('Enable your chosen provider (Gmail API or Google Apps Script).', 'Action 2:')
    ->addBullet('Enter your verified OAuth Access Token or Apps Script Web App URL.', 'Action 3:')
    ->addBullet('Click "Ping / Test Connection" to verify real connectivity.', 'Action 4:')

    ->addHeading2('Step 5: Test Personalized Outreach With Human-in-the-Loop Review')
    ->addBullet('Navigate to Email Studio -> Compose / Outbox (http://127.0.0.1:8000/email-studio).', 'Action 1:')
    ->addBullet('Select your staged application -> Choose the "Job Application (Formal)" template.', 'Action 2:')
    ->addBullet('Dynamic placeholders ({candidate_name}, {company_name}, {role_title}) are automatically interpolated.', 'Action 3:')
    ->addBullet('Click "Enqueue for Review". Notice that the message enters the Human-in-the-Loop review queue in PendingReview status.', 'Action 4:')
    ->addBullet('Inspect the rendered HTML email and attached CV. Click "Approve & Dispatch" to execute the real send.', 'Action 5:')
    ->addBullet('Return to Dashboard: Verify that your email quota counter increments accurately, the provider scorecard logs the delivery, and the permanent application registry transitions to "Sent".', 'Action 6:');

// ----------------------------------------------------------------------------
// SECTION 6: SUMMARY TABLE OF FREE TIERS
// ----------------------------------------------------------------------------
$doc->addHeading1('6. Summary Comparison: Free Services & Allowances')
    ->addParagraph('Quick reference table of all verified zero-spend resources utilized in JobPilot AI:')

    ->addTable(
        ['Platform / Resource', 'Component', 'Verified Free Allowance', 'Reset Period', 'Classification'],
        [
            ['Oracle Cloud Infrastructure', 'Compute VM', '4 ARM OCPUs, 24 GB RAM, 200 GB Storage', 'Permanent', '100% Free Tier'],
            ['TiDB Cloud Serverless', 'MySQL Database', '5 GiB Storage, 50M Request Units / mo', 'Monthly', '100% Free Tier'],
            ['Cloudflare Tunnels', 'Public HTTPS Tunnel', 'Unlimited bandwidth, Free SSL', 'Permanent', '100% Free Tier'],
            ['Gmail API (OAuth2)', 'Email Outreach', '~500 recipients guidance', 'Rolling 24h', 'LOCALLY_MEASURED'],
            ['Google Apps Script', 'Webhook Mailer', '100 recipients / day', 'Daily (1 AM PST)', 'PROVIDER_REPORTED'],
            ['Outlook Graph API', 'Email Outreach', '~300 recipients guidance', 'Rolling 24h', 'LOCALLY_MEASURED'],
            ['Zoho Mail', 'Business Mailbox', '150 webmail recipients / day', 'Daily midnight', 'REQUIRES_REVIEW'],
            ['Gemini 2.5 Flash', 'AI LLM API', '15 RPM, 1M TPM, 1,500 RPD (Zero Cost)', 'Daily', 'Free API Key'],
            ['Groq Cloud', 'Fast AI Inference', '30 RPM, 14,400 RPD (Llama 3.3 70B)', 'Daily', 'Free API Key'],
            ['OpenRouter Free', 'AI Fallback', 'Unlimited free models (:free suffix)', 'Permanent', 'Free API Key']
        ]
    );

// Save document
if ($doc->build($outputPath)) {
    echo "SUCCESS: Word document created at: " . $outputPath . " (" . filesize($outputPath) . " bytes)\n";
} else {
    echo "ERROR: Failed to create Word document.\n";
}
