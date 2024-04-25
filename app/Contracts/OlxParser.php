<?php

namespace App\Contracts;

interface OlxParser
{
    /**
     * Parse offers
     *
     * @param string $url The URL of the website to parse.
     * @return array An array of parsed offers data.
     */
    public function parseOffers(string $url): array;
}
