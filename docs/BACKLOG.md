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
