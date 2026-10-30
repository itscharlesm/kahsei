<style>
    .hover-effect {
        transition: color 0.3s ease, transform 0.3s ease;
        display: inline-block;
    }

    .hover-effect:hover {
        color: darkslategray !important;
        transform: translateY(-2px) !important;
    }

    .chart-card-body {
        position: relative;
        height: 280px;
    }

    .stat-card .inner h3 {
        font-size: 1.9rem;
    }

    .range-btn-group .btn {
        min-width: 95px;
    }

    .range-btn-group .btn.active {
        font-weight: 600;
    }

    #dashboardFilterOverlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.6);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    #dashboardWrapper {
        position: relative;
    }

    @media (min-width: 768px) {
        .col-md-2-4 {
            flex: 0 0 20%;
            max-width: 20%;
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
    }
</style>

<div class="container-fluid" id="dashboardWrapper">

    <div id="dashboardFilterOverlay">
        <i class="fas fa-2x fa-sync-alt fa-spin"></i>
    </div>

    {{-- FILTER BAR: Date Range + Branch --}}
    <section id="dashboard-filters">
        <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
            <div class="btn-group range-btn-group mb-2" role="group" aria-label="Date range filter">
                <button type="button"
                    class="btn btn-outline-primary range-option {{ $activeRange == 7 ? 'active' : '' }}" data-range="7">
                    Last 7 Days
                </button>
                <button type="button"
                    class="btn btn-outline-primary range-option {{ $activeRange == 30 ? 'active' : '' }}"
                    data-range="30">
                    Last 30 Days
                </button>
                <button type="button"
                    class="btn btn-outline-primary range-option {{ $activeRange == 90 ? 'active' : '' }}"
                    data-range="90">
                    Last 90 Days
                </button>
            </div>

            @if ($sessionBranchId == 1)
                <div class="form-group mb-2">
                    <label for="branchFilter" class="mr-2 mb-0" style="font-weight:600">Branch:</label>
                    <select id="branchFilter" class="form-control d-inline-block" style="width:auto;">
                        <option value="all" {{ $activeBranch == 'all' ? 'selected' : '' }}>All Branches</option>
                        @foreach ($branchOptions as $b)
                            <option value="{{ $b->branch_id }}" {{ (string) $activeBranch === (string) $b->branch_id ? 'selected' : '' }}>
                                {{ $b->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </section>

    {{-- SERVICE ORDERS PIPELINE --}}
    <section id="pipeline">
        <div class="d-flex align-items-center mb-2">
            <h5 class="mb-0" style="font-weight:600"><i class="fas fa-route mr-2"></i>Service Orders Pipeline</h5>
            <span class="badge bg-green ml-2" id="rangeBadge">Last {{ $activeRange }} Days</span>
        </div>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="small-box bg-warning stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="requested">{{ $pipeline['requested'] }}</h3>
                        <p>Requested</p>
                    </div>
                    <div class="icon"><i class="fas fa-inbox"></i></div>
                    <a href="{{ url('service/order/appointments/requested') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="small-box bg-secondary stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="unassigned">{{ $pipeline['unassigned'] }}</h3>
                        <p>Unassigned</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-clock"></i></div>
                    <a href="{{ url('service/order/appointments/scheduling') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="small-box bg-info stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="scheduled">{{ $pipeline['scheduled'] }}</h3>
                        <p>Scheduled</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <a href="{{ url('service/order/appointments/scheduling') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="small-box bg-primary stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="ongoing">{{ $pipeline['ongoing'] }}</h3>
                        <p>Ongoing</p>
                    </div>
                    <div class="icon"><i class="fas fa-route"></i></div>
                    <a href="{{ url('service/order/appointments/ongoing/tracking') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="small-box bg-teal stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="with_report">{{ $pipeline['with_report'] }}</h3>
                        <p>Finished Services</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <a href="{{ url('service/order/appointments/finished') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 col-6">
                <div class="small-box bg-success stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="completed">{{ $pipeline['completed'] }}</h3>
                        <p>Completed</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <a href="{{ url('service/order/appointments/completed') }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- CAPTURES SUMMARY --}}
        <div class="d-flex align-items-center mb-2">
            <h5 class="mb-0" style="font-weight:600"><i class="fas fa-box mr-2"></i>Captures Summary</h5>
        </div>
        <div class="row">
            <div class="col-md-2-4 col-6">
                <div class="small-box bg-maroon stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="rats">{{ $captures['rats'] }}</h3>
                        <p>Rats Captured</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>

            <div class="col-md-2-4 col-6">
                <div class="small-box bg-olive stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="snakes">{{ $captures['snakes'] }}</h3>
                        <p>Snakes Captured</p>
                    </div>
                    <div class="icon"><i class="fas fa-skull-crossbones"></i></div>
                </div>
            </div>

            <div class="col-md-2-4 col-6">
                <div class="small-box bg-lime stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="lizards">{{ $captures['lizards'] }}</h3>
                        <p>Lizards Captured</p>
                    </div>
                    <div class="icon"><i class="fas fa-dragon"></i></div>
                </div>
            </div>

            <div class="col-md-2-4 col-6">
                <div class="small-box bg-purple stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="kittens">{{ $captures['kittens'] }}</h3>
                        <p>Kittens Captured</p>
                    </div>
                    <div class="icon"><i class="fas fa-cat"></i></div>
                </div>
            </div>

            <div class="col-md-2-4 col-6">
                <div class="small-box bg-gray stat-card">
                    <div class="inner">
                        <h3 class="mb-0" data-stat="others">{{ $captures['others'] }}</h3>
                        <p>Others Captured</p>
                    </div>
                    <div class="icon"><i class="fas fa-bug"></i></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ACTIVE PROFILES + PAYMENT RATE --}}
    <section id="profiles-payment">
        <div class="row align-items-stretch">
            <div class="col-md-8 d-flex mb-3 mb-md-0">
                <div class="card card-outline card-navy h-100 w-100 mb-0">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i class="fas fa-users mr-2"></i>Active
                            Profiles</h3>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column justify-content-center">
                        <div class="row">
                            <div class="col-md-3 col-6">
                                <div class="info-box bg-gradient-navy">
                                    <span class="info-box-icon"><i class="fa fa-user-shield"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 11px">Admins</span>
                                        <span class="info-box-number"
                                            data-stat="admins">{{ $profiles['admins'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon"><i class="fa fa-user-gear"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 11px">Technicians</span>
                                        <span class="info-box-number"
                                            data-stat="technicians">{{ $profiles['technicians'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="info-box bg-gradient-olive">
                                    <span class="info-box-icon"><i class="fa fa-users-between-lines"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 11px">Clients</span>
                                        <span class="info-box-number"
                                            data-stat="clients">{{ $profiles['clients'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="info-box bg-gradient-teal">
                                    <span class="info-box-icon"><i class="fa fa-building"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 11px">Branches</span>
                                        <span class="info-box-number"
                                            data-stat="branches">{{ $profiles['branches'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex">
                <div class="card card-outline card-success h-100 w-100 mb-0">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i
                                class="fas fa-hand-holding-dollar mr-2"></i>Payment Rate</h3>
                    </div>
                    <div class="card-body pt-0 text-center d-flex flex-column justify-content-center">
                        <h2 class="mb-0" style="font-weight:700; color:#28a745;" id="collectionRateText">
                            {{ $payments['collection_rate'] }}%
                        </h2>
                        <p class="text-muted mb-2">Collected of ₱ <span
                                id="totalAmountText">{{ number_format($payments['total'], 2) }}</span></p>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" id="collectionRateBar"
                                style="width: {{ $payments['collection_rate'] }}%"
                                aria-valuenow="{{ $payments['collection_rate'] }}" aria-valuemin="0"
                                aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-success"><i class="fa fa-circle"></i> Collected: ₱ <span
                                    id="collectedAmountText">{{ number_format($payments['collected'], 2) }}</span></span>
                            <span class="text-danger"><i class="fa fa-circle"></i> Pending: ₱ <span
                                    id="pendingAmountText">{{ number_format($payments['pending'], 2) }}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- GRAPHS --}}
    <section id="graphs" class="mt-3">
        <div class="row">
            <div class="col-md-7">
                <div class="card card-outline card-primary">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i
                                class="fas fa-chart-line mr-2"></i>Appointments Trend</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-card-body">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card card-outline card-info">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i class="fas fa-chart-pie mr-2"></i>Payment
                            Collected vs Pending</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-card-body">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-secondary">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i class="fas fa-chart-bar mr-2"></i>Service
                            Status Breakdown</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-outline card-teal">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title" style="font-weight:600"><i class="fas fa-chart-bar mr-2"></i>Top
                            Branches by Appointment Volume</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-card-body">
                            <canvas id="branchChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Chart.js (CDN) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<script>
    (function () {
        var trendChart, statusChart, branchChart, paymentChart;

        var initialData = {
            pipeline: @json($pipeline),
            captures: @json($captures),
            profiles: @json($profiles),
            payments: @json($payments),
            trendLabels: @json($trendLabels),
            trendData: @json($trendData),
            statusBreakdown: @json($statusBreakdown),
            topBranches: @json($topBranches),
            paymentBreakdown: @json($paymentBreakdown),
        };

        function renderCharts(data) {
            // 1. Appointments Trend (Line)
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.trendLabels,
                    datasets: [{
                        label: 'Appointments',
                        data: data.trendData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0,123,255,0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: data.trendLabels.length > 30 ? 0 : 4,
                        pointBackgroundColor: '#007bff'
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
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: 12,
                                autoSkip: true
                            }
                        }
                    }
                }
            });

            // 2. Service Status Breakdown (Bar)
            if (statusChart) statusChart.destroy();
            statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: data.statusBreakdown.labels,
                    datasets: [{
                        label: 'Service Orders',
                        data: data.statusBreakdown.data,
                        backgroundColor: ['#ffc107', '#6c757d', '#17a2b8', '#007bff', '#20c997',
                            '#28a745'
                        ]
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
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // 3. Top Branches (Bar)
            if (branchChart) branchChart.destroy();
            branchChart = new Chart(document.getElementById('branchChart'), {
                type: 'bar',
                data: {
                    labels: data.topBranches.labels,
                    datasets: [{
                        label: 'Appointments',
                        data: data.topBranches.data,
                        backgroundColor: '#39CCCC'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // 4. Payment Collected vs Pending (Donut)
            if (paymentChart) paymentChart.destroy();
            paymentChart = new Chart(document.getElementById('paymentChart'), {
                type: 'doughnut',
                data: {
                    labels: data.paymentBreakdown.labels,
                    datasets: [{
                        data: data.paymentBreakdown.data,
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function updateStatCards(data) {
            document.querySelector('[data-stat="requested"]').textContent = data.pipeline.requested;
            document.querySelector('[data-stat="unassigned"]').textContent = data.pipeline.unassigned;
            document.querySelector('[data-stat="scheduled"]').textContent = data.pipeline.scheduled;
            document.querySelector('[data-stat="ongoing"]').textContent = data.pipeline.ongoing;
            document.querySelector('[data-stat="with_report"]').textContent = data.pipeline.with_report;
            document.querySelector('[data-stat="completed"]').textContent = data.pipeline.completed;

            document.querySelector('[data-stat="rats"]').textContent = data.captures.rats;
            document.querySelector('[data-stat="snakes"]').textContent = data.captures.snakes;
            document.querySelector('[data-stat="lizards"]').textContent = data.captures.lizards;
            document.querySelector('[data-stat="kittens"]').textContent = data.captures.kittens;
            document.querySelector('[data-stat="others"]').textContent = data.captures.others;

            document.querySelector('[data-stat="admins"]').textContent = data.profiles.admins;
            document.querySelector('[data-stat="technicians"]').textContent = data.profiles.technicians;
            document.querySelector('[data-stat="clients"]').textContent = data.profiles.clients;
            document.querySelector('[data-stat="branches"]').textContent = data.profiles.branches;

            document.getElementById('collectionRateText').textContent = data.payments.collection_rate + '%';
            document.getElementById('totalAmountText').textContent = formatPeso(data.payments.total);
            document.getElementById('collectedAmountText').textContent = formatPeso(data.payments.collected);
            document.getElementById('pendingAmountText').textContent = formatPeso(data.payments.pending);

            var bar = document.getElementById('collectionRateBar');
            bar.style.width = data.payments.collection_rate + '%';
            bar.setAttribute('aria-valuenow', data.payments.collection_rate);
        }

        function formatPeso(n) {
            return Number(n).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function setLoading(isLoading) {
            document.getElementById('dashboardFilterOverlay').style.display = isLoading ? 'flex' : 'none';
        }

        function getActiveRange() {
            var activeBtn = document.querySelector('.range-option.active');
            return activeBtn ? activeBtn.getAttribute('data-range') : '7';
        }

        function getSelectedBranch() {
            var sel = document.getElementById('branchFilter');
            return sel ? sel.value : 'all';
        }

        function fetchDashboard() {
            var range = getActiveRange();
            var branch = getSelectedBranch();

            setLoading(true);

            fetch('{{ url('home/dashboard-data') }}?range=' + encodeURIComponent(range) + '&branch=' +
                encodeURIComponent(branch), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    updateStatCards(data);
                    renderCharts(data);
                    document.getElementById('capturesRangeBadge').textContent = 'Last ' + range + ' Days';
                    document.getElementById('rangeBadge').textContent = 'Last ' + range + ' Days';
                    document.getElementById('trendRangeLabel').textContent = '(Last ' + range + ' Days)';
                })
                .catch(function (err) {
                    console.error('Dashboard fetch failed:', err);
                })
                .finally(function () {
                    setLoading(false);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initial render using server-provided data (no extra request on first load)
            renderCharts(initialData);

            // Range buttons
            document.querySelectorAll('.range-option').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.range-option').forEach(function (b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    fetchDashboard();
                });
            });

            // Branch dropdown (super admin only)
            var branchSelect = document.getElementById('branchFilter');
            if (branchSelect) {
                branchSelect.addEventListener('change', function () {
                    fetchDashboard();
                });
            }
        });
    })();
</script>