# CRM — Backlog / Future Notes

Forward-looking requirements mentioned by the project owner that are **not yet scoped or approved** for any specific step. This is not an architecture decision log (see `DECISIONS.md`) and not the current phase's approved spec (see `PHASE-1-SPEC.md`) — just a place to keep track of things said in passing so they aren't lost before they're actually planned.

An entry here becomes real work only once it's turned into an approved step (with its own scope, architecture check, and — if applicable — a `DECISIONS.md` entry), following the project's usual one-step-at-a-time workflow.

---

## API module (mentioned 2026-09-03)

Project owner noted, while discussing Dashboard/UI work, that an API module will be needed later. Requirements as stated:

- Products: add, get, update a product; list products. **Done — see `docs/DECISIONS.md` #13 and `IMPLEMENTATION-LOG.md` Step 18.** `GET/POST /api/products`, `GET/PUT /api/products/{id}`, Sanctum token auth, same `can:products.*` permissions as the web UI.
- Orders: add, get, update an order; list orders. **Not started.**
- Users: add, get, update a user. **Not started.**

Notes for whoever scopes the remaining pieces:
- Follow the pattern Products just established: an `app/Modules/<Name>/Controllers/Api/...Controller.php` + `app/Modules/<Name>/Resources/...Resource.php` + `app/Modules/<Name>/api-routes.php` (auto-discovered by `routes/api.php`), reusing the module's existing web `Service` and `Request` classes rather than duplicating validation/business logic.
- `laravel/sanctum` is now installed (`DECISIONS.md` #13) — no need to re-decide that part. Issue tokens via `php artisan api-token:create {email} [name]`.
- Orders' API will need to decide how nested `items` (product_id/quantity) are represented in the JSON payload — the web form's `items[n][product_id]` shape doesn't need to carry over verbatim to a JSON API; a plain `items: [{product_id, quantity}]` array is more natural for a JSON client.

---

## Sources / Integrations module (mentioned 2026-09-03, while scoping Customers)

While defining Customers' fields, the project owner clarified that "источник" (source) does **not** belong on Customers — it belongs on Orders, and implies a whole future module:

- Current order sources: Prom, Rozetka, Maudau, OLX, Epicentr, Kasta, Umall (marketplaces).
- A future "Sources" (Источники) module will hold each marketplace's API keys/credentials and handle **pulling orders in** from each of them via their respective APIs.
- Each Order will reference its source (which marketplace it came from).

This is the "Integrations" module already named in `PHASE-1-SPEC.md`'s Future Expansion Contract — this note is the first concrete shape it's taken (marketplace order-ingestion, not a generic integration framework). Whoever scopes it later should also decide then whether "source" becomes a managed lookup table or a fixed set tied to which marketplace integrations actually exist (probably the latter, since each source needs real API credentials behind it — an arbitrary free-text "source" wouldn't have anything to pull from).
