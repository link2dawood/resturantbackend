# Table Component Usage Guide

This document explains how to use the unified table component across all pages.

## Available Components

1. **table** - Main table wrapper with card, headers, and empty state
2. **table-row** - Table row component
3. **table-cell** - Table cell component

## Basic Usage

### Simple Table

```blade
<x-table :headers="['#', 'Name', 'Email', 'Actions']">
    @foreach($users as $user)
        <x-table-row>
            <x-table-cell>{{ $user->id }}</x-table-cell>
            <x-table-cell>{{ $user->name }}</x-table-cell>
            <x-table-cell>{{ $user->email }}</x-table-cell>
            <x-table-cell align="center">
                <x-button-group-actions
                    viewHref="{{ route('users.show', $user->id) }}"
                    editHref="{{ route('users.edit', $user->id) }}"
                    deleteAction="{{ route('users.destroy', $user->id) }}"
                />
            </x-table-cell>
        </x-table-row>
    @endforeach
</x-table>
```

### Table with Card Title, Empty State, Custom Headers

- `cardTitle`, `emptyMessage`, `emptyDescription`, `emptyActionHref`, `emptyActionText`
- Headers can be strings or `['label' => 'Actions', 'align' => 'center']`
- `x-table-cell` supports `align="center"|"right"`, `colspan`, `class`, `style`

## Component Properties

### Table
- `headers`, `emptyMessage`, `emptyDescription`, `emptyActionHref`, `emptyActionText`, `cardTitle`, `cardHeaderActions`, `class`, `responsive`

### Table Row
- `class`, `style`

### Table Cell
- `align` (left|center|right), `class`, `style`, `colspan`

## Benefits

Consistency, maintainability, built-in empty states, responsive wrapper, accessibility. Component files live in `resources/views/components/`.
