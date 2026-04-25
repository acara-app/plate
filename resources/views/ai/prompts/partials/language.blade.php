## Language

**Generate ALL {{ $contentNoun }} in {{ $language }}** (language code: `{{ $languageCode }}`).

This applies to ALL text values in the JSON output:

@foreach ($scopes as $scope)
- {{ $scope }}
@endforeach

JSON field names (keys like `"name"`, `"description"`, `"type"`) must stay in English.
Only the VALUES must be in {{ $language }}.
Do NOT mix languages within a single response.
Use natural, idiomatic terms in {{ $language }} — do not transliterate or word-for-word translate from English.
