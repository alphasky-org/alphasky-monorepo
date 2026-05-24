# Upgrade Guide

This document explains how to safely upgrade to major releases.

## Before you update

1. Create a backup and ensure rollback is possible.
2. Read all entries under `Breaking changes` in [CHANGELOG.md](CHANGELOG.md).
3. Update in a staging environment first.
4. Run full test suites and smoke tests.

## Composer strategy

Use explicit major constraints to avoid accidental breaking upgrades.

Example:

```json
{
  "require": {
    "alphasky/platform": "^1.0"
  }
}
```

Move to `^2.0` only after migration is complete.

## Recommended migration process

1. Update dependencies in a feature branch.
2. Apply package-level migration changes.
3. Resolve deprecations and removed APIs.
4. Run database migrations if required.
5. Validate admin, API, and frontend critical flows.
6. Deploy after verification only.

## Breaking-release announcement template

Use this text in release notes:

"This is a major release with breaking changes. Updating without following UPGRADE.md may break existing projects."
