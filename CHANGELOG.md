# 1.4.0

- Install authenticated AlphaSky catalog dependencies before generated modules, including standalone plugin activation. Verify hashes, manifests, core compatibility, dependency order and active state; preserve existing plugins and stop ambiguous partial migrations.

# Changelog

## 1.3.7 — 2026-10-30

- Clear tenant compiled views and application content caches after publishing a generated module/homepage, before verifying the public result. A failed cache refresh prevents a success report.

## 1.3.6 — 2026-10-30

- Verify the actual rendered homepage revision and requested Google map/layout before reporting a design change as installed. Reject stale pages and missing maps.
- Request a fresh page after installation while retaining compatibility with historical design artifacts.
- Add regression checks for stale HTML, missing/foreign map frames, wrong layout and comment-only markers.

## 1.3.5 — 2026-10-29

- Read Copilot enablement and owner ID from cached configuration; enabling Copilot no longer enables production debugging.
- Managed sites use their server-held activation token and configured site identity for the plugin marketplace and Copilot. No token is exposed to browser storage.
- Preserve manual activation for sites without a managed connection.
- Regression coverage for managed marketplace identity and legacy activation.

## 1.3.4 — 2026-10-29

- Add an opt-in command for Runure-managed hourly package updates and show managed-update status in Settings.
- Serialize updates, preserve a previous Git reference, use fast-forward-only updates, and never delete untracked files. Report local changes and permissions accurately.
- Managed provisioning must preserve a clean public monorepo checkout; legacy non-Git copies require a verified migration before automatic updates.

## 1.3.3 — 2026-10-29

- Link the locally cached font stylesheet by default instead of embedding every font weight and Unicode subset in each HTML response. Preserve explicit inline rendering and provider fallback.


## 1.3.2 — 2026-10-29

- Update already active design modules without attempting to activate them again; keep migrations and asset publishing.


## 1.3.1 — 2026-10-29

- Create the plugin directory safely on fresh CMS sites before installing a design module.
- Preserve existing plugin files when backup or move fails, and mark failed install receipts for reconciliation.

# 1.3.0 — 2026-10-29

- Add a server-side website design client for central conversations, metered operation admission, immutable module packages and deployment reporting.
- Verify package hashes and archive paths, retain prior module files, and prevent blind replay of interrupted installation.
- Add homepage publication from generated shortcodes and validated logo/color updates. Credentials remain server-side; callers must verify site ownership before generation and again before installation.
- Include credential isolation, TLS and package validation tests. The hosting application supplies a trusted job broker and the central design API; this release does not expose an unauthenticated browser endpoint.

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
