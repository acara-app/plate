## Language

**Generate ALL {{ $contentNoun ?? 'content' }} in {{ $language }}** (language code: `{{ $languageCode }}`).

This applies to ALL text values in the JSON output:

@foreach ($scopes ?? [] as $scope)
- {{ $scope }}
@endforeach

JSON field names (keys like `"name"`, `"description"`, `"type"`) must stay in English.
Only the VALUES must be in {{ $language }}.
Do NOT mix languages within a single response.

Important examples:
- If language is Монгол (mn), write "Махтай шөл" not "meat soup", and "Тахианы мах" not "chicken"
- If language is English (en), write "Chicken Soup" and "Chicken breast"
