<?php

namespace App\Support;

use Dom\HTMLDocument;
use Dom\Node;
use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/** The single HTML trust boundary for Reading sections. Plain text never passes through it. */
final class ReadingContent
{
    public const MAX_HTML_BYTES = 120000;

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(self::MAX_HTML_BYTES)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks(false)
            ->allowMediaSchemes([]);

        foreach (['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'ol', 'ul', 'li', 'code', 'pre'] as $tag) {
            $config = $config->allowElement($tag);
        }

        $config = $config->allowElement('a', ['href', 'title'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withAttributeSanitizer(new class implements AttributeSanitizerInterface
            {
                public function getSupportedElements(): ?array
                {
                    return ['a'];
                }

                public function getSupportedAttributes(): ?array
                {
                    return ['href'];
                }

                public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
                {
                    // Files belong in protected media sections, never public storage links.
                    $path = (string) parse_url($value, PHP_URL_PATH);
                    for ($i = 0; $i < 3; $i++) {
                        $path = rawurldecode($path);
                    }
                    $path = str_replace('\\', '/', $path);

                    return preg_match('~(?:^|/)(?:storage|lesson-files|assignment-submissions|payment-proofs)(?:/|$)~i', $path)
                        ? null : $value;
                }
            });

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        if (! mb_check_encoding($html, 'UTF-8') || strlen($html) > self::MAX_HTML_BYTES) {
            throw new InvalidArgumentException('Rich text must be valid UTF-8 and at most 120,000 bytes.');
        }

        $safe = $this->sanitizer->sanitize($html);

        // Do not persist content that would be truncated on a later render.
        if (strlen($safe) > self::MAX_HTML_BYTES) {
            throw new InvalidArgumentException('Sanitized rich text exceeds 120,000 bytes.');
        }

        $document = $this->document($safe);
        $stack = [[$document->body, 0]];
        $nodes = 0;
        while ($stack) {
            [$node, $depth] = array_pop($stack);
            if (++$nodes > 10000 || $depth > 64) {
                throw new InvalidArgumentException('Rich text is too deeply nested or complex.');
            }
            foreach ($node->childNodes as $child) {
                $stack[] = [$child, $depth + 1];
            }
        }

        return $safe;
    }

    private function document(string $safe): HTMLDocument
    {
        return HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$safe.'</body></html>',
            LIBXML_NOERROR,
            'UTF-8'
        );
    }

    /** Preserve paragraph/list boundaries and code whitespace for legacy and AI consumers. */
    public function plainText(string $html): string
    {
        $safe = $this->sanitize($html);
        $document = $this->document($safe);

        return trim($this->text($document->body), "\r\n");
    }

    private function text(Node $node, int $depth = 0): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->textContent;
        }

        $tag = strtolower($node->nodeName);
        if ($tag === 'br') {
            return "\n";
        }

        if ($tag === 'ol' || $tag === 'ul') {
            $items = [];
            $number = 0;
            foreach ($node->childNodes as $child) {
                if (strtolower($child->nodeName) === 'li') {
                    $prefix = $tag === 'ol' ? (++$number).'. ' : '- ';
                    $items[] = str_repeat('  ', $depth).$prefix.trim($this->children($child, $depth + 1), "\r\n");
                }
            }

            return "\n".implode("\n", $items)."\n";
        }

        $body = $this->children($node, $depth);
        if (in_array($tag, ['p', 'h2', 'h3', 'pre'], true)) {
            return $body."\n\n";
        }

        if ($tag === 'a' && $node instanceof \Dom\Element && $node->hasAttribute('href')) {
            $href = $node->getAttribute('href');

            return $body === $href ? $body : $body.' ('.$href.')';
        }

        return $body;
    }

    private function children(Node $node, int $depth): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->text($child, $depth);
        }

        return $text;
    }
}
