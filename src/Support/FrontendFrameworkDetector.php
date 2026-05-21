<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

use Illuminate\Filesystem\Filesystem;
use Leoknudsen\LaravelInertiaGenerator\Support\DetectedFrontendFramework;
use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfileRepository as FrameworkProfile;
use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;

class FrontendFrameworkDetector
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly FrameworkProfile $profileRepository
    ){}

    private function packageJsonDetected(): bool
    {
        $packageJsonPath = $this->getPackageJsonPath();
        if ( ! $this->files->exists($packageJsonPath)) {
            return false;
        }

        return true;
    }

    private function getPackageJsonPath(): string
    {
        return $this->path('package.json');
    }

    public function detect(?string $stack = null): ?DetectedFrontendFramework
    {
        if ($stack != null && $stack !== '') {
            return new DetectedFrontendFramework(
                $this->profileRepository->for($stack),
                'package.json',
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
        if (! $this->packageJsonDetected()) {
            return null;
        }

        $decodedPackageJson = json_decode($this->files->get($this->getPackageJsonPath()), true);

        if (! is_array($decodedPackageJson)) {
            return null;
        }

        $dependencies = array_keys(array_merge(
            $decodedPackageJson['dependencies'] ?? [],
            $decodedPackageJson['devDependencies'] ?? []
        ));

        $matchedProfiles = [];
        foreach ( $this->profileRepository->all() as $profile ) {
            if (in_array($profile->adapter, $dependencies)) {
                $matchedProfiles[] = $profile;
            }
        }

        if (count($matchedProfiles) > 1) {
            $stacks = array_map(fn($profile): string => $profile->name, $matchedProfiles);
            throw CouldNotDetectFrameworkException::multipleStacks($stacks);
        }

        // if any single adapter is found, we can confidently detect the framework based on the package.json dependencies
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