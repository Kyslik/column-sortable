# CLAUDE.md — column-sortable

## Overview

Ubiquilife fork of Kyslik's column-sortable. Adds clickable column header sorting to Eloquent queries. Used by Appbase's QoModel to provide automatic table column sorting on every feature's index view.

## Namespace

`Kyslik\ColumnSortable`

## Key Classes

- **`Sortable`** — Trait for Eloquent models. Adds `scopeSortable()` which reads `sort` and `direction` from the request query string and applies `ORDER BY`. Supports sorting through `BelongsTo` and `HasOne` relationships.
- **`SortableLink`** — Blade helper to render sortable column headers with directional arrows. Usage: `@sortablelink('name', 'Display Name')`.
- **`ColumnSortableServiceProvider`** — Auto-discovered. Publishes config, registers Blade directive.
- **`Exceptions\ColumnSortableException`** — Thrown when an unsortable column is requested.

## Configuration

Sortable columns are defined per model via `$sortable` array property. Global config in `config/columnsortable.php`.

## Testing

```bash
cd column-sortable && vendor/bin/phpunit
```

## Mandatory Rules

- This is a PRIVATE Ubiquilife package. Changes affect ALL apps.
- NEVER change the `Sortable` trait API or query string parameter names (`sort`, `direction`) — every index view depends on these.
- NEVER change `SortableLink` output HTML without checking Appbase table templates.
- Use British spelling in all text and comments.
- Test before committing.
- One logical change per commit.
