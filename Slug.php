<?php

namespace GeoSocio\Core\Utils;

/**
 * Slug Utility.
 */
class Slug
{

    /**
     * Generates a slug from a string.
     *
     * @param string $text
     */
    public static function create(string $text) : string
    {
        $slug = trim($text);
        $slug = mb_strtolower($slug);
        $slug = str_replace(' ', '-', $slug);
        $slug = str_replace(['.', '(', ')'], '', $slug);
        $slug = preg_replace('/-{2,}/u', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }
}
