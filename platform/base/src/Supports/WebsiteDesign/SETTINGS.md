# Website settings JSON actions

Runure authenticates the member and verifies website ownership before the tenant bridge runs. The website's monorepo client supplies the settings inventory to AlphaSky. The model receives field names and validation constraints only, not current private values. AlphaSky stores the proposal in the existing central conversation/operation tables and returns a `settings_plan` artifact. The tenant installs official dependency packages first, boots a fresh PHP process, applies native validation, writes only requested keys, verifies the result and reports completion.

## Owner API

`POST /api/v1/websites/control` with authenticated member, `website_id`, and `action: "settings_catalog"` returns the real tenant registry. Writes run asynchronously through the existing design chat/build API (`/api/v1/website-design` on AlphaSky); no public unauthenticated settings execution endpoint is introduced.

## JSON examples

```json
{"role":"settings","kind":"update_content","requires_confirmation":false,"instruction":"Use Arabic for the admin dashboard","settings":{"version":1,"action":"settings.update","group":"admin-appearance","values":{"admin_appearance_locale":"ar","admin_appearance_locale_direction":"rtl"}}}
```

The server derives `language` and `language-advanced` dependencies. The catalog installer checks installed/active packages and preserves existing plugins. This changes the admin default language; it does not translate website pages or override a user's own admin-language preference.

```json
{"role":"settings","kind":"update_content","requires_confirmation":false,"instruction":"Create a database and uploaded-media backup","settings":{"version":1,"action":"backup.create","name":"Owner backup","include_media":true}}
```

The server derives the `backup` dependency. Success requires actual database/media archive files. This is a database/media backup, not a complete source/server backup. Restore and deletion are not automatic actions.

## Registry and limits

Built-in groups: general, admin-appearance, cache, datatables, phone-number, media presentation/processing, email sender identity. Each uses its existing CMS FormRequest rules. General and email changes require explicit confirmation. Current values are captured centrally as `expected`; stale writes fail. UUID receipts prevent a repeated operation from creating another backup. An interrupted applying receipt requires reconciliation instead of blind retry.

Additional reviewed plugins register groups through `website_design_settings_registry` with a FormRequest class and explicit field allowlist. Settings absent from this registry—including storage credentials, SMTP secrets, executable custom CSS/JS, template code, destructive maintenance and plugin-specific settings without adapters—must go to capability review. Do not claim every CMS setting is currently executable. Never pass arbitrary setting keys, controllers, shell commands or PHP supplied by the model.

Release 1.9.0. Source deployment must advance Git to the release rather than copying files over an older checkout.
