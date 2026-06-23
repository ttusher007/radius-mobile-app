# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

A Laravel 13 (PHP 8.3+) application with a Livewire-first frontend. Key packages:

- **Livewire 4** (`livewire/livewire`) — primary UI layer for interactive components.
- **Flux UI 2** (`livewire/flux`) — first-party Livewire component library (`<flux:*>` components).
- **Blaze** (`livewire/blaze`) — Blade component render optimizer.
- **Tailwind CSS v4** — styling, built through Vite (`@tailwindcss/vite`), no `tailwind.config.js`.
- **SQLite** — default database (`DB_CONNECTION=sqlite`); tests run against `:memory:`.

This started from the `laravel/laravel` skeleton; most domain code is not yet written. The only route is `/` rendering `welcome.blade.php`, and the only model is `User`.

## Commands

```bash
# First-time setup (install deps, .env, key, migrate, build assets)
composer run setup

# Run everything for development at once (server + queue + logs + vite via concurrently)
composer run dev

# Run the full test suite (clears config first, then artisan test)
composer run test

# Run a single test file / method
php artisan test --filter=ExampleTest
php artisan test tests/Feature/ExampleTest.php

# Lint / format (Laravel Pint)
vendor/bin/pint            # apply fixes
vendor/bin/pint --test     # check only, no writes

# Build / watch frontend assets
npm run build
npm run dev
```

Note: `composer run dev` is the normal way to work — do not start `php artisan serve`, the queue, and Vite separately. Use `php artisan pail` for live log tailing on its own.

## Architecture notes

- **Routing & bootstrap:** Configured in `bootstrap/app.php` (Laravel 13 style — no `Kernel.php`). Web routes in `routes/web.php`, console routes/schedule in `routes/console.php`. A `/up` health check and JSON exception rendering for `api/*` requests are already wired.
- **Queues, cache, sessions** all default to the `database` driver (see `.env.example`). Running queued work requires the queue worker (included in `composer run dev`).
- **Frontend entry:** `resources/css/app.css` and `resources/js/app.js`, bundled by `vite.config.js`. Livewire/Flux components live under `resources/views/` (and `app/Livewire/` once created).

## Core requirements

These three rules apply to every change, without exception:

### 1. Security first
- Use Laravel's built-in protections: CSRF tokens on all forms, parameterized queries via Eloquent/Query Builder (never raw interpolated SQL), mass-assignment protection with `$fillable`/`$guarded` on every model.
- Authorize every action with Policies or Gates before executing it; never rely on UI-level hiding alone.
- Validate all input at the boundary using Form Requests; never trust `$request->all()`.
- Sanitize output in Blade (`{{ }}` not `{!! !!}` unless explicitly safe); avoid `eval`, `exec`, `shell_exec`, and `system` unless absolutely unavoidable.
- Store secrets in `.env` only, never in code or config files committed to source control.

### 2. Mobile-first UI
- Every view, Livewire component, and Flux component must be designed for small screens first, then enhanced for larger breakpoints.
- Use Tailwind responsive prefixes (`sm:`, `md:`, `lg:`) to progressively enhance — default styles target mobile.
- Touch targets must be at least 44×44 px; prefer full-width buttons on mobile, avoid hover-only interactions.
- Test layouts at 375 px wide (iPhone SE) as the baseline.

### 3. Optimized database queries
- Eager-load relationships with `with()` or `load()` to avoid N+1 queries.
- Select only needed columns (`select('id', 'name', ...)`) on large tables rather than `SELECT *`.
- Use pagination (`paginate()` / `simplePaginate()`) instead of `get()` on unbounded collections.
- Add database indexes on every foreign key and any column used in `WHERE`, `ORDER BY`, or `JOIN` clauses.
- Prefer `exists()` over `count() > 0`; prefer `firstWhere()` over `where()->first()`.

## Conventions

Boost is installed (`boost.json`) and registers skills for this project. When writing code, follow the corresponding skills:

- `laravel-best-practices` — Laravel/Eloquent PHP patterns.
- `livewire-development` — anything with `wire:*` directives or Livewire components.
- `fluxui-development` — building UI with `<flux:*>` components.
- `tailwindcss-development` — Tailwind v4 utility styling.
- `blaze-optimize` — optimizing Blade component rendering with `@blaze`.