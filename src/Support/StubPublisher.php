<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StubPublisher
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly string $outputDirectory,
        private readonly string $packagePath
    ) {}

    public function publish(FrameworkProfile $profile, bool $force = false): array {
        $directories = [
            'page' => $this->resolveDirectory($profile->pathCandidates('pages')),
            'component' => $this->resolveDirectory($profile->pathCandidates('components')),
            'layout' => $this->resolveDirectory($profile->pathCandidates('layouts')),
        ];

        $targets = [
            'page' => $directories['page'] . '/' . $this->outputDirectory . '/page.' . $profile->extension('page'),
            'component' => $directories['component'] . '/' . $this->outputDirectory . '/component.' . $profile->extension('component'),
            'layout' => $directories['layout'] . '/' . $this->outputDirectory . '/layout.' . $profile->extension('layout'),
        ];

        $replacements = [
            '{{ framework_label }}' => $profile->label(),
            '{{ adapter_package }}' => $profile->adapter,
            '{{ componentImportPath }}' => $this->relativeImportPath($targets['page'], $targets['component']),
            '{{ layoutImportPath }}' => $this->relativeImportPath($targets['page'], $targets['layout']),
        ];

        foreach ($targets as $type => $relativeTarget) {
            $absoluteTarget = $this->path($relativeTarget);
            $absoluteDirectory = dirname($absoluteTarget);

            if ( ! $this->files->isDirectory($absoluteDirectory) ) {
                $this->files->makeDirectory($absoluteDirectory, 0755, true);
            }

            if ( ! $force && $this->files->exists($absoluteTarget) ) {
                throw new RuntimeException("File already exists at $relativeTarget. Use --force to overwrite.");
            }

            $stubPath = $this->packagePath . '/stubs/' . $profile->stubSet . '/' . $type . '.stub';
            if (! $this->files->exists($stubPath)) {
                throw new RuntimeException("Stub file not found for type '$type' at expected path: $stubPath");
            }

            $this->files->put(
                $absoluteTarget,
                str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $this->files->get($stubPath)
                )
            );
        }

        return array_values($targets);
    }

    public function publishToCustomPath(string $customPath, bool $force = false, FrameworkProfile $profile): array {

        $sourcePath = $this->packagePath . '/stubs/' . '/' . $profile->stubSet . '/';
        $allFiles = $this->files->allFiles($sourcePath);

        foreach ($allFiles as $file) {
            $relativePath = str_replace($sourcePath, '', $file->getPathname());
            $targetPath = $this->path($customPath . '/' . $relativePath);

            if ( ! $force && $this->files->exists($targetPath) ) {
                throw new RuntimeException("File already exists at $targetPath. Use --force to overwrite.");
            }

            $targetDirectory = dirname($targetPath);
            if ( ! $this->files->isDirectory($targetDirectory) ) {
                $this->files->makeDirectory($targetDirectory, 0755, true);
            }

            $this->files->put(
                $targetPath,
                $this->files->get($file->getPathname())
            );
        }

        return array_map(fn($file) => $customPath . '/' . str_replace($sourcePath, '', $file->getPathname()), $allFiles);
    }

    public function resolveDirectory(array $candidates): string {
        foreach ( $candidates as $candidate ) {
            if ($this->files->isDirectory($this->path($candidate))) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    public function path(string $relativePath): string {
        return $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function relativeImportPath(string $from, string $to): string {
        $fromSegments = $this->segments($from);
        $toSegments = $this->segments($to);

        // remove common segments
        while ($fromSegments !== [] && $toSegments !== [] && $fromSegments[0] === $toSegments[0]) {
            array_shift($fromSegments);
            array_shift($toSegments);
        }

        $relativeSegments = array_merge(
            array_fill(0, count($fromSegments), '..'),
            $toSegments
        );

        $relativePath = implode('/', $relativeSegments);

        return str_starts_with($relativePath, '.') ? $relativePath : './' . $relativePath;
    }

    public function segments(string $path): array {
        return array_values(array_filter(explode('/', trim(str_replace('\\', '/', $path), '/'))));
    }
}