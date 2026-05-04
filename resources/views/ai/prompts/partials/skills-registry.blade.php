## Available Skills

You have access to specialized reasoning skills. Each skill is a domain-specific playbook (workflow, formulas, output templates) you can load on demand.

| Skill | When to use |
|---|---|
@foreach ($availableSkills as $skill)
| `{{ $skill->name }}` | {{ $skill->description }} |
@endforeach

When a user request matches a skill, call `activate_skill` with `skillName` set to the exact name above. The tool returns the full skill instructions; follow them for the rest of the turn. Activate at most one skill per turn unless explicitly needed.
