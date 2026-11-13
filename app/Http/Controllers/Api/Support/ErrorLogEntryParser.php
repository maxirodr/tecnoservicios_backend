<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

/**
 * Lee las entradas del log `errors` (formato Monolog line) en la forma del contrato de GET /api/errors.
 * Las entradas escritas antes de SEG-02 traían valores del input: se devuelven solo las claves y la URL
 * sin query string (TL-11: extraído de ErrorLogController).
 */
final class ErrorLogEntryParser
{
    private const ENTRY_START = '/(?=\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\])/';

    /**
     * @return list<array<string, mixed>>
     */
    public static function parseFile(string $content): array
    {
        $chunks = preg_split(self::ENTRY_START, $content, -1, PREG_SPLIT_NO_EMPTY);

        return array_map(self::parse(...), $chunks === false ? [] : $chunks);
    }

    /**
     * @return array<string, mixed>
     */
    public static function parse(string $entry): array
    {
        preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]/', $entry, $timestampMatch);
        preg_match('/\]\s+(\w+)\.(\w+):/', $entry, $levelMatch);
        preg_match('/\w+\.\w+:\s(.+?)(?:\s*\{|\s*$)/m', $entry, $messageMatch);

        $context = [];
        if (preg_match('/(\{.*\})\s*$/s', $entry, $jsonMatch) === 1) {
            $decoded = json_decode($jsonMatch[1], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        // Las entradas viejas guardaban valores: se devuelven solo las claves (SEG-02).
        $input = $context['input'] ?? null;
        if (is_array($input) && ! self::isListOfStrings($input)) {
            $input = ErrorLogContext::inputKeys($input);
        }

        $url = $context['url'] ?? null;
        if (is_string($url)) {
            $url = explode('?', $url, 2)[0];
        }

        $parsed = [
            'timestamp' => $timestampMatch[1] ?? null,
            'level' => strtoupper($levelMatch[2] ?? 'ERROR'),
            'message' => trim($messageMatch[1] ?? ''),
            'action' => $context['action'] ?? null,
            'method' => $context['method'] ?? null,
            'url' => $url,
            'user_id' => $context['user_id'] ?? null,
            'ip' => $context['ip'] ?? null,
            'input' => $input,
            'exception_class' => $context['exception_class'] ?? null,
            'file' => $context['file'] ?? null,
            'trace_short' => $context['trace_short'] ?? null,
        ];

        if (isset($context['validation_errors']) && is_array($context['validation_errors'])) {
            $errors = $context['validation_errors'];
            $parsed['validation_errors'] = self::isListOfStrings($errors) ? $errors : ErrorLogContext::validationFields($errors);
        }

        return $parsed;
    }

    /**
     * @param  array<mixed>  $values
     */
    private static function isListOfStrings(array $values): bool
    {
        return array_is_list($values) && array_filter($values, is_string(...)) === $values;
    }
}
