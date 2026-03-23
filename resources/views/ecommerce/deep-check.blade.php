@extends('layouts.ecommerce')

@section('title', 'Oracle Deep Check')

@section('content')
    <section class="card">
        <h1 class="headline">Oracle User Deep Check</h1>
        <p class="subtle">
            Connected schema: <strong>C##website_v1</strong>. This page reads Oracle dictionary views:
            <code>USER_TABLES</code>, <code>USER_VIEWS</code>, <code>USER_TRIGGERS</code>, and
            <code>USER_TAB_IDENTITY_COLS</code>.
        </p>
    </section>

    <section class="card">
        <h2 style="margin-top: 0;">Tables</h2>
        <div class="table-wrap" style="margin-top: 10px;">
            <table>
                <thead>
                <tr>
                    <th>Table</th>
                    <th style="text-align: right;">NUM_ROWS</th>
                    <th style="text-align: right;">Columns</th>
                    <th>Last Analyzed</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tables as $table)
                    <tr>
                        <td><strong>{{ $table->table_name }}</strong></td>
                        <td style="text-align: right;">{{ $table->num_rows !== null ? number_format((float) $table->num_rows) : 'N/A' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $table->column_count) }}</td>
                        <td>{{ $table->last_analyzed ?: 'N/A' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid grid-2">
        <section class="card">
            <h2 style="margin-top: 0;">Views</h2>
            <div class="grid" style="margin-top: 10px;">
                @foreach($views as $view)
                    <div class="chip" style="justify-content: space-between;">
                        <span>{{ $view->view_name }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <h2 style="margin-top: 0;">Identity Columns</h2>
            <div class="table-wrap" style="margin-top: 10px;">
                <table style="min-width: 420px;">
                    <thead>
                    <tr>
                        <th>Table</th>
                        <th>Column</th>
                        <th>Sequence</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($identities as $identity)
                        <tr>
                            <td>{{ $identity->table_name }}</td>
                            <td>{{ $identity->column_name }}</td>
                            <td>{{ $identity->sequence_name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">Triggers</h2>
        <div class="table-wrap" style="margin-top: 10px;">
            <table>
                <thead>
                <tr>
                    <th>Trigger Name</th>
                    <th>Table</th>
                    <th>Event</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($triggers as $trigger)
                    <tr>
                        <td>{{ $trigger->trigger_name }}</td>
                        <td>{{ $trigger->table_name }}</td>
                        <td>{{ $trigger->triggering_event }}</td>
                        <td><span class="chip">{{ $trigger->status }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
