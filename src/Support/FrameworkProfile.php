<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;

readonly class FrameworkProfile
{
  public function __construct(
    public string $name,
    public string $label,
    public string $adapter,
    public array $entryFiles,
    public array $paths,
    public array $extensions,
    public string $stubSet,
    public array $contentSignatures
  ) {}

  public static function fromConfig(string $name, array $configuration): self
  {
    foreach (['adapter', 'label', 'entry_files', 'paths', 'extensions', 'stub_set', 'content_signatures'] as $requiredKey) {
      if (!array_key_exists($requiredKey, $configuration)) {
        throw CouldNotDetectFrameworkException::missingConfiguration($name, $requiredKey);
      }
    }

    return new self(
      $name,
      (string) $configuration['label'],
      (string) $configuration['adapter'],
      array_values($configuration['entry_files']),
      $configuration['paths'],
      $configuration['extensions'],
      (string) $configuration['stub_set'],
      array_values($configuration['content_signatures'] ?? [])
    );
  }

  public function pathCandidates(string $type): array
  {
    return array_values($this->paths[$type] ?? []);
  }

  public function extension(string $type): string
  {
    return $this->extensions[$type] ?? '';
  }

  public function label(): string
  {
    return $this->label;
  }
}