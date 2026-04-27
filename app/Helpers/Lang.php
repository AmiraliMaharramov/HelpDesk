<?php
/**
 * QuickFixDesk — Language / Translation Helper
 */

class Lang
{
    /** @var array<string,string> */
    private static array $strings = [];
    private static string $current = 'en';

    /**
     * Load translations for the given locale.
     * Falls back to English if the locale file is missing.
     */
    public static function load(string $locale): void
    {
        $supported = SUPPORTED_LANGS;

        if (!in_array($locale, $supported, true)) {
            $locale = APP_LANG;
        }

        self::$current = $locale;

        $path = LANG_PATH . '/' . $locale . '.json';

        if (!file_exists($path)) {
            // Fallback to English
            $path = LANG_PATH . '/en.json';
        }

        $json = file_get_contents($path);
        self::$strings = json_decode($json, true) ?? [];
    }

    /**
     * Translate a key, replacing :placeholder tokens.
     *
     * @param string               $key
     * @param array<string,string> $replace  ['min' => '8']
     */
    public static function get(string $key, array $replace = []): string
    {
        $text = self::$strings[$key] ?? $key;

        foreach ($replace as $search => $value) {
            $text = str_replace(':' . $search, $value, $text);
        }

        return $text;
    }

    /**
     * Shorthand alias.
     */
    public static function t(string $key, array $replace = []): string
    {
        return self::get($key, $replace);
    }

    public static function current(): string
    {
        return self::$current;
    }
}

/**
 * Global shorthand function for templates.
 *
 * @param array<string,string> $replace
 */
function __t(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}
