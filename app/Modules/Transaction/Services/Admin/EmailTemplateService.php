<?php

namespace App\Services\Admin;

use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class EmailTemplateService
{
    public function index()
    {
        return EmailTemplate::paginate();
    }
    
    public function renderTemplate(string $slug, array $data = [], ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        $template = EmailTemplate::where('slug', $slug)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->firstOrFail();

        return [
            'subject' => $this->replaceHtmlVariables($template->subject, $data),
            'html' => $this->replaceHtmlVariables($template->html_template, $data),
            'text' => $template->text_template 
                ? $this->replaceTextVariables($template->text_template, $data) 
                : null,
        ];
    }

    protected function replaceHtmlVariables(string $content, array $data): string
    {
        // First handle standard Laravel {{ $var }} syntax
        $content = preg_replace_callback('/{{\s*\$(.*?)\s*}}/', function($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $content);

        // Then handle compact {{$var}} syntax
        $content = preg_replace_callback('/{{\$(.*?)}}/', function($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $content);

        return $content;
    }

    protected function replaceTextVariables(string $content, array $data): string
    {
        return Str::replace(
            array_map(fn($key) => "{{{$key}}}", array_keys($data)),
            array_values($data),
            $content
        );
    }
}