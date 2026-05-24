# Alphasky Monorepo

This repository contains Alphasky packages in a single monorepo.

## Important upgrade warning

Major releases may include breaking changes that can require code updates in your project.

Before running Composer updates, always review:

- [CHANGELOG.md](CHANGELOG.md)
- [UPGRADE.md](UPGRADE.md)

Recommended safe update flow:

1. Read breaking changes first.
2. Test in staging before production.
3. Deploy only after integration tests pass.

## Included packages

- api
- assets
- data-synchronize
- dev-tool
- form-builder
- get-started
- git-commit-checker
- installer
- menu
- optimize
- page
- platform
- plugin-management
- revision
- seo-helper
- shortcode
- sitemap
- slug
- theme
- widget

## Notes

- Each package keeps its own `composer.json`.
- The `platform` directory contains the core modules used by `alphasky/platform`.
