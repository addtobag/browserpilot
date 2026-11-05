<?php

namespace BrowserPilot\Support;

use Illuminate\Support\Str;
use Stringable;

final class Snapshot implements Stringable
{
    /**
     * @param  list<string>  $elements
     */
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly array $elements,
        public readonly string $text,
    ) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true) ?: [];

        return new self(
            url: (string) ($data['url'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            elements: array_values(array_map('strval', $data['elements'] ?? [])),
            text: (string) ($data['text'] ?? ''),
        );
    }

    public function forAgent(?int $maxText = null): string
    {
        $maxText ??= (int) config('pilot.tools.max_text', 6000);

        $elements = $this->elements === []
            ? '(none found)'
            : implode("\n", $this->elements);

        return trim(<<<TXT
        URL: {$this->url}
        Title: {$this->title}

        Interactive elements (use the number in [brackets] as "ref"):
        {$elements}

        Page text:
        {$this->truncatedText($maxText)}
        TXT);
    }

    public function __toString(): string
    {
        return $this->forAgent();
    }

    protected function truncatedText(int $max): string
    {
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", $this->text));

        return Str::limit($text, $max, "\n…(truncated)");
    }
}
