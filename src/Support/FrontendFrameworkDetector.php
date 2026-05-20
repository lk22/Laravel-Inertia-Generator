<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Illuminate\Filesystem\Filesystem;
use Leoknudsen\LaravelInertiaGenerator\Support\DetectedFrontendFramework;
use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;

class FrontendFrameworkDetector
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly FrameworkProfileRepository $profileRepository
    ){}

    public function detect(?string $stack = null): DetectedFrontendFramework
    {
        if ($stack != null && $stack !== '') {
            return new DetectedFrontendFramework(
                $this->profileRepository->for($stack),
                'command options',
                sprintf('--stack=%s', $stack)
            );
        }

        $packageJsonDetection = $this->detectFromPackageJson();
        if ($packageJsonDetection !== null) {
            return $packageJsonDetection;
        }

        $entryPointDetection = $this->detectFromEntryPoints();
        if ($entryPointDetection !== null) {
            return $entryPointDetection;
        }

        throw CouldNotDetectFrameworkException::nothingDetected();
    }

    public function detectFromPackageJson(): ?DetectedFrontendFramework
    {
        $packageJsonPath = $this->path('package.json');
        if ( ! $this->files->exists($packageJsonPath)) {
            return null;
        }

        $packageJson = json_decode($this->files->get($packageJsonPath), true);
        if ( ! is_array($packageJson)) {
            return null;
        }

        $dependencies = array_keys(array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        ));

        $matchedProfiles = [];
        foreach ( $this->profileRepository->all() as $profile ) {
            if (in_array($profile->adapter, $dependencies)) {
                $matchedProfiles[] = $profile;
            }
        }

        if (count($matchedProfiles) > 1) {
            throw CouldNotDetectFrameworkException::multipleStacks(array_map(fn($profile): string => $profile->name, $matchedProfiles));
        }

        if (count($matchedProfiles) === 1) {
            $profile = $matchedProfiles[0];

            return new DetectedFrontendFramework(
                $profile,
                'package.json dependencies',
                $profile->adapter
            );
        }

        return null;
    }

    public function detectFromEntryPoints(): ?DetectedFrontendFramework
    {
        $matches = [];

        foreach($this->profileRepository->all() as $profile) {
            foreach ($profile->entryFiles as $entryFile) {
                $path = $this->path($entryFile);
                if (! $this->files->exists($path) ) {
                    continue;
                }

                $content = $this->files->get($path);

                foreach ( $profile->contentSignatures as $signature ) {
                    if (str_contains($content, $signature)) {
                        $matches[] = new DetectedFrontendFramework(
                            $profile,
                            "entry file: $entryFile",
                            $entryFile
                        );

                        continue 3;
                    }
                }
            }

            foreach(['pages', 'components', 'layouts'] as $type) {
                foreach ( $profile->pathCandidates($type) as $pathCandidate ) {
                    $fullDirectory = $this->path($pathCandidate);
                    if ( ! $this->files->isDirectory($fullDirectory) ) {
                        continue;
                    }

                    foreach ( $this->files->allFiles($fullDirectory) as $file) {
                        if ( $file->getExtension() === $profile->extension($this->singularize($pathCandidate))) {
                            $matches[] = new DetectedFrontendFramework(
                                $profile,
                                "existing starter-kit files",
                                $fullDirectory
                            );
                        }
                        continue 4;
                    }
                }
            }
        }

        if ( count($matches) > 1 ) {
            throw CouldNotDetectFrameworkException::multipleStacks(
                array_map(static fn(DetectedFrontendFramework $match): string => $match->profile->name, $matches)
            );
        }

        return $matches[0] ?? null;
    }

    private function path(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
    }

    private function singularize(string $word): string
    {
        return rtrim($word, 's');
    }
}