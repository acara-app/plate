# Plate Core Guidelines

Plate is split into a public Laravel app and a private package:

- `acara-app/plate` is the public app.
- `acara-app/plate-core` is the private SaaS package.
- `main` must stay community-safe.
- `private/plate-core` is the SaaS/deployment branch.

## Branch Rules

- Use `main` for public/community work.
- Use `private/plate-core` for SaaS work that needs the private package.
- Do not merge `private/plate-core` back into `main`.
- Bring public changes into SaaS by merging `main` into `private/plate-core`.
- Bring public-safe changes from `private/plate-core` into `main` with cherry-pick, and exclude `composer.json` and `composer.lock`.

Before committing to `main`, verify that these files do not add `acara-app/plate-core`:

- `composer.json`
- `composer.lock`

Also verify that app code does not import private package namespaces such as `Acara\PlateCore`.

## Public Extension Points

The public app owns cross-boundary contracts. Do not move these contracts into `plate-core`.

Memory contracts live in:

- `app/Contracts/Memory/ManagesMemoryContext.php`
- `app/Contracts/Memory/DispatchesMemoryExtraction.php`

Community-safe null implementations live in:

- `app/Services/Memory/NullMemoryPromptContext.php`
- `app/Services/Memory/NullMemoryExtractionDispatcher.php`

Default bindings belong in `App\Providers\AppServiceProvider` using `bindIf()`. This lets the public app boot without `plate-core`, while the private package can override the bindings when installed.

## Private Implementation

Real SaaS memory behavior belongs in `acara-app/plate-core`.

Do not copy private implementation classes into the Plate app. Do not publish or duplicate `PlateCoreServiceProvider.php` into the Plate repo. The package service provider should be discovered by Composer/Laravel when `acara-app/plate-core` is installed on `private/plate-core`.

When adding a new private feature that needs host integration:

1. Add the contract and null implementation to `main`.
2. Add tests proving `main` boots and behaves safely without the private package.
3. Merge `main` into `private/plate-core`.
4. Implement the real behavior in `acara-app/plate-core`.
5. Update `composer.lock` on `private/plate-core` with `composer update acara-app/plate-core`.

## Prompt and Tool Safety

Public prompts must not assume private tools exist.

For memory-specific prompt text, only render it when the real memory implementation is active. On `main`, `NullMemoryPromptContext` means memory storage is disabled and prompts must not instruct the model to call private-only tools such as `store_memory`.

Private AI memory tools should be registered by `plate-core`, not hard-coded into public `config/plate.php`.

## Deployment

Laravel Cloud production should deploy:

- repository: `acara-app/plate`
- branch: `private/plate-core`

The deployment branch contains the private Composer dependency. `main` must not.

Use `GITHUB_COMPOSER_TOKEN` in Laravel Cloud so Composer can download `acara-app/plate-core`. Never commit GitHub tokens, `auth.json`, or `COMPOSER_AUTH` values.

## Local Workflow

Community work:

```bash
git checkout main
composer install
```

SaaS work:

```bash
git checkout private/plate-core
composer install
```

After switching from `private/plate-core` back to `main`, run `composer install` so local `vendor` matches the public branch and no tests accidentally pass because `plate-core` is still installed.

## Safety Checks

Before opening or merging public changes into `main`, run checks like:

```bash
rg "acara-app/plate-core|Acara\\\\PlateCore|plate-core" composer.json composer.lock app config routes database tests resources
composer install
vendor/bin/pest
vendor/bin/pint --dirty --test
```

Expected result on `main`: no private Composer dependency, no private namespace imports, and a bootable app with null memory behavior.
