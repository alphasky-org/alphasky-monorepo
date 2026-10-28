# Website design foundations

`DesignPlan` validates ordered dependency steps, owner/site identity, revisions and approval fingerprints. It is a data-only component; it does not execute, persist, install or charge. Hosts must persist plans atomically and verify their current owner before each action. A revised plan invalidates approval. An ownership transfer invalidates the previous owner's approval.

`SiteConnection` calls existing activation and read/wait library operations using `ALPHASKY_SITE_TOKEN`, `alphasky.server_url`, `alphasky.api_key` and `app.url`. Credentials and domain never come from frontend payloads. Activation responses are reduced to status/remaining units and never return the echoed activation secret. TLS verification remains enabled and redirects disabled. Urgent/paid operations are deliberately not exposed.

This release does not expose a public endpoint, grant a new site token, start model calls or enable the designer UI. Site provisioning, adaptive planning, storage, execution and accounting integration must be completed before enabling the feature.

## Accounting contract

An activation token identifies a site; AI usage tokens are metered credit. Runure's wallet is the authoritative money balance, shared across all sites owned by that account. Do not independently assign each site the entire wallet balance.

Before any billable planning or build request: authenticate owner, snapshot the price version/provider/model and input/output limits, reserve a maximum amount atomically, and issue a short-lived authorization for that operation/site. AlphaSky must enforce that limit, record authoritative usage and return an authenticated settlement with a unique operation ID. Settle once, release the unused reservation, and retain unresolved reservations for reconciliation after timeouts. Never trust frontend usage, estimate a successful zero-cost request on transport failure, or retry an ambiguous billed generation blindly.

Prices must be configured per provider/model and input/output/cached tokens as applicable, with integer monetary subunits and deterministic rounding. Missing prices disable billing rather than assume a conversion. Failed calls may still incur provider usage; customer charging policy must be explicit. Legacy `token_count` alone cannot represent all model costs and must not authorize an unfunded operation.

All calls, including planning, refinement, JSON repair and generation, belong to the operation ledger. Urgent direct-code execution remains phase two. No wallet debits are introduced by this foundation release.
