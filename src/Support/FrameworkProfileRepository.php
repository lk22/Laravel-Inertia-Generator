<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;

class FrameworkProfileRepository
{
    private array $profiles = [];

    public function __construct()
    {
        $frameworks = config('inertia-generator.frameworks', []);

        foreach ($frameworks as $name => $profileConfiguration) {
            $this->profiles[$name] = FrameworkProfile::fromConfig($name, $profileConfiguration);
        }
    }

    public function for(string $name): FrameworkProfile
    {
        if (! array_key_exists($name, $this->profiles)) {
            throw CouldNotDetectFrameworkException::missingConfiguration("No framework profile found for '$name'", "framework.$name");
        }

        return $this->profiles[$name];
    }

    public function all(): array
    {
        return $this->profiles;
    }

    public function names(): array
    {
        return array_keys($this->profiles);
    }
}