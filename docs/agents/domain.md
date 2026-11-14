# Domain docs

Use a single-context layout:

- `GLOSSARY.md` at the repository root.
- Architecture decision records under `docs/adr/`.

Before exploring an area, read the glossary and relevant ADRs.
If they do not exist, proceed silently. Create them only when
domain-modeling work resolves actual terminology or decisions.

Use the glossary's terminology consistently in specs, tickets,
code discussions, and tests. Surface conflicts with existing
ADRs explicitly rather than silently overriding them.

These documents describe Plate's public codebase. Keep private
acara-core implementation details in the private repository.
