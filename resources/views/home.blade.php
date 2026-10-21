@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Dashboard')

@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of your QA activities')

@section('content')
    <!-- Status Alert if Session Exists -->
    @if (session('status'))
        <div class="content-card mb-4" style="background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; padding: 12px 20px; border-radius: 12px;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('status') }}
        </div>
    @endif

    <!-- ===== STATS CARDS ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-number">156</div>
            <div class="stat-label">Total Test Cases</div>
            <div class="stat-change up"><i class="fas fa-arrow-up"></i> 12% from last month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number">124</div>
            <div class="stat-label">Passed</div>
            <div class="stat-change up"><i class="fas fa-arrow-up"></i> 8% from last month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number">23</div>
            <div class="stat-label">Failed</div>
            <div class="stat-change down"><i class="fas fa-arrow-down"></i> 3% from last month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-number">9</div>
            <div class="stat-label">Blocked</div>
            <div class="stat-change down"><i class="fas fa-arrow-down"></i> 2% from last month</div>
        </div>
    </div>

    <!-- ===== RECENT TEST CASES ===== -->
    <div class="content-card">
        <div class="card-header">
            <h3><i class="fas fa-clock mr-2 text-blue-500"></i>Recent Test Cases</h3>
            <a href="#" class="card-action">View All &rarr;</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Payment Gateway Integration</strong></td>
                            <td><span class="badge-status badge-info">Integration</span></td>
                            <td><span class="priority-high">High</span></td>
                            <td><span class="badge-status badge-success">PASS</span></td>
                            <td>2026-08-13</td>
                        </tr>
                        <tr>
                            <td><strong>User Login Authentication</strong></td>
                            <td><span class="badge-status badge-info">Functional</span></td>
                            <td><span class="priority-critical">Critical</span></td>
                            <td><span class="badge-status badge-danger">FAILED</span></td>
                            <td>2026-08-12</td>
                        </tr>
                        <tr>
                            <td><strong>API Performance Test</strong></td>
                            <td><span class="badge-status badge-info">Performance</span></td>
                            <td><span class="priority-high">High</span></td>
                            <td><span class="badge-status badge-warning">BLOCKED</span></td>
                            <td>2026-08-12</td>
                        </tr>
                        <tr>
                            <td><strong>Database Migration Validation</strong></td>
                            <td><span class="badge-status badge-info">Regression</span></td>
                            <td><span class="priority-medium">Medium</span></td>
                            <td><span class="badge-status badge-success">PASS</span></td>
                            <td>2026-08-11</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== RECENT EXECUTIONS ===== -->
    <div class="content-card">
        <div class="card-header">
            <h3><i class="fas fa-history mr-2 text-green-500"></i>Recent Executions</h3>
            <a href="#" class="card-action">View All &rarr;</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Test Case</th>
                            <th>Executed By</th>
                            <th>Result</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Payment Gateway Integration</td>
                            <td>{{ Auth::user()->name ?? 'John Doe' }}</td>
                            <td><span class="badge-status badge-success">PASSED</span></td>
                            <td>2026-08-13 14:30</td>
                        </tr>
                        <tr>
                            <td>User Login Authentication</td>
                            <td>Jane Smith</td>
                            <td><span class="badge-status badge-danger">FAILED</span></td>
                            <td>2026-08-13 11:20</td>
                        </tr>
                        <tr>
                            <td>API Performance Test</td>
                            <td>Mike Johnson</td>
                            <td><span class="badge-status badge-warning">BLOCKED</span></td>
                            <td>2026-08-12 16:45</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
