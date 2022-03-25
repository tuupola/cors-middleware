<?php

declare(strict_types=1);

namespace Tuupola\Middleware;

use Neomerx\Cors\Contracts\Http\ParsedUrlInterface;
use Neomerx\Cors\Strategies\Settings as BaseSettings;

class Settings extends BaseSettings
{
    public function isRequestOriginAllowed(ParsedUrlInterface $requestOrigin): bool
    {
        $isAllowed = parent::isRequestOriginAllowed($requestOrigin);

        if (!$isAllowed) {
            $isAllowed = $this->wildcardOriginAllowed($requestOrigin->getOrigin());
        }

        return $isAllowed;
    }

    private function wildcardOriginAllowed(string $origin): bool
    {
        foreach ($this->settings[self::KEY_ALLOWED_ORIGINS] as $allowedOrigin => $value) {
            if (fnmatch($allowedOrigin, $origin)) {
                return true;
            }
        }

        return false;
    }
}
