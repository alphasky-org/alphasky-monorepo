# Changelog

All notable changes to the monorepo are documented in this file.

## [1.2.0] - 2026-10-29

- Add a reusable Arabic/English guided website interview with branching questions for informational sites, catalogues, stores and appointments.
- Produce reviewable requirements and stages; preserve relevant answers when editing and discard obsolete dependent choices.
- Keep this guided interview independent of paid AI generation and installation.
- Test five complete interview branches and language consistency.

## [1.1.0] - 2026-10-29

- Add server-side, per-site activation/library connection using a private configuration token, with identity allowlists and secret-free activation results.
- Add website design plan ownership, dependency, revision and approval validation; edits invalidate approval.
- Document the shared-wallet reservation and authoritative usage settlement contract. This foundation does not expose a designer endpoint or enable generation, billing or urgent execution.
- Add regression checks for cross-site/owner approval, stale revisions, modified plans and connection credential isolation.

## [1.0.5] - 2026-10-24

- Add the customer capability-request UI and authenticated server proxy for plugin development requests.
- Use the configured Alphasky server URL for local/production integration.
- Protect the monorepo updater from discarding modified or untracked site files.
- Support optional individual/company account types; sites can require the field with `API_REQUIRE_ACCOUNT_TYPE=true` without breaking existing clients.
- Document the mandatory canonical-source, VERSION bump, commit and GitHub push workflow in AGENTS.md.

## [2.0.0] - 2026-05-24

### Breaking changes

- This release contains backward-incompatible updates across packages.
- Existing projects may require code and configuration updates.
- You must review [UPGRADE.md](UPGRADE.md) before updating.

### Upgrade impact warning

If you update directly to this version without applying the migration notes,
your project can fail at runtime or during deployment.

### Added

- Root-level upgrade policy and migration checklist.

## [1.x]

- Previous stable release line.
