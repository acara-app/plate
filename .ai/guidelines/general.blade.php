# General Guidelines

- Don't include any superfluous PHP Annotations, except ones that start with `@` for typing variables.

# Laravel Wayfinder Guidelines

When you generate the routes, use `--with-form` so that `Wayfinder` can create form request classes for you, like so;

```shell
php artisan wayfinder:generate --with-form
```
