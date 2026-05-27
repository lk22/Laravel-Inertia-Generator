<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use InvalidArgumentException;

class GenerateCommand extends Command
{
    protected $signature = "inertia:generate
        {--type= : The type of artifact to generate (e.g. 'pages' or 'components' or 'layouts')}
        {--name= : The name of the page/component to generate (e.g. 'Dashboard/Stats' or 'User/Profile')}
        {--stack= : Manually specify the frontend framework to target (e.g. 'vue3', 'react', 'svelte')}
        {--ts-types : Whether to generate TypeScript types (if supported by the detected framework)}
        {--interface : Whether to generate an interface for the resource (if supported by the detected framework)}
        {--props= : Whether to include a props definition in the generated type or interface definition (if supported by the detected framework and if --ts-types or --interface is set)}
        {--force : Overwrite existing files without prompting}";

    protected $description = 'Generate an Inertia page/component based on the detected frontend framework';
    protected string $type;
    protected string $stack;
    protected bool $force;

    public function handle(FrontendFrameworkDetector $detector): int
    {
        try {
            $framework = $this->option('stack') !== null && $this->option('stack') !== ''
                ? $detector->detect($this->option('stack'))
                : $detector->detect();
        } catch (CouldNotDetectFrameworkException $e) {
            $this->components->error($e->getMessage());
            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Detected %s via %s: %s',
            $framework->profile->label(),
            $framework->source,
            $framework->evidence
        ));

        // Here you would add the logic to generate the page/component based on the detected framework
        $name = $this->option('name') ?? 'UnnamedComponent';
        $force = (bool) $this->option('force');
        $stack = $framework->profile->name;
        $type = $this->option('type') ?? 'component';
        $has_ts_types = (bool) $this->option('ts-types');
        $has_interface = (bool) $this->option('interface');
        $props = $this->option('props');
        // generate the file using the appropriate stub based on the detected framework and provided type/name
        $this->generate(
            type: $type,
            name: $name,
            stack: $stack,
            force: $force,
            has_ts_types: $has_ts_types,
            has_interface: $has_interface,
            props: $props
        );

        return Command::SUCCESS;
    }

    protected function generate(string $type, string $name, string $stack, bool $force, bool $has_ts_types, bool $has_interface, ?string $props = null): void
    {

        // only allow --props if either --ts-types or --interface option is set
        // since props can only be generated if there is a type or interface
        if ((isset($props) && is_string($props)) && ! ($has_ts_types || $has_interface) ) {
            $this->components->error('The --props option requires either --ts-types or --interface to be set.');
            return;
        }

        $folder = "";
        if ( str_contains($name, '/') ) {
            $parts = explode('/', $name);
            $name = array_pop($parts);
            $folder = implode('/', $parts) . '/';

            // nake sure the folder exists
            if (! file_exists(base_path("resources/js/{$type}/{$folder}")) ) {
                mkdir(base_path("resources/js/{$type}/{$folder}"), 0755, true);
            }
        }

        // This is a placeholder method. You would implement the actual generation logic here,
        // such as determining the correct stub to use based on the profile, replacing placeholders in the stub with the provided name, and writing the file to the appropriate location.
        $this->info("Component generation logic would go here. (Type: {$type}, Profile: {$stack}, Name: {$name}, Force: " . ($force ? 'true' : 'false') . ")");

        // get the correct stub based on the profile
        // replace placeholders in the stub with the provided name
        // write the file to the appropriate location, checking for existing files if $force is false
        $this->info("Attempting to retrieve stub for stack '{$stack}' and type '{$type}'...");

        $stubContent = $this->get_stub_for_profile($stack, $type);
        $stubContent = str_replace('{{ name }}', $name, $stubContent);

        $parsedProps = [];

        if (is_string($props) && trim($props) !== '') {
            $parsedProps = array_map(function(string $rawProp): array {
                $parts = array_map('trim', explode(':', $rawProp, 2));

                if ( count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' ) {
                    $this->components->error("Invalid prop definition: '{$rawProp}'. Each prop must be in the format 'propName: propType'.");
                    return [];
                }

                return [
                    'name' => $parts[0],
                    'type' => $parts[1]
                ];
            }, array_filter(array_map('trim', explode(';', $props))));
        }

        // Build a comma-separated list of props for the component definition (e.g. "prop1, prop2, prop3")
        $componentProps = implode(', ', array_column($parsedProps, 'name'));

        // Build TS field lines
        $typeProps = implode("\n", array_map(
            fn(array $prop): string => "{$prop['name']}: {$prop['type']};",
            $parsedProps
        ));

        $vueTypeProps = implode("\n", array_map(
            fn(array $prop): string => " {$prop['name']}: '',",
            $parsedProps
        ));

        if ( $typeProps === '') {
            $typeProps = " // define your props here\n";
        }

        $stubContent = str_replace('{{ props }}', $componentProps, $stubContent);
        $stubContent = str_replace('{{ TypeProps }}', "$vueTypeProps", $stubContent);

        if ($has_ts_types) {
            $typeScriptTypeDefinition = "type {$name}Props = {\n{$typeProps}\n};";
            $stubContent = str_replace('{{ typeDefinition }}', $typeScriptTypeDefinition, $stubContent);
            $stubContent = str_replace('{{ TypeName }}', "{$name}Props", $stubContent);
        } elseif ($has_interface) {
            $interfaceDefinition = "interface {$name}Props {\n{$typeProps}\n}";
            $stubContent = str_replace('{{ typeDefinition }}', $interfaceDefinition, $stubContent);
            $stubContent = str_replace('{{ TypeName }}', "{$name}Props", $stubContent);
        }

        $this->info("Generated stub content:\n" . $stubContent);

        $extension = match($stack) {
            'react' => 'tsx',
            'vue' => 'vue',
            'svelte' => 'svelte',
            default => 'txt'
        };

        $filePath = ($folder !== '')
            ? base_path("resources/js/{$type}/{$folder}{$name}.{$extension}")
            : base_path("resources/js/{$type}/{$name}.{$extension}");

        // throwing an error if the file already exists and --force is not set
        if (file_exists($filePath) && ! $force) {
            $this->error("File already exists at {$filePath}. Use --force to overwrite.");
            return;
        }

        file_put_contents($filePath, $stubContent);
        $this->info("File generated at: {$filePath}");
    }

    protected function get_stub_for_profile(string $stack, string $type): string
    {
        $stubContent = file_get_contents(dirname(__FILE__, 3) . "/stubs/{$stack}/{$type}.stub"); // You would need to create these stub files in the appropriate directory structure within your package

        if (! $stubContent) {
            $this->error("No template found for framework '{$stack}' and type '{$type}'.");
        }

        return $stubContent;
    }

    protected function get_placeholders_for_type(string $type): array
    {
        return match($type) {
            'pages' => [
                '{{ name }}',
                '{{ TypeName }}',
                '{{ TypeProps }}',
                '{{ props }}'
            ],
            'components' => [
                '{{ name }}',
                '{{ TypeName }}',
                '{{ TypeProps }}',
                '{{ props }}'
            ],
            'layouts' => [
                '{{ name }}',
                '{{ TypeName }}',
                '{{ TypeProps }}',
                '{{ props }}'
            ],
            default => []
        };
    }
}