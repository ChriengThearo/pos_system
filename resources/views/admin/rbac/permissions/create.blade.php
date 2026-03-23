@extends('layouts.rbac')

@section('title', 'Create Permission')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Create Permission</h1>
        <a href="{{ route('admin.rbac.permissions.index') }}" class="btn btn-muted">Back to Permissions</a>
    </div>

    <section class="card">
        <form method="POST" action="{{ route('admin.rbac.permissions.store') }}" class="field-grid">
            @csrf

            <div>
                <label for="code">Code</label>
                <input id="code" name="code" type="text" value="{{ old('code') }}" maxlength="20" placeholder="e.g. user.read" required>
            </div>

            <div>
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="20" placeholder="Human-readable name" required>
            </div>

            <div>
                <label for="module">Module</label>
                <select id="module" name="module">
                    <option value="">Auto from code</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(old('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="20" placeholder="What does this allow?">{{ old('description') }}</textarea>
                <div class="muted">DB column limit is 20 characters.</div>
            </div>

            <div class="actions" style="margin-top: 8px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.rbac.permissions.index') }}" class="btn btn-muted">Cancel</a>
            </div>
        </form>
    </section>
@endsection
