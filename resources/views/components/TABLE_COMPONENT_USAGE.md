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

### Table with Card Title

```blade
<x-table 
    :headers="['#', 'Name', 'Email', 'Actions']"
    cardTitle="All Users">
    @foreach($users as $user)
        <x-table-row>
            <x-table-cell>{{ $user->id }}</x-table-cell>
            <x-table-cell>{{ $user->name }}</x-table-cell>
            <x-table-cell>{{ $user->email }}</x-table-cell>
            <x-table-cell align="center">
                <x-button-group-actions
                    viewHref="{{ route('users.show', $user->id) }}"
                    editHref="{{ route('users.edit', $user->id) }}"
                />
            </x-table-cell>
        </x-table-row>
    @endforeach
</x-table>
```

### Table with Custom Headers (Alignment)

```blade
<x-table :headers="[
    '#',
    'Name',
    'Email',
    ['label' => 'Actions', 'align' => 'center']
]">
    {{-- table rows --}}
</x-table>
```

### Table with Empty State

```blade
<x-table 
    :headers="['#', 'Name', 'Email']"
    cardTitle="Users"
    emptyMessage="No users found"
    emptyDescription="Get started by creating your first user."
    emptyActionHref="{{ route('users.create') }}"
    emptyActionText="Create User">
    @if($users->count() > 0)
        @foreach($users as $user)
            <x-table-row>
                <x-table-cell>{{ $user->id }}</x-table-cell>
                <x-table-cell>{{ $user->name }}</x-table-cell>
                <x-table-cell>{{ $user->email }}</x-table-cell>
            </x-table-row>
        @endforeach
    @endif
</x-table>
```

### Table with Cell Alignment

```blade
<x-table-row>
    <x-table-cell>{{ $item->id }}</x-table-cell>
    <x-table-cell>{{ $item->name }}</x-table-cell>
    <x-table-cell align="center">
        <span class="badge bg-success">{{ $item->status }}</span>
    </x-table-cell>
    <x-table-cell align="right">
        ${{ number_format($item->price, 2) }}
    </x-table-cell>
    <x-table-cell align="center">
        <x-button-group-actions ... />
    </x-table-cell>
</x-table-row>
```

### Table with Custom Styling

```blade
<x-table-row style="background-color: #f0f0f0;">
    <x-table-cell style="font-weight: bold;">Total</x-table-cell>
    <x-table-cell></x-table-cell>
    <x-table-cell align="right" style="font-weight: bold;">
        ${{ number_format($total, 2) }}
    </x-table-cell>
</x-table-row>
```

## Complete Example

```blade
@extends('layouts.tabler')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Users</h1>
            <p class="text-muted">Manage system users</p>
        </div>
        <x-button-add href="{{ route('users.create') }}" text="Add User" />
    </div>

    <x-table 
        :headers="['#', 'Name', 'Email', 'Role', ['label' => 'Actions', 'align' => 'center']]"
        cardTitle="All Users"
        emptyMessage="No users found"
        emptyDescription="Get started by creating your first user."
        emptyActionHref="{{ route('users.create') }}"
        emptyActionText="Create User">
        @if($users->count() > 0)
            @foreach($users as $user)
                <x-table-row>
                    <x-table-cell>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;">{{ $user->id }}</span>
                    </x-table-cell>
                    <x-table-cell>
                        <div style="font-weight: 500; font-size: 0.875rem;">{{ $user->name }}</div>
                    </x-table-cell>
                    <x-table-cell>{{ $user->email }}</x-table-cell>
                    <x-table-cell>
                        <span class="badge bg-primary">{{ $user->role }}</span>
                    </x-table-cell>
                    <x-table-cell align="center">
                        <x-button-group-actions
                            viewHref="{{ route('users.show', $user->id) }}"
                            editHref="{{ route('users.edit', $user->id) }}"
                            deleteAction="{{ route('users.destroy', $user->id) }}"
                        />
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
```

## Component Properties

### Table Component
- `headers`: Array of header labels or objects with `label`, `align`, `style`
- `emptyMessage`: Message shown when table is empty (default: 'No items found')
- `emptyDescription`: Description shown in empty state
- `emptyActionHref`: URL for action button in empty state
- `emptyActionText`: Text for action button in empty state
- `cardTitle`: Title shown in card header
- `cardHeaderActions`: Additional content for card header (buttons, etc.)
- `class`: Additional CSS classes for card
- `responsive`: Wrap table in responsive div (default: true)

### Table Row Component
- `class`: Additional CSS classes
- `style`: Inline styles

### Table Cell Component
- `align`: Text alignment ('left', 'center', 'right') - default: 'left'
- `class`: Additional CSS classes
- `style`: Inline styles
- `colspan`: Column span for merged cells

## Benefits

1. **Consistency**: All tables look the same across the application
2. **Maintainability**: Update table styles in one place
3. **Empty States**: Built-in empty state handling
4. **Responsive**: Automatic responsive wrapper
5. **Flexibility**: Support for custom styling when needed
6. **Accessibility**: Proper semantic HTML structure

