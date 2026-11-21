<?php

declare(strict_types=1);

use App\Actions\ApplyJsonPatch;

covers(ApplyJsonPatch::class);

it('applies patches the way the chat clients do', function (string $document, string $operation, string $expected): void {
    $result = (new ApplyJsonPatch)->handle(json_decode($document), json_decode($operation, flags: JSON_THROW_ON_ERROR));

    expect(json_encode($result, JSON_UNESCAPED_SLASHES))->toBe($expected);
})->with([
    'add creates missing parents' => ['{}', '{"op":"add","path":"/elements/card/props","value":{}}', '{"elements":{"card":{"props":{}}}}'],
    'add appends to an array' => ['{"items":[1]}', '{"op":"add","path":"/items/-","value":2}', '{"items":[1,2]}'],
    'add inserts at an index' => ['{"items":[1,3]}', '{"op":"add","path":"/items/1","value":2}', '{"items":[1,2,3]}'],
    'a numeric segment creates an array' => ['{}', '{"op":"add","path":"/rows/0","value":"a"}', '{"rows":["a"]}'],
    'escaped keys' => ['{}', '{"op":"add","path":"/a~1b/c~0d","value":1}', '{"a/b":{"c~d":1}}'],
    'replace overwrites a value' => ['{"items":["old"]}', '{"op":"replace","path":"/items/0","value":"new"}', '{"items":["new"]}'],
    'remove deletes an item' => ['{"a":{"b":[1,2]}}', '{"op":"remove","path":"/a/b/0"}', '{"a":{"b":[2]}}'],
    'remove deletes a key' => ['{"a":{"b":1,"c":2}}', '{"op":"remove","path":"/a/b"}', '{"a":{"c":2}}'],
    'remove ignores a missing key' => ['{"a":1}', '{"op":"remove","path":"/b"}', '{"a":1}'],
]);

it('rejects a patch it cannot apply', function (string $operation): void {
    expect(fn (): mixed => (new ApplyJsonPatch)->handle(json_decode('{"items":[]}'), json_decode($operation, flags: JSON_THROW_ON_ERROR)))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'an unsupported operation' => '{"op":"move","from":"/a","path":"/b"}',
    'a relative path' => '{"op":"add","path":"items","value":1}',
    'a missing value' => '{"op":"add","path":"/a"}',
    'an index out of range' => '{"op":"add","path":"/items/5","value":1}',
    'removing the whole document' => '{"op":"remove","path":""}',
]);
