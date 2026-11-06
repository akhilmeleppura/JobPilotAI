<?php

namespace App\Modules\Email\app\Services;

use App\Modules\Email\app\Models\EmailTemplate;

class TemplateInterpolationService
{
    /**
     * Render subject and body of an email template with context variables.
     */
    public function renderTemplate(EmailTemplate $template, array $context): array
    {
        return [
            'subject' => $this->interpolate($template->subject_template, $context),
            'body' => $this->interpolate($template->body_template, $context),
        ];
    }

    /**
     * Replace all {{key}} placeholder variables in a string.
     */
    public function interpolate(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($context) {
            $key = $matches[1];
            if (array_key_exists($key, $context)) {
                $val = $context[$key];
                if (is_array($val)) {
                    return implode(', ', $val);
                }
                return (string) $val;
            }
            return $matches[0]; // Retain placeholder if variable not provided
        }, $text);
    }
}

