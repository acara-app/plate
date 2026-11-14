# Issue tracker: GitHub

Issues and feature specs for Plate live in the private
acara-app/acara-core repository.

Always target acara-app/acara-core explicitly. The local
Git remote points to Plate's code repository, not its tracker.

Use connected GitHub tools when available. For the `gh` CLI,
include `--repo acara-app/acara-core` on issue commands.

## Operations

- Create: `gh issue create --repo acara-app/acara-core --title "..." --body-file <file>`
- Read: `gh issue view <number> --repo acara-app/acara-core --json number,title,body,labels,comments`
- List: `gh issue list --repo acara-app/acara-core --state open`
- Update labels: `gh issue edit <number> --repo acara-app/acara-core --add-label "..."`
- Comment: `gh issue comment <number> --repo acara-app/acara-core --body-file <file>`
- Close: post the explanation, then close the issue.

Use native sub-issues and blocking dependencies when available.
Otherwise, record `Part of #<parent>` and `Blocked by: #<number>`
in the issue body. Work is unblocked when all blockers are closed.

"Publish to the issue tracker" means create an issue here.
"Fetch the relevant ticket" means read the issue here.

## Pull requests as a triage surface

PRs as a request surface: no.
