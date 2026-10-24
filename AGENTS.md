# Alphasky monorepo: mandatory source and release policy

These instructions apply to this repository and to its installed copies in any customer site, including Runure.

- The canonical source is `/Users/bassammac/htdocs/Alphasky-portal/alphasky-monorepo`; its remote is `https://github.com/alphasky-org/alphasky-monorepo`.
- Never leave a change only in a site's `vendor/alphasky` or another installed copy. Apply the same fix to the canonical repository during the same task.
- Site-specific behavior must be configurable or implemented in the site layer. Do not force one site's custom requirements on all customers; keep secrets and site configuration out of the shared repository.
- Before publishing, inspect the remote branch and preserve existing local changes. Resolve conflicts without discarding customer modifications.
- Every published change MUST increment the root `VERSION` using an appropriate semantic version, update `CHANGELOG.md`, and pass relevant checks. The settings updater reads the root VERSION on main.
- Commit the reviewed source changes and PUSH them to GitHub. A local edit or local commit alone does not complete the task. The owner explicitly requires the commit/push workflow for changes to this monorepo; do not ask for repeated approval for that workflow unless a separate blocking policy requires it.
- Verify the pushed commit is present on the remote and its VERSION is the expected value. Sync the intended installed copies and published assets, preserving local configuration, and report version, commit, tests and any remaining limitations.
- If push is blocked, report the exact blocker and that the release is unpublished. Never claim the update is available in Settings before the remote push succeeds.
- Do not use force-push or destructive resets to bypass conflicts. Do not silently enable automatic updates on sites with local changes.
