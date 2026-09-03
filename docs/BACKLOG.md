# CRM — Backlog / Future Notes

Forward-looking requirements mentioned by the project owner that are **not yet scoped or approved** for any specific step. This is not an architecture decision log (see `DECISIONS.md`) and not the current phase's approved spec (see `PHASE-1-SPEC.md`) — just a place to keep track of things said in passing so they aren't lost before they're actually planned.

An entry here becomes real work only once it's turned into an approved step (with its own scope, architecture check, and — if applicable — a `DECISIONS.md` entry), following the project's usual one-step-at-a-time workflow.

---

## API module (mentioned 2026-09-03)

Project owner noted, while discussing Dashboard/UI work, that an API module will be needed later. Requirements as stated:

- Products: add, get, update a product; list products.
- Orders: add, get, update an order; list orders.
- Users: add, get, update a user.

Notes for whoever scopes this later:
- `ARCHITECTURE.md`'s "Protected decisions" section already anticipates this: `Web Controller → Service → Model` and `API Controller → Service → Model` are meant to share the same Services — the API layer should be additional controllers/routes calling the *existing* `UserService` etc., not parallel business logic.
- Products and Orders are Phase 2+ modules (Customers/Products/Orders — see `PHASE-1-SPEC.md`'s Future Expansion Contract); their own Web CRUD doesn't exist yet either, so the API can't be built before the underlying module exists.
- `laravel/sanctum` (or similar) is not installed — per `DECISIONS.md` #10, that's deliberate until there's an actual API consumer. This note is the first sign one may be coming; installing it is still a decision to make explicitly when this is actually scoped, not something to add speculatively now.

---

## Sources / Integrations module (mentioned 2026-09-03, while scoping Customers)

While defining Customers' fields, the project owner clarified that "источник" (source) does **not** belong on Customers — it belongs on Orders, and implies a whole future module:

- Current order sources: Prom, Rozetka, Maudau, OLX, Epicentr, Kasta, Umall (marketplaces).
- A future "Sources" (Источники) module will hold each marketplace's API keys/credentials and handle **pulling orders in** from each of them via their respective APIs.
- Each Order will reference its source (which marketplace it came from).

This is the "Integrations" module already named in `PHASE-1-SPEC.md`'s Future Expansion Contract — this note is the first concrete shape it's taken (marketplace order-ingestion, not a generic integration framework). Whoever scopes it later should also decide then whether "source" becomes a managed lookup table or a fixed set tied to which marketplace integrations actually exist (probably the latter, since each source needs real API credentials behind it — an arbitrary free-text "source" wouldn't have anything to pull from).
