---
name: cursor-development
description: "Guidelines for Cursor AI development in this project. Covers .cursor/rules integration, Cursor-specific features, and AI-assisted coding patterns for Laravel."
---

# Cursor Development Skill

This skill provides guidance for using Cursor AI within this project.

## Integration

Cursor reads project context from:

- `.cursor/rules/` — persistent project rules (`.mdc` files)
- `.cursor/skills/` — domain skills installed by Laravel Boost
- `CLAUDE.md` — shared project conventions and core requirements

## Best Practices for Cursor

- **Use Composer-based Tools:** Leverage the custom composer scripts defined in `composer.json` (e.g., `composer run test`, `composer run dev`).
- **Follow CLAUDE.md:** Always respect the core requirements and conventions outlined in `CLAUDE.md`.
- **Skill-Awareness:** Cursor should be aware of the other skills registered in `boost.json` and follow their specific rules (e.g., `laravel-best-practices`, `livewire-development`).

## Cursor Specifics

- **Multi-file Edits:** Cursor excels at multi-file edits; ensure you provide enough context for complex refactors.
- **Terminal Integration:** Use the integrated terminal to run Artisan commands and tests.
- **Rules Documentation:** Reference `.cursor/skills/*/SKILL.md` files when unsure about specific domain patterns.
