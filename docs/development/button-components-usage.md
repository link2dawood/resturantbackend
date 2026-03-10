# Button Components Usage Guide

This document explains how to use the reusable button components across all pages.

## Available Components

1. **button-add** - Add/Create button
2. **button-edit** - Edit button
3. **button-delete** - Delete button
4. **button-view** - View/Show button
5. **button-search** - Search button
6. **button-group-actions** - Group of action buttons (View, Edit, Delete)

## Usage Examples

### Add / Edit / View / Search

```blade
<x-button-add href="{{ route('items.create') }}" />
<x-button-add href="{{ route('items.create') }}" text="Add New Item" size="sm" :icon="false" />
<x-button-edit href="{{ route('items.edit', $item->id) }}" iconOnly="true" />
<x-button-view href="{{ route('items.show', $item->id) }}" text="View Details" />
<x-button-search type="submit" />
```

### Delete (prefer action + confirmMessage)

```blade
<x-button-delete action="{{ route('items.destroy', $item->id) }}" iconOnly="true"
    confirmMessage="Are you sure you want to delete this item?" method="DELETE" />
```

### Button Group (table actions)

```blade
<x-button-group-actions
    viewHref="{{ route('items.show', $item->id) }}"
    editHref="{{ route('items.edit', $item->id) }}"
    deleteAction="{{ route('items.destroy', $item->id) }}"
    deleteConfirm="Are you sure?"
/>
<!-- Without delete: showDelete="false" -->
```

## Component Properties

- **Common:** `size` (sm|default|lg), `class`, `icon`, `iconOnly`
- **Add:** `href`, `text`
- **Edit/View:** `href`, `text`, `title`
- **Delete:** `href`|`action`, `text`, `confirmMessage`, `method`
- **Button group:** `viewHref`, `editHref`, `deleteAction`, `deleteConfirm`, `showView`, `showEdit`, `showDelete`, `size`

Component files: `resources/views/components/`.
