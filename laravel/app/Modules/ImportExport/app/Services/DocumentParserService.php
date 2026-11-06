<?php

namespace App\Modules\ImportExport\app\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SimpleXMLElement;
use ZipArchive;

class DocumentParserService
{
    /**
     * Parse a CSV file or CSV string into headers and rows.
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>}
     */
    public function parseCsv(string $contentOrPath): array
    {
        $content = File::exists($contentOrPath) ? File::get($contentOrPath) : $contentOrPath;

        // Strip UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return ['headers' => [], 'rows' => []];
        }

        // Auto-detect delimiter from first line
        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = count(str_getcsv($firstLine, $delim));
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $headers = array_map(fn($h) => trim(trim($h), "\"'"), str_getcsv($lines[0], $bestDelimiter));
        $rows = [];

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }

            $fields = str_getcsv($line, $bestDelimiter);
            $row = [];

            foreach ($headers as $colIndex => $colName) {
                $row[$colName] = isset($fields[$colIndex]) ? trim(trim($fields[$colIndex]), "\"'") : '';
            }

            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Parse an XLSX file natively via PHP ZipArchive & XML parsing (zero heavy external dependencies).
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>}
     */
    public function parseXlsx(string $filePath): array
    {
        if (!File::exists($filePath)) {
            throw new Exception("File not found at path: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Unable to open archive file as XLSX spreadsheet.");
        }

        // 1. Read Shared Strings (xl/sharedStrings.xml)
        $sharedStrings = [];
        $stringsXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXmlContent) {
            $xml = new SimpleXMLElement($stringsXmlContent);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Read Sheet 1 (xl/worksheets/sheet1.xml)
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXmlContent) {
            throw new Exception("Worksheet 'sheet1.xml' not found in XLSX package.");
        }

        $sheetXml = new SimpleXMLElement($sheetXmlContent);
        $rawRows = [];

        if (isset($sheetXml->sheetData->row)) {
            foreach ($sheetXml->sheetData->row as $row) {
                $currentRow = [];
                foreach ($row->c as $cell) {
                    $type = (string) $cell['t'];
                    $val = (string) $cell->v;

                    if ($type === 's' && isset($sharedStrings[(int) $val])) {
                        $cellVal = $sharedStrings[(int) $val];
                    } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                        $cellVal = (string) $cell->is->t;
                    } else {
                        $cellVal = $val;
                    }

                    $currentRow[] = trim($cellVal);
                }
                $rawRows[] = $currentRow;
            }
        }

        if (empty($rawRows)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map('trim', $rawRows[0]);
        $rows = [];

        for ($i = 1; $i < count($rawRows); $i++) {
            $rawRow = $rawRows[$i];
            $row = [];
            foreach ($headers as $colIndex => $colName) {
                $row[$colName] = $rawRow[$colIndex] ?? '';
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Parse a Google Sheets public URL into standard rows.
     */
    public function parseGoogleSheetUrl(string $url): array
    {
        // Extract Google Sheet Key: https://docs.google.com/spreadsheets/d/{key}/...
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetKey = $matches[1];
            $csvExportUrl = "https://docs.google.com/spreadsheets/d/{$sheetKey}/export?format=csv";

            try {
                $response = Http::timeout(8)->get($csvExportUrl);
                if ($response->successful()) {
                    return $this->parseCsv($response->body());
                }
            } catch (\Throwable $e) {
                // If network fetch fails, throw friendly message
                throw new Exception("Could not fetch Google Sheet CSV: {$e->getMessage()}. Ensure sheet sharing is set to 'Anyone with the link can view'.");
            }
        }

        throw new Exception("Invalid Google Sheets URL format. Expected 'https://docs.google.com/spreadsheets/d/...'.");
    }

    /**
     * Parse unstructured email confirmation or application snippet into a structured row.
     */
    public function parseTextSnippet(string $text): array
    {
        $company = 'Unknown Company';
        $title = 'Software Engineer';
        $status = 'Applied';
        $url = null;
        $date = date('Y-m-d');

        if (preg_match('/(?:Company|Organization|At|Employer):\s*([^\r\n]+)/i', $text, $m)) {
            $company = trim($m[1]);
        }
        if (preg_match('/(?:Position|Title|Role|Job Title):\s*([^\r\n]+)/i', $text, $m)) {
            $title = trim($m[1]);
        }
        if (preg_match('/(?:Status|Stage|State):\s*([^\r\n]+)/i', $text, $m)) {
            $status = trim($m[1]);
        }
        if (preg_match('/(?:Date|Applied on|Submission Date):\s*([^\r\n]+)/i', $text, $m)) {
            $parsedDate = strtotime(trim($m[1]));
            if ($parsedDate) {
                $date = date('Y-m-d', $parsedDate);
            }
        }
        if (preg_match('/(https?:\/\/[^\s]+)/i', $text, $m)) {
            $url = trim($m[1]);
        }

        return [
            'headers' => ['Company', 'Job Title', 'Status', 'Date Applied', 'URL'],
            'rows' => [
                [
                    'Company' => $company,
                    'Job Title' => $title,
                    'Status' => $status,
                    'Date Applied' => $date,
                    'URL' => $url ?? '',
                ],
            ],
        ];
    }
}

