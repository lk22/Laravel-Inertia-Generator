<?php

namespace LeoKnudsen\LaravelInertiaGenerator\Support;

use leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfile;

readonly class DetectedFrontendFramework
{
  public function __construct(
    public FrameworkProfile $profile,
    public string $source,
    public string $evidence
  ) {}
}