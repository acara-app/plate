# General Guidelines

- Don't include any superfluous PHP Annotations, except ones that start with `@` for typing variables.

# Laravel Wayfinder Guidelines

When you generate the routes, use `--with-form` so that `Wayfinder` can create form request classes for you, like so;

```shell
php artisan wayfinder:generate --with-form
```

## Approach for writing tests

 - Use a cleaner, more testable approach. Separate edge cases into their own methods or classes.
 - Use data providers to test multiple scenarios in a single test method.
