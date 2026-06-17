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

    /**
     * Detects the framework using in the application
     *
     * @param mixed $stack
     * @return DetectedFrontendFramework
     */
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

    /**
     * detects the testing framework based on the 'testing_framework' configuration and the presence of corresponding dependencies in package.json.
     * This method is separate from the main detect() method because testing frameworks are optional and may not always be detectable,
     * so it allows for more flexible handling of the case where no testing framework is detected without causing the entire frontend framework detection to fail.
     *
     * @throws Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException
     * @return Leoknudsen\LaravelInertiaGenerator\Support\DetectedFrontendFramework|string|null Returns a DetectedFrontendFramework instance if a testing framework is detected, a string representing the testing framework stack if specified but not detected, or null if no testing framework is specified in the configuration.
     */
    public function detectTestingFramework(): string | DetectedFrontendFramework | null
    {
        $testingFrameworkStack = config('laravel-inertia-generator.testing_framework');
        if ($testingFrameworkStack) {
            if (! $this->packageJsonDetected()) {
                throw CouldNotDetectFrameworkException::testingFrameworkWithoutPackageJson($testingFrameworkStack);
            }

            $packageJsonPath = $this->getPackageJsonPath();
            $decodedPackageJson = json_decode($this->files->get($packageJsonPath), true);

            // allowed testing framework stacks and their corresponding dependencies to check for in package.json
            $testingFrameworkStackDependencies = [
                'react-testing-library' => '@testing-library/react',
                'vue-test-utils' => '@vue/test-utils',
                'svelte-testing-library' => '@testing-library/svelte',
            ];

            $matchedTestingFrameworks = [];
            foreach ( $testingFrameworkStackDependencies as $frameworkStack => $dependency ) {
                if ( in_array($dependency, array_keys(array_merge(
                    $decodedPackageJson['dependencies'] ?? [],
                    $decodedPackageJson['devDependencies'] ?? []
                ))) ) {
                    $matchedTestingFrameworks[] = $frameworkStack;
                }
            }

            // if no testing framework dependencies are found, we can return null to indicate that no testing framework was detected.
            // and the calling code can decide how to handle this case
            if ( count ($matchedTestingFrameworks) === 0 ) {
                return null;
            }

            if (count($matchedTestingFrameworks) > 1) {
                throw CouldNotDetectFrameworkException::multipleStacks($matchedTestingFrameworks);
            }

            return $matchedTestingFrameworks[0];
        }

        return null;
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