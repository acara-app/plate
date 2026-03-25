---
name: spatie-laravel-php-standards
description: Apply Spatie's Laravel and PHP coding standards for any task that creates, edits, reviews, refactors, or formats Laravel/PHP code or Blade templates; use for controllers, Eloquent models, routes, config, validation, migrations, tests, and related files to align with Laravel conventions and PSR-12.
license: MIT
metadata:
  author: Spatie
---

# Spatie Laravel & PHP Guidelines

## Overview

Apply Spatie's Laravel and PHP guidelines to keep code style consistent and Laravel-native.

## When to Activate

- Activate this skill for any Laravel or PHP coding work, even if the user does not explicitly mention Spatie.
- Activate this skill when asked to generate, edit, format, refactor, review, or align Laravel/PHP code.
- Activate this skill when working on `.php` or `.blade.php` files, routes, controllers, models, config, validation, migrations, or tests.

## Scope

- In scope: `.php`, `.blade.php`, Laravel conventions (routes, controllers, config, validation, migrations, tests).
- Out of scope: JS/TS, CSS, infrastructure, database schema design, non-Laravel frameworks.

## Workflow

1. Identify the artifact (controller, route, config, model, Blade, test, etc.).
2. Read `references/spatie-laravel-php-guidelines.md` and focus on the relevant sections.
3. Apply the core Laravel principle first, then PHP standards, then section-specific rules.
4. If a rule conflicts with existing project conventions, follow Laravel conventions and keep changes consistent.

## Core Rules (Summary)

- Follow Laravel conventions first.
- Follow PSR-1, PSR-2, and PSR-12.
- Prefer typed properties and explicit return types (including `void`).
- Use short nullable syntax like `?string`.
- Prefer early returns and avoid `else` when possible.
- Always use curly braces for control structures.
- Use string interpolation over concatenation.

## Do and Don't

Do:
- Use kebab-case URLs, camelCase route names, and camelCase route parameters.
- Use array notation for validation rules.
- Use `config()` and avoid `env()` outside config files.

Don't:
- Add docblocks when full type hints already exist.
- Use fully qualified classnames in docblocks.
- Use `@lang` instead of `__()`.

## Examples

```php
if (! $user) {
    return null;
}

if (! $user->isActive()) {
    return null;
}

$name = $isFoo ? 'foo' : 'bar';
```

```blade
@if($condition)
    Something
@endif
```

## References

- `references/spatie-laravel-php-guidelines.md`