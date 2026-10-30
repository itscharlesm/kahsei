document.addEventListener('DOMContentLoaded', function () {
    function fetchAndDisplayDefaultCharts() {
        const fetchUrl = '/default-charts';
        fetch(fetchUrl)
            .then(response => response.json())
            .then(data => {
                const barChartLabels = data.default_bar_chart_data.map(item => item.eqp_name);
                const barChartValues = data.default_bar_chart_data.map(item => item.total_quantity);

                const topBarChartLabels = data.top_borrowers_bar_chart_data.map(item => item.borrower_name_code);
                const topBarChartValues = data.top_borrowers_bar_chart_data.map(item => item.total_quantity);

                const pieChartLabels = data.pie_chart_data.map(item => item.eqp_name);
                const pieChartValues = data.pie_chart_data.map(item => item.percentage);

                displayBarChart(barChartLabels, barChartValues);
                displayTopBarChart(topBarChartLabels, topBarChartValues);
                displayPieChart(pieChartLabels, pieChartValues);
            })
            .catch(error => {
                console.error('Error fetching default data:', error);
            });
    }

    document.getElementById('searchForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;

        if (fromDate > toDate) {
            alert('Please select a valid date range.');
            return;
        }

        fetchFilteredDataAndDisplayCharts(fromDate, toDate);
    });

    function fetchFilteredDataAndDisplayCharts(fromDate, toDate) {
        const url = '/admin/search';
        const token = document.querySelector('input[name="_token"]').value;

        const formData = new FormData();
        formData.append('from_date', fromDate);
        formData.append('to_date', toDate);
        formData.append('_token', token);

        fetch(url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            updateCharts(data);
            updateTables(data);
        })
        .catch(error => {
            console.error('Error fetching filtered data:', error);
        });
    }

    function displayDefaultCharts(data) {
        updateCharts(data);
        updateTables(data);
    }

    function updateTables(data) {
        updateTable1(data.filtered_bar_chart_data);
        updateTable2(data.filtered_top_borrowers_bar_chart_data);
    }

    function updateTable1(data) {
        const table = document.getElementById('table1Body');
        table.innerHTML = '';

        data.forEach(item => {
            const row = `<tr>
                <td>${item.eqp_name}</td>
                <td style="font-weight: bold">${item.total_quantity}</td>
            </tr>`;
            table.insertAdjacentHTML('beforeend', row);
        });
    }

    function updateTable2(data) {
        const table = document.getElementById('table2Body');
        table.innerHTML = '';

        data.forEach(item => {
            const row = `<tr>
                <td>${item.borrower_name_code}</td>
                <td style="font-weight: bold">${item.total_quantity}</td>
            </tr>`;
            table.insertAdjacentHTML('beforeend', row);
        });
    }

    function updateCharts(data) {
        const barChartLabels = data.filtered_bar_chart_data.map(item => item.eqp_name);
        const barChartValues = data.filtered_bar_chart_data.map(item => item.total_quantity);

        const topBarChartLabels = data.filtered_top_borrowers_bar_chart_data.map(item => item.borrower_name_code);
        const topBarChartValues = data.filtered_top_borrowers_bar_chart_data.map(item => item.total_quantity);

        const pieChartLabels = data.filtered_pie_chart_data.map(item => item.eqp_name);
        const pieChartValues = data.filtered_pie_chart_data.map(item => item.percentage);

        displayBarChart(barChartLabels, barChartValues);
        displayTopBarChart(topBarChartLabels, topBarChartValues);
        displayPieChart(pieChartLabels, pieChartValues);
    }

    function displayBarChart(labels, values) {
        const ctx = document.getElementById('barChart').getContext('2d');
        if (window.barChartInstance) {
            window.barChartInstance.destroy();
        }
        window.barChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Borrowed Equipments',
                    data: values,
                    backgroundColor: '#662C91',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function displayTopBarChart(labels, values) {
        const ctx1 = document.getElementById('topBarChart').getContext('2d');
        if (window.topBarChartInstance) {
            window.topBarChartInstance.destroy();
        }
        window.topBarChartInstance = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Borrowers',
                    data: values,
                    backgroundColor: '#662C91',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function displayPieChart(labels, values) {
        const ctx2 = document.getElementById('pieChart').getContext('2d');
        if (window.pieChartInstance) {
            window.pieChartInstance.destroy();
        }
        window.pieChartInstance = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        '#FFC107',
                        '#2196F3',
                        '#FF5722',
                        '#795548',
                        '#9C27B0',
                        '#4CAF50',
                        '#FF9800',
                        '#607D8B',
                        '#00BCD4',
                        '#F44336'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    fetchAndDisplayDefaultCharts();
});
