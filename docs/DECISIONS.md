# Decision log

The canonical record of **why** this repository is the way it is: the reasoning and the **rejected alternatives** behind decisions that have closed. It is the guard against silently relitigating a settled decision.

**Consult it before acting.** Before proposing a change, reopening a question, or picking an approach, check here first: the path under consideration may already be a decided-and-recorded one, or an explicitly rejected one. If it is, follow the record (or make the case to revisit it); do not quietly re-decide.

**When a seam closes, record the verdict in both places.** Capture the *what*, *why* and rejected alternatives **here** (the cross-ticket index) **and** in the GitHub issue's `## Decision` section (the per-ticket record), keeping the two in sync. This file does not replace the issue tracker, which stays the system of record for a single ticket's status.

See the [`design-decision-forks`](../.claude/rules/design-decision-forks.md) rule for how a fork is surfaced and driven to resolution before its verdict lands here.

## How to add an entry

Oldest first, one entry per closed decision, so **a new entry is appended to the end of the file**. Keep it terse; the issue carries the depth. Each entry is preceded by exactly one blank line, which `scripts/check-decision-log-order.mjs` enforces along with the date order. The file is marked `merge=union` in `.gitattributes`, so two branches that each append an entry merge without a conflict.

```markdown
## YYYY-MM-DD: <short decision title> (<#issue or area>)

**Decision.** <what was decided, in a sentence or two>

**Why.** <the reasoning that settled it>

**Rejected.** <the alternatives considered, and why each lost>

**Companion.** <link to the GitHub issue holding the full reasoning>
```

---

## 2026-10-14: Started from the add-on template (repository setup)

**Decision.** This repository started from `UAMS-Web/statamic-addon-template`. It carries no synced files (`.agents/`, `.claude/`, `.cursor/`) and no `LICENSE`, and `composer.json` has no `license` field.

**Why.** The shared rules and skills reach a repository by onboarding it to the sync in `UAMS-Web/uams-claude-skills`, not by copying a snapshot that goes stale. The license waits on the general decision from UAMS legal in `UAMS-Web/uams-statamic#261`.

**Rejected.** Snapshotting the synced files into the template: every new add-on would begin with a stale copy that conflicts with its first sync pull request. Copying a license from an existing add-on: legal has not decided which license applies.

**Companion.** `UAMS-Web/uams-statamic#261`.

---

## 2026-11-18: Move the site enums here, name the add-on `uams-web/aether-enums`, and keep the add-on free of site classes

**Decision.** The add-on is `uams-web/aether-enums` in the `UAMSWeb\Aether\Enums` namespace. The twenty enums sit in `src/` under that namespace with their `Attributes`, and the nine generator commands move with them (`Console`, `Generation`, `Imports`). The generators write into the add-on's own `src/` (through `Generation\EnumPaths`) and read the bundled fixtures beside themselves, never a host's `app/` or `base_path()`. The site's `EntriesBySchemaType` cache warm becomes the `Generation\Schema\AliasCacheWarmer` contract, which a host binds and the add-on skips when it is unbound. `SpreadsheetImport` moves with the ISCO-08 generator. Enum values are pinned by `tests/Fixtures/site-enum-manifest.json`, built from the site's own files.

**Why.** UAMS-Web/uams-statamic#767 decided that one shared add-on holds the enums for the fieldtype add-on and the site. Nothing here may depend on a class in `App\`, so the two site classes the generators reached are either moved (`SpreadsheetImport`) or inverted into a contract (`EntriesBySchemaType`). A manifest taken from the site's files, rather than from the moved copies, is what shows the values did not change in the move.

**Rejected.** Moving `EntriesBySchemaType` here: it is a 400-line Statamic query scope that reads the site's entries, so the add-on would depend on the site's content model. Keeping `app_path()` and `base_path()` in the generators: they resolve inside whichever host runs them, so a generator run from a host's `vendor/` would write outside the add-on. A snapshot generated from the moved copies: it would pass whatever the move did to a value.

**Companion.** `UAMS-Web/aether-enums#5`, `UAMS-Web/uams-statamic#995` and `UAMS-Web/uams-statamic#767`.

## 2026-11-24: The `Language` code getters return `null` for an absent code (#66)

**Decision.** `Language::iso639_1()`, `iso639_2_b()`, `iso639_2_t()`, `iso639_3()` and `iso639_5()` return `?string` and return `null` for a case that has no such code, instead of throwing `ValueError`.

**Why.** `LanguageInfo` declares the five fields nullable, each getter docblock already says `@return ?string`, and `LanguageBCP47` and `Language::fieldValueForLanguageCode()` already return `null` for an absent value. At v0.2.1 the getters throw for 7,853, 7,552, 7,552, 113 and 7,924 of the 8,037 cases, so a caller has no way to read them without a `try/catch`. No caller in the repository or the organization's indexed code reads them, so widening the type breaks nothing known.

**Rejected.** An empty string, which keeps the `string` type but merges "no code" with data that is falsy and can reach a select list or a URL, and makes a later move to `null` a second break. Keeping the throw and documenting it, which leaves four of five getters unusable on most cases and needs a `try/catch` in every caller.

**Companion.** `UAMS-Web/aether-enums#66`, built in `UAMS-Web/aether-enums#70`.
