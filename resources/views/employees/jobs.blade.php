@extends('layouts.ecommerce')

@section('title', 'Jobs')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="headline">Jobs</h1>
                <p class="subtle">Browse job titles from <code>JOBS</code> and review assigned employee totals.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('jobs.index') }}" class="field-grid" style="margin-top: 14px;">
            <div>
                <label for="q">Search Job Title</label>
                <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. MANAGER">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('jobs.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="grid grid-2">
            <article class="stat">
                <div class="label">Jobs</div>
                <div class="value">{{ number_format((int) $totalJobs) }}</div>
            </article>
            <article class="stat">
                <div class="label">Assigned Employees</div>
                <div class="value">{{ number_format((int) $assignedEmployees) }}</div>
            </article>
        </div>
    </section>

    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: center;">
                <div class="actions">
                    <button type="button" class="btn btn-primary" id="show-job-list">Job List</button>
                @if($canManageJobs)
                    <button type="button" class="btn btn-muted" id="show-job-add">Add Job</button>
                @endif
            </div>
            <span class="subtle">Use the buttons to switch between list and add forms.</span>
        </div>
    </section>

    <section class="card" id="job-list-panel">
        <div class="actions" style="justify-content: space-between;">
            <h2 style="margin-top: 0;">Job List</h2>
            <span class="chip">{{ $jobs->total() }} total</span>
        </div>

        <div class="table-wrap" style="margin-top: 12px;">
            <table>
                <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Job Title</th>
                    <th>Employees</th>
                </tr>
                </thead>
                <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td><strong>#{{ (int) $job->job_id }}</strong></td>
                        <td>{{ $job->job_title }}</td>
                        <td>{{ number_format((int) $job->employee_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="subtle">No jobs found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager" style="margin-top: 12px;">
            {{ $jobs->links('pagination.orbit') }}
        </div>
    </section>

    @if($canManageJobs)
    <section class="card" id="job-add-panel" style="display: none;">
        <h2 style="margin-top: 0;">Add Job</h2>
        <p class="subtle">Create a new job record.</p>

        <form method="POST" action="{{ route('jobs.store') }}" class="field-grid" style="margin-top: 12px;">
            @csrf
            <div>
                <label for="job_title">Job Title</label>
                <input id="job_title" name="job_title" type="text" value="{{ old('job_title') }}" maxlength="50" required>
            </div>
            <div>
                <label for="min_salary">Min Salary</label>
                <input id="min_salary" name="min_salary" type="number" step="0.01" min="0" value="{{ old('min_salary') }}">
            </div>
            <div>
                <label for="max_salary">Max Salary</label>
                <input id="max_salary" name="max_salary" type="number" step="0.01" min="0" value="{{ old('max_salary') }}">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listBtn = document.getElementById('show-job-list');
            const addBtn = document.getElementById('show-job-add');
            const listPanel = document.getElementById('job-list-panel');
            const addPanel = document.getElementById('job-add-panel');
            const openAddByError = @json(
                $errors->has('job_title') ||
                $errors->has('min_salary') ||
                $errors->has('max_salary')
            );

            if (!listBtn || !listPanel) return;

            const activate = (target) => {
                const showList = target === 'list';
                listPanel.style.display = showList ? '' : 'none';
                if (addPanel) addPanel.style.display = showList ? 'none' : '';
                listBtn.classList.toggle('btn-primary', showList);
                listBtn.classList.toggle('btn-muted', !showList);
                if (addBtn) {
                    addBtn.classList.toggle('btn-primary', !showList);
                    addBtn.classList.toggle('btn-muted', showList);
                }
            };

            if ((window.location.hash === '#add-job' || openAddByError) && addBtn) {
                activate('add');
            } else {
                activate('list');
            }

            listBtn.addEventListener('click', () => activate('list'));
            if (addBtn) addBtn.addEventListener('click', () => activate('add'));
        });
    </script>
@endsection
