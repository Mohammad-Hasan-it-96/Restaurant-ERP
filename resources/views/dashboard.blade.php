@extends('layouts.app')

@section('title', \App\Helpers\Helpers::translate('dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">{{\App\Helpers\Helpers::translate('dashboard')}}</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary">
                <i class="bi bi-download me-2"></i>{{\App\Helpers\Helpers::translate('export')}}
            </button>
            <button class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>{{\App\Helpers\Helpers::translate('new_report')}}
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1">{{\App\Helpers\Helpers::translate('total_sales')}}</h6>
                            <h3 class="fw-bold mb-0">$24,780</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background-color: rgba(79, 70, 229, 0.1);">
                            <i class="bi bi-cart-check fs-4" style="color: var(--primary);"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">+12.5%</span>
                        <span class="text-muted small">{{\App\Helpers\Helpers::translate('from_last_month')}}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1">{{\App\Helpers\Helpers::translate('total_orders')}}</h6>
                            <h3 class="fw-bold mb-0">1,482</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background-color: rgba(16, 185, 129, 0.1);">
                            <i class="bi bi-box-seam fs-4" style="color: var(--success);"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">+8.2%</span>
                        <span class="text-muted small">{{\App\Helpers\Helpers::translate('from_last_month')}}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1">{{\App\Helpers\Helpers::translate('new_customers')}}</h6>
                            <h3 class="fw-bold mb-0">382</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background-color: rgba(59, 130, 246, 0.1);">
                            <i class="bi bi-people fs-4" style="color: var(--info);"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">+5.7%</span>
                        <span class="text-muted small">{{\App\Helpers\Helpers::translate('from_last_month')}}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1">{{\App\Helpers\Helpers::translate('conversion_rate')}}</h6>
                            <h3 class="fw-bold mb-0">3.42%</h3>
                        </div>
                        <div class="rounded-circle p-2" style="background-color: rgba(245, 158, 11, 0.1);">
                            <i class="bi bi-graph-up-arrow fs-4" style="color: var(--warning);"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-2">-1.2%</span>
                        <span class="text-muted small">{{\App\Helpers\Helpers::translate('from_last_month')}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{\App\Helpers\Helpers::translate('sales_overview')}}</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{\App\Helpers\Helpers::translate('this_month')}}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">{{\App\Helpers\Helpers::translate('this_week')}}</a></li>
                            <li><a class="dropdown-item" href="#">{{\App\Helpers\Helpers::translate('this_month')}}</a></li>
                            <li><a class="dropdown-item" href="#">{{\App\Helpers\Helpers::translate('this_year')}}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{\App\Helpers\Helpers::translate('product_categories')}}</h5>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-three-dots"></i>
                    </button>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{\App\Helpers\Helpers::translate('recent_orders')}}</h5>
                    <a href="#" class="btn btn-sm btn-link text-decoration-none">{{\App\Helpers\Helpers::translate('view_all')}}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>{{\App\Helpers\Helpers::translate('order_id')}}</th>
                                    <th>{{\App\Helpers\Helpers::translate('customer')}}</th>
                                    <th>{{\App\Helpers\Helpers::translate('product')}}</th>
                                    <th>{{\App\Helpers\Helpers::translate('date')}}</th>
                                    <th>{{\App\Helpers\Helpers::translate('amount')}}</th>
                                    <th>{{\App\Helpers\Helpers::translate('status')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#ORD-7843</td>
                                    <td>John Smith</td>
                                    <td>Protein Powder</td>
                                    <td>Aug 12, 2023</td>
                                    <td>$59.99</td>
                                    <td><span class="badge bg-success">{{\App\Helpers\Helpers::translate('completed')}}</span></td>
                                </tr>
                                <tr>
                                    <td>#ORD-7842</td>
                                    <td>Emma Johnson</td>
                                    <td>Resistance Bands</td>
                                    <td>Aug 11, 2023</td>
                                    <td>$24.99</td>
                                    <td><span class="badge bg-warning text-dark">{{\App\Helpers\Helpers::translate('processing')}}</span></td>
                                </tr>
                                <!-- More rows... -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Rest of the content... -->
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Sales',
                data: [12500, 15000, 17500, 14000, 18000, 19500, 22000, 24000, 23000, 25000, 27000, 24500],
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [2, 4],
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Proteins', 'Pre-Workout', 'Vitamins', 'Weight Loss', 'Accessories'],
            datasets: [{
                data: [35, 25, 15, 15, 10],
                backgroundColor: [
                    '#4f46e5',
                    '#10b981',
                    '#3b82f6',
                    '#f59e0b',
                    '#64748b'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
</script>

<style>
    .bg-soft-primary {
        background-color: rgba(79, 70, 229, 0.1) !important;
    }

    .text-primary {
        color: var(--primary) !important;
    }
</style>
@endpush
