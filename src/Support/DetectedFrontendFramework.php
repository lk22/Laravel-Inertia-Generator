<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfile;

readonly class DetectedFrontendFramework
{
  public function __construct(
    public FrameworkProfile $profile,
    public string $source,
    public string $evidence
  ) {}
}