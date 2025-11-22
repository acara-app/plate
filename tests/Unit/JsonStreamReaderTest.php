<?php

declare(strict_types=1);

use App\Services\JsonStreamReader;

function createJsonFile(string $content): string
{
    $path = sys_get_temp_dir().'/'.uniqid('test_', true).'.json';
    file_put_contents($path, $content);

    return $path;
}

it('returns empty generator when file does not exist', function (): void {
    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream('/nonexistent/file.json'));

    expect($results)->toBeEmpty();
});

it('returns empty generator when file cannot be opened', function (): void {
    $path = sys_get_temp_dir().'/'.uniqid('test_', true).'.json';

    // Create a file
    file_put_contents($path, '{"data": []}');

    // Make it unreadable (note: this might not work on all systems)
    chmod($path, 0000);

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toBeEmpty();

    // Cleanup
    chmod($path, 0644);
    @unlink($path);
});

it('streams valid json objects from file', function (): void {
    $path = createJsonFile('{"FoundationFoods": [
{"id": 1, "name": "Apple"},
{"id": 2, "name": "Banana"},
{"id": 3, "name": "Cherry"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(3)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple'])
        ->and($results[1])->toBe(['id' => 2, 'name' => 'Banana'])
        ->and($results[2])->toBe(['id' => 3, 'name' => 'Cherry']);

    unlink($path);
});

it('handles trailing commas in json lines', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "value": "first"},
{"id": 2, "value": "second"},
{"id": 3, "value": "third"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(3)
        ->and($results[0])->toBe(['id' => 1, 'value' => 'first'])
        ->and($results[1])->toBe(['id' => 2, 'value' => 'second'])
        ->and($results[2])->toBe(['id' => 3, 'value' => 'third']);

    unlink($path);
});

it('stops reading at closing bracket with brace', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "name": "Apple"}
]}
{"should": "not be read"}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple']);

    unlink($path);
});

it('stops reading at closing bracket without brace', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "name": "Apple"}
]
{"should": "not be read"}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple']);

    unlink($path);
});

it('skips empty lines', function (): void {
    $path = createJsonFile('{"data": [

{"id": 1, "name": "Apple"},

{"id": 2, "name": "Banana"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple'])
        ->and($results[1])->toBe(['id' => 2, 'name' => 'Banana']);

    unlink($path);
});

it('skips lines that are just brackets or braces', function (): void {
    $path = createJsonFile('{"data": [
[
{
{"id": 1, "name": "Apple"},
{"id": 2, "name": "Banana"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple'])
        ->and($results[1])->toBe(['id' => 2, 'name' => 'Banana']);

    unlink($path);
});

it('skips lines with string zero', function (): void {
    $path = createJsonFile('{"data": [
0
{"id": 1, "name": "Apple"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple']);

    unlink($path);
});

it('skips invalid json lines', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "name": "Apple"},
this is not json,
{"id": 2, "name": "Banana"},
{invalid: json},
{"id": 3, "name": "Cherry"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(3)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple'])
        ->and($results[1])->toBe(['id' => 2, 'name' => 'Banana'])
        ->and($results[2])->toBe(['id' => 3, 'name' => 'Cherry']);

    unlink($path);
});

it('skips json that decodes to non-array values', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "name": "Apple"},
"string value",
123,
true,
null,
{"id": 2, "name": "Banana"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBe(['id' => 1, 'name' => 'Apple'])
        ->and($results[1])->toBe(['id' => 2, 'name' => 'Banana']);

    unlink($path);
});

it('handles complex nested json objects', function (): void {
    $path = createJsonFile('{"data": [
{"id": 1, "meta": {"tags": ["fruit", "red"], "count": 5}},
{"id": 2, "meta": {"tags": ["fruit", "yellow"], "count": 10}}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBe(['id' => 1, 'meta' => ['tags' => ['fruit', 'red'], 'count' => 5]])
        ->and($results[1])->toBe(['id' => 2, 'meta' => ['tags' => ['fruit', 'yellow'], 'count' => 10]]);

    unlink($path);
});

it('handles unicode and multibyte characters', function (): void {
    $path = createJsonFile('{"data": [
{"name": "Äpfel", "emoji": "🍎"},
{"name": "улаанбаатар", "emoji": "🍌"}
]}');

    $reader = new JsonStreamReader();
    $results = iterator_to_array($reader->stream($path));

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBe(['name' => 'Äpfel', 'emoji' => '🍎'])
        ->and($results[1])->toBe(['name' => 'улаанбаатар', 'emoji' => '🍌']);

    unlink($path);
});
