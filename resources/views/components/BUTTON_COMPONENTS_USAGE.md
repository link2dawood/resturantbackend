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

### 1. Add Button

```blade
{{-- Simple add button --}}
<x-button-add href="{{ route('items.create') }}" />

{{-- Add button with custom text --}}
<x-button-add href="{{ route('items.create') }}" text="Add New Item" />

{{-- Small add button --}}
<x-button-add href="{{ route('items.create') }}" text="Add" size="sm" />

{{-- Add button without icon --}}
<x-button-add href="{{ route('items.create') }}" :icon="false" />
```

### 2. Edit Button

```blade
{{-- Simple edit button (icon only) --}}
<x-button-edit href="{{ route('items.edit', $item->id)" iconOnly="true" />

{{-- Edit button with text --}}
<x-button-edit href="{{ route('items.edit', $item->id)" text="Edit Item" />

{{-- Large edit button --}}
<x-button-edit href="{{ route('items.edit', $item->id)" size="lg" />
```

### 3. Delete Button

```blade
{{-- Delete button with form action (recommended) --}}
<x-button-delete 
    action="{{ route('items.destroy', $item->id)" 
    iconOnly="true"
    confirmMessage="Are you sure you want to delete this item?"
/>

{{-- Delete button with href --}}
<x-button-delete 
    href="{{ route('items.destroy', $item->id)" 
    text="Delete"
/>

{{-- Delete with custom confirmation --}}
<x-button-delete 
    action="{{ route('items.destroy', $item->id)" 
    confirmMessage="This will permanently delete the item. Continue?"
    method="DELETE"
/>
```

### 4. View Button

```blade
{{-- Simple view button (icon only) --}}
<x-button-view href="{{ route('items.show', $item->id)" iconOnly="true" />

{{-- View button with text --}}
<x-button-view href="{{ route('items.show', $item->id)" text="View Details" />
```

### 5. Search Button

```blade
{{-- Search button (submit type) --}}
<x-button-search type="submit" />

{{-- Search button (button type) --}}
<x-button-search type="button" text="Search Items" />
```

### 6. Button Group (Recommended for Table Actions)

```blade
{{-- Complete action group --}}
<x-button-group-actions
    viewHref="{{ route('items.show', $item->id)"
    editHref="{{ route('items.edit', $item->id)"
    deleteAction="{{ route('items.destroy', $item->id)"
    deleteConfirm="Are you sure you want to delete this item?"
/>

{{-- Without delete button --}}
<x-button-group-actions
    viewHref="{{ route('items.show', $item->id)"
    editHref="{{ route('items.edit', $item->id)"
    showDelete="false"
/>

{{-- Only view and edit --}}
<x-button-group-actions
    viewHref="{{ route('items.show', $item->id)"
    editHref="{{ route('items.edit', $item->id)"
    showView="true"
    showEdit="true"
    showDelete="false"
/>
```

## Table Example

```blade
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>
                <x-button-group-actions
                    viewHref="{{ route('items.show', $item->id)"
                    editHref="{{ route('items.edit', $item->id)"
                    deleteAction="{{ route('items.destroy', $item->id)"
                />
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

## Page Header Example

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>Items</h1>
        <p class="text-muted">Manage your items</p>
    </div>
    <x-button-add href="{{ route('items.create') }}" text="Add Item" />
</div>
```

## Component Properties

### Common Properties (All Buttons)
- `size`: 'sm', 'default', 'lg' (default: 'sm' for icon buttons, 'default' for text buttons)
- `class`: Additional CSS classes
- `icon`: true/false (default: true)
- `iconOnly`: true/false (default: false) - Shows only icon, no text

### Add Button
- `href`: URL for the add/create page
- `text`: Button text (default: 'Add')

### Edit Button
- `href`: URL for the edit page
- `text`: Button text (default: 'Edit')
- `title`: Tooltip text (default: 'Edit')

### Delete Button
- `href`: URL for delete (if using GET)
- `action`: Form action URL (recommended for DELETE method)
- `text`: Button text (default: 'Delete')
- `title`: Tooltip text (default: 'Delete')
- `confirmMessage`: Confirmation dialog message
- `method`: HTTP method (default: 'DELETE')

### View Button
- `href`: URL for the view/show page
- `text`: Button text (default: 'View')
- `title`: Tooltip text (default: 'View Details')

### Search Button
- `text`: Button text (default: 'Search')
- `type`: 'button' or 'submit' (default: 'button')

### Button Group
- `viewHref`: URL for view action
- `editHref`: URL for edit action
- `deleteAction`: Form action URL for delete
- `deleteConfirm`: Confirmation message
- `deleteMethod`: HTTP method (default: 'DELETE')
- `showView`: Show view button (default: true)
- `showEdit`: Show edit button (default: true)
- `showDelete`: Show delete button (default: true)
- `size`: Button size (default: 'sm')

## Benefits

1. **Consistency**: All buttons look and behave the same across the application
2. **Maintainability**: Update button styles in one place
3. **Reusability**: Easy to use anywhere with simple syntax
4. **Flexibility**: Many customization options
5. **Accessibility**: Proper titles and ARIA attributes

