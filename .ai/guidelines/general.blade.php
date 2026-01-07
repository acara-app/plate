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

## Code Testability Guidelines

- **Avoid inline closures in controller responses** - When returning data from controllers (especially with Inertia), don't use inline closures for data transformation as they are difficult to test.
- **Extract formatting logic to models** - Instead of writing transformation closures in controllers, add dedicated methods to models (e.g., `toResponseData()`, `formattedItemsByCategory()`) that can be unit tested independently.
- **Use DTOs for response data** - Instead of inline array type declarations like `@return array{id: int, name: string}`, create dedicated Data Transfer Objects (DTOs) in `app/DataObjects/` using Spatie Laravel Data.
- **Keep controller methods thin** - Controllers should orchestrate, not transform. Move data formatting and business logic to models, actions, or dedicated service classes.

### Bad Example (untestable closure with inline array type):
```php
// In Controller - hard to test, inline type declaration
/**
 * @return array{id: int, name: string}
 */
return [
    'items' => $collection->map(fn ($item) => [
        'id' => $item->id,
        'name' => $item->name,
    ]),
];
```

### Good Example (testable method with DTO):
```php
// In DataObjects/ItemResponseData.php
final class ItemResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

// In Model
public function toResponseData(): ItemResponseData
{
    return new ItemResponseData(
        id: $this->id,
        name: $this->name,
    );
}

// In Controller
return [
    'items' => $collection->map(fn ($item) => $item->toResponseData()),
];
```

## When you want to use local model `scope`, use the Scope Attribute in Laravel 12.

<code-snippet name="Laravel 12 Local Model Scope Example" lang="php">
use Illuminate\Database\Eloquent\Attributes\Scope;
 
#[Scope]
protected function popular(Builder $query): void
{
    $query->where('votes', '>', 100);
}
</code-snippet>
