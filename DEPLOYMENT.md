# Deployment & Private Package Install

`acara-app/plate-core` is a private package in `require`. Installing plate therefore needs GitHub access to `github.com/acara-app/plate-core` — either via SSH key (local dev) or a GitHub PAT (CI / deploy). Composer pulls it transparently from the path repo (sibling checkout) when present, or from the private GitHub VCS repo otherwise.

## Acara local development

```bash
cd ~/Herd
git clone git@github.com:acara-app/plate-core.git   # sibling, one-time
git clone git@github.com:acara-app/plate.git
cd plate
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`composer install` symlinks plate-core from the sibling via the `path` repository — edits in `../plate-core/src/` are instantly live.

## Acara production deploy

### Preferred — `COMPOSER_AUTH` env var

Inject the GitHub token via your CI/CD secrets. Never commit it.

```bash
export COMPOSER_AUTH='{"github-oauth":{"github.com":"'"$GITHUB_DEPLOY_TOKEN"'"}}'
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

Deploy servers don't have the `../plate-core` sibling, so Composer falls through to the VCS repo and resolves via the injected token. `vendor/acara-app/plate-core` is a full git clone pinned to the commit in `composer.lock`.

### Fallback — project-local `auth.json`

```json
{
    "github-oauth": {
        "github.com": "ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

Placed at `plate/auth.json` (gitignored at `.gitignore:34`). Same `composer install --no-dev` as above.

### Dev convenience — global auth (one-time per machine)

For Acara developers who hit several private repos daily:

```bash
composer config --global --auth github-oauth.github.com ghp_xxxxxxxx
```

Writes to `~/.composer/auth.json`; applies to every project on that machine.

## Token requirements

Use a **fine-grained personal access token** with **Contents: read** scope on `acara-app/plate-core`. Rotate every 90 days. When a token rotates, update the CI secret or the project-local `auth.json`; Composer picks it up on the next install.

## Community fork (no Acara credentials)

Plate is open source, but `acara-app/plate-core` is private and follows the `spatie/laravel-multitenancy` convention — the package owns its cross-boundary contracts at `Acara\PlateCore\Contracts\Memory\*`. `composer remove acara-app/plate-core` alone will **not** produce a bootable app because `App\Ai\AgentBuilder` and `App\Actions\BuildAssistantAgentAction` type-hint interfaces that live in the missing package.

### Option A — supply your own auth (simplest if you're on the Acara team or have a license)

With a GitHub token that can read `acara-app/plate-core`:

```bash
export COMPOSER_AUTH='{"github-oauth":{"github.com":"'"$GITHUB_TOKEN"'"}}'
git clone https://github.com/acara-app/plate.git
cd plate
composer install
```

### Option B — ship a replacement stub package

If you don't have access to `acara-app/plate-core` but want a memoryless boot, publish a tiny public package (e.g. `yourorg/plate-core-stub`) that:

1. Declares in its `composer.json`:
   ```json
   {
       "name": "yourorg/plate-core-stub",
       "replace": { "acara-app/plate-core": "*" }
   }
   ```
2. Defines the two interfaces under `Acara\PlateCore\Contracts\Memory\*` and two concrete noop classes implementing them (e.g. returning `''` from `render()` and doing nothing from `dispatchIfEligible()`).
3. Binds the noops in a small service provider and registers it via `extra.laravel.providers`.

Then in your fork:

```bash
composer require yourorg/plate-core-stub
composer install
```

The `replace` directive tells Composer that the stub satisfies the `acara-app/plate-core` requirement; Laravel auto-discovers the stub's provider; the host resolves the noops via the same `config('memory.context_manager')` / `config('memory.extraction_dispatcher')` lookup the real package uses.

### Overriding the default implementations

Even in full-install mode the multitenancy-style config pattern lets you swap concretes without touching package code. Publish the config (`php artisan vendor:publish --tag=plate-core-config`), then in `config/memory.php`:

```php
'context_manager' => \App\YourCustom\ContextManager::class,
'extraction_dispatcher' => \App\YourCustom\ExtractionDispatcher::class,
```

Your custom classes must implement `Acara\PlateCore\Contracts\Memory\ManagesMemoryContext` and `Acara\PlateCore\Contracts\Memory\DispatchesMemoryExtraction` respectively.

## Verifying the install mode

```bash
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo get_class(app(Acara\PlateCore\Contracts\Memory\ManagesMemoryContext::class)) . PHP_EOL;'
```

- Premium mode (plate-core installed): `App\Services\Memory\MemoryPromptContext`
- Community mode (stub package installed): whatever noop class the stub binds via `config('memory.context_manager')`

## Troubleshooting

- `composer install` fails with `Could not authenticate against github.com` → missing or expired token. Regenerate in GitHub → Settings → Developer → Personal access tokens (fine-grained), scoped to `acara-app/plate-core`, **Contents: read**.
- `composer install` fails with `The "url" supplied for the path (../plate-core) repository does not exist` → the `path` repo option auto-skips when the directory is missing, so this error usually means the VCS repo also failed to authenticate. Fix the token first.
- Community contributor hits `Could not authenticate` → they don't have Acara credentials; point them at **Option A** above.
