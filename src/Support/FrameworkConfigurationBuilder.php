<?php

namespace Leoknudsen\LaravelInertiaGenerator\Support;

class FrameworkConfigurationBuilder
{
    protected array $config;

    public function __construct(
        private readonly string $stack
    ){}

    public function setAdapter(string $adapter): self
    {
        $this->config['adapter'] = $adapter;
        return $this;
    }

    public function setEntryFiles(array $entryFiles): self
    {
        $this->config['entry_files'] = $entryFiles;
        return $this;
    }

    public function setPaths(array $paths): self
    {
        $this->config['paths'] = $paths;
        return $this;
    }

    public function setExtensions(array $extensions): self
    {
        $this->config['extensions'] = $extensions;
        return $this;
    }

    public function setStubSet(string $stubSet): self
    {
        $this->config['stub_set'] = $stubSet;
        return $this;
    }

    public function setContentSignatures(array $contentSignatures): self
    {
        $this->config['content_signatures'] = $contentSignatures;
        return $this;
    }

    public function build(): array
    {
        // get the config file array
        $frameworksConfig = config('inertia-generator.frameworks', []);

        if ($this->stack && isset($frameworksConfig[$this->stack])) {
            return $frameworksConfig[$this->stack];
        }

        return $this->config;
    }
}