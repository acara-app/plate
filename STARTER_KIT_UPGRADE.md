# Starter Kit Upgrade Report

- Date: 2026-05-15
- Kit: laravel/react-starter-kit
- Branch tracked: main
- Upgrade branch: starter-kit-upgrade/20260515-0948-react-multi-feature
- Base branch: main (created off main, not acara-core; per CLAUDE.md, public/community work belongs on main first)
- Commits applied: 5

## Features applied

### `1fc5122` — `.npmrc` ignore-scripts (full apply, scoped)
- Commit: `f6b9fba`
- Files updated (took upstream, scoped to `1fc5122`): `.npmrc`
- Why scoped: a later upstream commit (`0e38900`) moved the `public-hoist-pattern` line into `pnpm-workspace.yaml`. Project still uses `.npmrc` for both lines, so applied the feature-SHA content (both lines) rather than HEAD (only `ignore-scripts`).

### `fa49dc8` — Fortify-aware skip helper (partial)
- Commit: `79bb97f` + follow-up `753e6d4` (rector conformance)
- Files updated (manual merge): `tests/TestCase.php` — added `skipUnlessFortifyHas()` helper + `use Laravel\Fortify\Features;`. Kept `declare(strict_types=1);`.
- Files kept as-is: `tests/Feature/Auth/AuthenticationTest.php`, `EmailVerificationTest.php`, `PasswordResetTest.php`, `RegistrationTest.php`, `TwoFactorChallengeTest.php`, `VerificationNotificationTest.php`, `tests/Feature/Settings/TwoFactorAuthenticationTest.php` — project already has auth/2FA coverage; pulling in upstream's would duplicate.
- Follow-up: converted the `"{$feature}"` interpolation in the helper to `sprintf('%s', $feature)` to satisfy the project's `EncapsedStringsToSprintfRector` rule (was blocking `composer test`).

### `2225da5` — Toast notification primitives (partial)
- Commit: `a2e9bf5` + follow-up `d666a9a` (prettier conformance)
- Files added: `resources/js/components/ui/sonner.tsx` (upstream HEAD), `resources/js/hooks/use-flash-toast.ts` (upstream HEAD).
- Files updated (manual merge): `resources/js/types/ui.ts` (added `FlashToast` type; kept user's relative `./navigation` import); `package.json` (added `"sonner": "^2.0.0"` between `recharts` and `streamdown`).
- Files kept as-is: `resources/js/app.tsx` (user has i18n/PWA wiring upstream's diff would clobber), `app/Http/Controllers/Settings/ProfileController.php`, `app/Http/Controllers/Settings/SecurityController.php` (user's controller layout differs from upstream).
- Wiring required: render `<Toaster />` from `@/components/ui/sonner` at the React app root and flash sessions in the shape `FlashToast = { type, message }`.
- Follow-up: prettier (prettier-plugin-organize-imports) reordered `use-flash-toast.ts` imports.

## Features deliberately skipped

| SHA | Feature | Reason |
|---|---|---|
| `71d276a` | Align app variant API | Conflicts with user's `useSharedProps` abstraction; behavior change (default variant `header` → `sidebar`) would silently alter UX |
| `9e90c42` | Password visibility toggle | User has no `resources/js/pages/auth/` directory; 4 new files would be orphans. `delete-user.tsx` is heavily customized with Cashier subscription logic |
| `6606def` | Remove redundant useCallback/useMemo | Not refactors — these are major hook rewrites (new return types, `useSyncExternalStore`, `useHttp` in 2FA hook) that would break user's callers |
| `cadfedc` | SSR window reference fix | Adds `use-current-url.ts`; user has nothing that imports it. Only useful if `307d39e` (TooltipProvider move) is also applied, which it isn't |
| `04781f1` | Unified security page | Architectural consolidation; user has working separate `password.tsx` + `two-factor.tsx` pages |
| `307d39e` | TooltipProvider to app root | `app.tsx` is heavily customized (i18n locale handling, PWA registration, custom Inertia setup); taking upstream would wipe it |
| `2e22614` | `Rule` → `ValidationRule` traits | Adds two new traits in `app/Concerns/` that nothing in the project references — would be orphan files |
| `7583af5` | Vite font plugin | User's `welcome.tsx` is 9 lines (bespoke landing); upstream's is 394. Wholesale takeover not appropriate |
| `67cffb6` | 2FA autofocus | User's `two-factor-setup-modal.tsx` uses custom `Spinner`, `AlertError`, appearance handling — diffs go far beyond a single autofocus attribute |

## Manifest / lockfile updates

- `package.json` — added `sonner@^2.0.0` (commit `a2e9bf5`).
- `composer.json` — unchanged.
- `bun.lock`, `package-lock.json` — both gitignored in this project; `bun install` reran successfully and resolved sonner@2.0.7. No lockfile commit needed.

## Verification

- Baseline (pre-upgrade, on `main`):
  - `php_tests` (composer test): pass
  - `js_typecheck`: no command discovered
  - `js_build` (bun run build): pass
- Post-upgrade compare:
  - `php_tests`: pass
  - `js_build`: pass
  - No regressions vs baseline

### Side-effect: Playwright browser binaries

The `bun install` during reconcile triggered Playwright's "just installed/updated" guard, which made `pestphp/pest-plugin-browser` throw `PlaywrightOutdatedException` on the browser test. Resolved by running `npx playwright install chromium` once. Not a code regression — environmental.

## How to revert

- Drop a single feature commit:
  ```bash
  git revert <commit-sha>
  ```
- Discard the entire upgrade:
  ```bash
  git checkout main
  git branch -D starter-kit-upgrade/20260515-0948-react-multi-feature
  ```

## Notes / follow-up

- The upgrade branch is off `main`. To bring these changes into `acara-core` per CLAUDE.md's branch rules, merge `main` → `acara-core` after the upgrade branch lands on `main`.
- `bun install` left a fresh `bun.lock` and `node_modules`; both untracked.
- A dirty mode-only diff on `.ai/skills/starter-kit-upgrade/scripts/*.sh` (chmod +x to make them runnable) is intentionally NOT part of the upgrade commits. Apply with `chmod +x .ai/skills/starter-kit-upgrade/scripts/*.sh && git commit` separately if you want to keep them executable in git.
