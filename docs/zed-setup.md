# Zed Editor Setup for Laravel Development

This guide covers setting up Zed as your primary editor for Laravel development.

## Prerequisites

- [Zed](https://zed.dev/) installed
- PHP installed via Homebrew or your preferred method
- Composer for PHP dependency management

## PHP Language Server

Zed supports Phpactor (default), Intelephense, and PHP Tools. We use **Phpactor** as it's open-source and integrates with PHPStan.

### Installation

```bash
# macOS
brew install phpactor

# Or via Composer
composer global require phpactor/phpactor
```

### Configuration

Add to `~/.config/zed/settings.json`:

```json
{
    "languages": {
        "PHP": {
            "language_servers": ["phpactor", "!intelephense", "!phptools"]
        }
    }
}
```

## PHP Formatting with Pint

Create a formatter script at `~/.local/bin/php-format`:

```bash
#!/bin/bash

TMP_FILE=$(mktemp /tmp/php-format-XXXXXX.php)
cat > "$TMP_FILE"

BUFFER_PATH="$1"
PROJECT_ROOT=$(dirname "$BUFFER_PATH")
while [[ "$PROJECT_ROOT" != "/" ]]; do
    if [[ -f "$PROJECT_ROOT/composer.json" ]]; then
        break
    fi
    PROJECT_ROOT=$(dirname "$PROJECT_ROOT")
done

if [[ -f "$PROJECT_ROOT/vendor/bin/pint" ]]; then
    "$PROJECT_ROOT/vendor/bin/pint" "$TMP_FILE" --quiet
elif [[ -f "$PROJECT_ROOT/vendor/bin/php-cs-fixer" ]]; then
    "$PROJECT_ROOT/vendor/bin/php-cs-fixer" fix "$TMP_FILE" --quiet
elif command -v pint &> /dev/null; then
    pint "$TMP_FILE" --quiet
fi

cat "$TMP_FILE"
rm "$TMP_FILE"
```

Make it executable:

```bash
chmod +x ~/.local/bin/php-format
```

### Configure Zed

Add to `~/.config/zed/settings.json`:

```json
{
    "languages": {
        "PHP": {
            "formatter": {
                "external": {
                    "command": "/Users/YOUR_USERNAME/.local/bin/php-format",
                    "arguments": ["{buffer_path}"]
                }
            }
        }
    }
}
```

## Tailwind CSS in Blade Files

Add to `~/.config/zed/settings.json`:

```json
{
    "languages": {
        "Blade": {
            "language_servers": ["tailwindcss-language-server", "..."]
        }
    },
    "lsp": {
        "tailwindcss-language-server": {
            "settings": {
                "includeLanguages": {
                    "blade": "html"
                }
            }
        }
    }
}
```

## Laravel IDE Helper

Install in your Laravel project for better autocomplete:

```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:models --nowrite
php artisan ide-helper:meta
```

Add generated files to `.gitignore`:

```
_ide_helper.php
_ide_helper_models.php
.phpstorm.meta.php
```

Re-run when adding new models or packages.

## Recommended Settings

Here's a complete `~/.config/zed/settings.json`:

```json
{
    "base_keymap": "VSCode",
    "theme": {
        "mode": "system",
        "light": "One Light",
        "dark": "One Dark"
    },
    "buffer_font_size": 17,
    "prettier": {
        "allowed": true
    },
    "inlay_hints": {
        "enabled": true
    },
    "sticky_scroll": {
        "enabled": true
    },
    "wrap_guides": [140],
    "languages": {
        "PHP": {
            "language_servers": ["phpactor", "!intelephense", "!phptools"],
            "formatter": {
                "external": {
                    "command": "/Users/YOUR_USERNAME/.local/bin/php-format",
                    "arguments": ["{buffer_path}"]
                }
            }
        },
        "Blade": {
            "language_servers": ["tailwindcss-language-server", "..."],
            "tab_size": 2
        }
    },
    "lsp": {
        "tailwindcss-language-server": {
            "settings": {
                "includeLanguages": {
                    "blade": "html"
                }
            }
        }
    }
}
```

Replace `/Users/YOUR_USERNAME/.local/bin/php-format` with your actual home path.

## Troubleshooting

- **Phpactor not starting**: Ensure it's in your PATH (`which phpactor`)
- **Formatting not working**: Check the script is executable and path is correct
- **Laravel facades not resolving**: Run `php artisan ide-helper:generate`
- **Restart Zed** after making config changes, or run `zed: reload` from command palette
