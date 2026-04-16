# Deployment & Private Package Install

Plate ships two install modes. Pick the one that matches your situation.

## Community install (no Acara credentials)

The public `acara-app/plate` repo is fully functional on its own — premium memory features silently no-op via null-object fallbacks.

```bash
git clone https://github.com/acara-app/plate.git
cd plate
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
```

What you get: meal planning, chat assistant (no memory recall or extraction), user profiles. What you don't get: memory recall, memory extraction, the seven AI memory tool adapters. Subscription gating is bypassed (`GatesPremiumFeatures` resolves to `NullPremiumGate`, which treats every user as premium).

No authentication or private repo access is required.

## Acara local development

Requires GitHub access to the private `acara-app/plate-core` repo.

```bash
# One-time, alongside the plate clone
cd ~/Herd
git clone git@github.com:acara-app/plate-core.git

cd plate
cp composer.local.example.json composer.local.json
composer update acara-app/plate-core --no-scripts
```

`composer.local.json` is gitignored (see `.gitignore:44`). The `wikimedia/composer-merge-plugin` in `composer.json` merges the `require` and `repositories` from your `composer.local.json` at install time. The path repo resolves via symlink — edits in `../plate-core/src/` are immediately live in `vendor/acara-app/plate-core/`.

**Why `composer update` instead of `composer install`?** The committed `composer.lock` is community-safe — it doesn't contain `acara-app/plate-core`. `composer install` would refuse because the merged require references a package not in the lock. `composer update acara-app/plate-core --no-scripts` resolves the overlay's require, adds plate-core to the local lock, and symlinks from the sibling. Don't commit the resulting lock change — keep the committed lock community-safe.

## Acara production deploy

Requires a fine-grained GitHub PAT with **Contents: read** scope on `acara-app/plate-core`.

### Preferred — `COMPOSER_AUTH` env var (no disk file)

Inject the token via your CI/CD secrets manager; never commit it.

```bash
export COMPOSER_AUTH='{"github-oauth":{"github.com":"'"$GITHUB_DEPLOY_TOKEN"'"}}'
cp composer.local.example.json composer.local.json
composer update acara-app/plate-core --no-scripts --no-interaction
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

Two-step: `composer update` resolves the overlay's require against the merged repository list (the VCS repo wins because the deploy server has no `../plate-core` sibling), writing plate-core into the lock. Then `composer install --no-dev` installs from the updated lock. Result: `vendor/acara-app/plate-core` is a full git clone of the private repo at the pinned commit.

### Fallback — project-local `auth.json`

Place this file at the project root (gitignored at `.gitignore:34`):

```json
{
    "github-oauth": {
        "github.com": "ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

Then:

```bash
cp composer.local.example.json composer.local.json
composer update acara-app/plate-core --no-scripts --no-interaction
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

### Dev convenience — global auth (one-time per machine)

For Acara developers who hit several private repos daily:

```bash
composer config --global --auth github-oauth.github.com ghp_xxxxxxxx
```

Writes to `~/.composer/auth.json`. Every Composer command on that machine can then resolve private GitHub repos without per-project auth.

## Token rotation

Deploy tokens should be fine-grained PATs with a 90-day expiry. When a token rotates, update the CI secret (preferred) or the `auth.json` on the server; `composer install` picks it up on the next deploy.

## Verifying the install mode

After `composer install`, check which binding path is active:

```bash
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo get_class(app(App\Contracts\Ai\Memory\ManagesMemoryContext::class)) . PHP_EOL;'
```

- Community mode: `App\Services\Null\NullMemoryContext`
- Acara mode: `App\Services\Memory\MemoryPromptContext`

## Troubleshooting

- `composer install` fails with `Could not authenticate against github.com` on an Acara deploy → auth token missing or expired. Regenerate in GitHub settings → Developer → Personal access tokens (fine-grained), scoped to `acara-app/plate-core`, **Contents: read**.
- `composer install` on community mode complains about `composer.local.json` not found → fine, the merge plugin warns but does not fail when the include file is missing.
- Memory features silently disabled in Acara mode (no recall / extraction) → confirm `composer.local.json` is in place and `vendor/acara-app/plate-core` exists. If missing, re-run `composer install`.
