#!/bin/bash
set -euo pipefail

REPO="acara-app/plate"
BRANCH="main"

for cmd in git gh bun rg; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Error: '$cmd' is required but not installed." >&2
        exit 1
    fi
done

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo "Error: must be on $BRANCH branch (current: $CURRENT_BRANCH)" >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Error: working tree is not clean. Commit or stash changes before releasing." >&2
    git status --porcelain
    exit 1
fi

git pull --ff-only

SAFETY_FILES=(composer.json)
[ -f composer.lock ] && SAFETY_FILES+=(composer.lock)

if rg -q 'acara-app/acara-core|Acara\\AcaraCore' "${SAFETY_FILES[@]}"; then
    echo "Error: private package leaked into $BRANCH:" >&2
    rg -n 'acara-app/acara-core|Acara\\AcaraCore' "${SAFETY_FILES[@]}" >&2
    echo "" >&2
    echo "$BRANCH must never reference acara-app/acara-core. See .ai/acara-core in CLAUDE.md." >&2
    exit 1
fi

CURRENT_TAG=$(git describe --tags --abbrev=0 --match 'v*' 2>/dev/null || echo "v0.0.0")
CURRENT_VERSION="${CURRENT_TAG#v}"

echo ""
echo "Current version: $CURRENT_TAG"
echo ""

echo "Select version bump type:"
echo "1) patch (bug fixes)"
echo "2) minor (new features)"
echo "3) major (breaking changes)"
echo

read -p "Enter your choice (1-3): " choice

case $choice in
    1) RELEASE_TYPE="patch" ;;
    2) RELEASE_TYPE="minor" ;;
    3) RELEASE_TYPE="major" ;;
    *)
        echo "Invalid choice. Exiting." >&2
        exit 1
        ;;
esac

if ! [[ "$CURRENT_VERSION" =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
    echo "Error: latest tag '$CURRENT_TAG' is not a clean vMAJOR.MINOR.PATCH semver." >&2
    exit 1
fi

MAJOR="${BASH_REMATCH[1]}"
MINOR="${BASH_REMATCH[2]}"
PATCH="${BASH_REMATCH[3]}"

case "$RELEASE_TYPE" in
    patch) PATCH=$((PATCH + 1)) ;;
    minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
    major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
TAG="v${NEW_VERSION}"

echo ""
echo "Releasing $TAG..."
echo ""

echo "Installing frontend dependencies..."
bun install --frozen-lockfile
echo ""

echo "Verifying build..."
bun run build
echo ""

git tag -a "$TAG" -m "$TAG"
git push origin "$BRANCH"
git push origin "$TAG"

gh release create "$TAG" --generate-notes

echo ""
echo "Release $TAG completed successfully."
echo "https://github.com/$REPO/releases/tag/$TAG"
