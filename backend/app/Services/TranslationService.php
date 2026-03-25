<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TranslationService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 10.0]);
    }

    /**
     * Translate text using MyMemory (free, primary) with Google Translate as fallback.
     *
     * @throws \RuntimeException
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        if ($sourceLang === $targetLang) {
            return $text;
        }

        try {
            return $this->myMemoryTranslate($text, $sourceLang, $targetLang);
        } catch (\Exception $e) {
            // Fallback to Google Translate if configured
            $googleKey = env('GOOGLE_TRANSLATE_API_KEY');
            if ($googleKey) {
                try {
                    return $this->googleTranslate($text, $sourceLang, $targetLang, $googleKey);
                } catch (\Exception) {
                    // Both failed
                }
            }
            throw new \RuntimeException('Translation unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Translate all missing fields for a dish.
     * Given name in one language, auto-fill the others.
     */
    public function autoTranslateDish(array $fields): array
    {
        $langs = ['fr', 'en', 'es'];
        // Find source language (first non-empty)
        $sourceLang = null;
        $sourceText = null;
        foreach ($langs as $lang) {
            if (!empty($fields["name_{$lang}"])) {
                $sourceLang = $lang;
                $sourceText = $fields["name_{$lang}"];
                break;
            }
        }

        if (!$sourceLang) {
            return $fields;
        }

        foreach ($langs as $lang) {
            if ($lang !== $sourceLang && empty($fields["name_{$lang}"])) {
                try {
                    $fields["name_{$lang}"] = $this->translate($sourceText, $sourceLang, $lang);
                } catch (\Exception) {
                    $fields["name_{$lang}"] = $sourceText; // fallback: same text
                }
            }
        }

        return $fields;
    }

    // --------- private methods ---------

    private function myMemoryTranslate(string $text, string $from, string $to): string
    {
        $langPair = strtoupper($from) . '|' . strtoupper($to);
        $url      = 'https://api.mymemory.translated.net/get?' . http_build_query([
            'q'    => $text,
            'langpair' => $langPair,
        ]);

        $key = env('MYMEMORY_API_KEY');
        if ($key) {
            $url .= '&key=' . $key;
        }

        $response = $this->http->get($url);
        $body     = json_decode((string) $response->getBody(), true);

        if (isset($body['responseData']['translatedText'])) {
            return (string) $body['responseData']['translatedText'];
        }

        throw new \RuntimeException('MyMemory translation failed: ' . ($body['responseStatus'] ?? 'unknown'));
    }

    private function googleTranslate(string $text, string $from, string $to, string $apiKey): string
    {
        $response = $this->http->post(
            'https://translation.googleapis.com/language/translate/v2?key=' . $apiKey,
            [
                'json' => [
                    'q'      => $text,
                    'source' => $from,
                    'target' => $to,
                    'format' => 'text',
                ],
            ]
        );

        $body = json_decode((string) $response->getBody(), true);
        $translated = $body['data']['translations'][0]['translatedText'] ?? null;

        if (!$translated) {
            throw new \RuntimeException('Google Translate returned no result');
        }

        return $translated;
    }
}
