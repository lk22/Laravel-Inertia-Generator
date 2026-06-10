# Laravel-Inertia-Generator

Laravel Inertia Generator is a Laravel package for scaffolding new Inertia frontend resources in Laravel projects using React, Vue, or Svelte.


The package separates setup from generation:

- Install commands handle package setup and framework detection.
- Generate commands create new frontend artifacts from framework-specific stubs.

This means pages, components, and layouts are generated on dedicated generate commands, not during install.

## What You Can Generate

- New Inertia pages
- Reusable frontend components
- Layout components
- Framework-specific output files with the right extension (`.tsx`, `.vue`, `.svelte`)
- Generated resources based on your selected or detected frontend stack

## Why Use It

- Speeds up frontend scaffolding for Inertia projects
- Keeps page/component/layout structure consistent
- Reduces repetitive manual boilerplate
- Supports both automatic framework detection and explicit stack selection

## Typical Workflow

1. Run install/setup to publish package configuration and prepare the package in your app.
2. Run generation commands when you need new resources.
3. Choose what to generate (page/component/layout) and the target name.
4. Let the package generate files from the correct framework stubs.

In short: install prepares the package, generate commands create the actual frontend resources.

## What It Does (Visual)

![Laravel Inertia Generator workflow](docs/assets/what-it-does.svg)

The package workflow is simple:

- Setup once with the install command
- Generate pages, components, and layouts with dedicated generate commands
- Get framework-specific output files for React, Vue, or Svelte

## How To

### 1. Install and prepare the package

Run the install command to publish package configuration and initialize setup:

```bash
php artisan inertia-generator:install
```

If you want to explicitly target a frontend stack:

```bash
php artisan inertia-generator:install --stack=react
php artisan inertia-generator:install --stack=vue
php artisan inertia-generator:install --stack=svelte
```

Its recommended to use the stack that follows with your starter kit

### 2. Detect the current frontend framework (optional)

```bash
php artisan inertia:detect-framework
```

### 3. Generate new resources

Generate a page:

```bash
php artisan inertia:generate --type=page --name=Dashboard
```

Generate a component:

```bash
php artisan inertia:generate --type=component --name=User/ProfileCard
```

Generate a layout:

```bash
php artisan inertia:generate --type=layout --name=AppLayout
```

Force overwrite an existing generated file:

```bash
php artisan inertia:generate --type=component --name=User/ProfileCard --force
```

Generate for a specific stack:

```bash
php artisan inertia:generate --type=page --name=Reports/Index --stack=vue
```

Generate with type or interface and prop definitions

```bash
php artisan inertia:generate --type=page --name=ReportComponent --ts-types --props='prop1:string;prop2:number'
php artisan inertia:generate --type=page --name=ReportComponent --interface --props='prop1:string;prop2:number'
```

## current state
Current state for this package is currently in development, if you think this is a interesting package and maybe want to contribute in anyway, let me know :)

## newest feature
Adding support for Generation components or pages and layouts in sub folders with {Folder}/{Name} using --name options
```bash
php artisan inertia:generate --type=pages --name=Reports/Index
```

## Resource Type Mapping

When you generate a resource, the package picks a stub based on your stack and type, then writes the file to the matching frontend directory.

| Type | Stub file (by stack) | Output base path candidates | Example output (default output_directory: `inertia`) |
| --- | --- | --- | --- |
| `page` | `stubs/<stack>/page.stub` | `resources/js/pages` or `resources/js/Pages` | `resources/js/pages/inertia/StarterKitShowcase.<ext>` |
| `component` | `stubs/<stack>/component.stub` | `resources/js/components` or `resources/js/Components` | `resources/js/components/inertia/StarterKitPanel.<ext>` |
| `layout` | `stubs/<stack>/layout.stub` | `resources/js/layouts` or `resources/js/Layouts` | `resources/js/layouts/inertia/StarterKitLayout.<ext>` |

Notes:

- `<stack>` is one of `react`, `vue`, or `svelte`.
- `<ext>` comes from your stack configuration (`tsx`, `vue`, `svelte`).
- Output subdirectory comes from `output_directory` in [config/laravel-inertia-generator.php](config/laravel-inertia-generator.php).

## What's Remaining

The package is in a good state for setup + generation, but these improvements are still recommended:

1. Add stronger validation and user-friendly errors for invalid `--type`, missing `--name`, and missing stubs.
2. Adding support for custom template stubs
3. Expand automated tests for generation command behavior across all stacks and all types.
4. Adding support for adding tests for each generated page, component or layout files
5. Add end-to-end command examples in README that match the exact generated file names in current implementation.
6. Add optional video demo or GIF walkthrough of install + generate flow.
