<?php

namespace Leoknudsen\LaravelInertiaGenerator\Exceptions;

use RuntimeException;

class CouldNotDetectFrameworkException extends RuntimeException
{
    public static function unknownStack(string $stack, array $supportedStacks): self
    {
        return new self(sprintf(
            "Unsupported frontend stack detected. Found evidence of '%s', but it doesn't match any of the supported stacks: %s",
            $stack,
            implode(', ', $supportedStacks)
        ));
    }

    public static function multipleStacks(array $stacks): self
    {
        return new self(sprintf(
            "Multiple frontend stacks detected. Found evidence of the following stacks: %s. Please ensure only one frontend framework is used or adjust the configuration to exclude certain frameworks.",
            implode(', ', $stacks)
        ));
    }

    public static function nothingDetected(): self
    {
        return new self("Could not detect any supported frontend stack. Please ensure your configuration is correct and that you have the necessary entry files and content signatures in place.");
    }

    public static function missingConfiguration(string $message, string $configKey): self
    {
        return new self("Missing configuration: " . $message . " (Configuration key: " . $configKey . ")");
    }

    public static function testingFrameworkWithoutPackageJson(string $stack): self
    {
        return new self(sprintf(
            "Cannot detect testing framework '%s' because no package.json file was found. Please ensure you have a package.json file in your project root with the appropriate testing framework dependencies, or adjust the 'testing_framework' configuration to null to skip testing framework detection.",
            $stack
        ));
    }
}