<?php

namespace Keyboardman\FilemanagerBundle\Security;

use Symfony\Component\HttpFoundation\Request;

class IframeTokenValidator
{
    public function __construct(
        private readonly bool $verify,
        private readonly array $hostTokens,
    ) {
    }

    public function isValid(Request $request): bool
    {
        if (false === $this->verify) {
            return true;
        }

        // Détection iframe
        $isIframe = 'iframe' === $request->headers->get('sec-fetch-dest');

        // Appel direct → PAS de contrôle
        if (!$isIframe) {
            return true;
        }

        //  iframe → contrôle obligatoire
        $host = $request->headers->get('origin') ?? parse_url($request->headers->get('referer') ?? '', PHP_URL_HOST);
        if (!$host || !isset($this->hostTokens[$host])) {
            return false;
        }

        //  récupérer le token
        $token = $request->query->get('token');

        if (!$token) {
            return false;
        }

        if (!isset($this->hostTokens[$host])) {
            return false;
        }

        return hash_equals($this->hostTokens[$host], $token);
    }
}
