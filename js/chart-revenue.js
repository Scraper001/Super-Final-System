


        // Connect to the actual database - NO DUMMY DATA
        let paymentRecords = [];

        // Function to calculate monthly revenue
        function calculateMonthlyRevenue(records) {
            const monthlyRevenue = {};
            const monthlyPayments = {};
            
            records.forEach(record => {
                const date = new Date(record.date_created);
                const month = date.getMonth();
                const year = date.getFullYear();
                const monthYear = `${year}-${month + 1}`;
                
                // Calculate actual revenue (cash minus change)
                const revenue = parseInt(record.cash) - parseInt(record.change);
                
                if (!monthlyRevenue[monthYear]) {
                    monthlyRevenue[monthYear] = 0;
                    monthlyPayments[monthYear] = 0;
                }
                
                monthlyRevenue[monthYear] += revenue;
                monthlyPayments[monthYear]++;
            });
            
            return { monthlyRevenue, monthlyPayments };
        }

        // Function to format month-year for display
        function formatMonthYear(monthYear) {
            const [year, month] = monthYear.split('-');
            const date = new Date(parseInt(year), parseInt(month) - 1, 1);
            return date.toLocaleString('default', { month: 'short', year: 'numeric' });
        }

        // Function to update chart data
        function updateChartData(records) {
            const { monthlyRevenue, monthlyPayments } = calculateMonthlyRevenue(records);
            
            // Sort months chronologically
            const sortedMonths = Object.keys(monthlyRevenue).sort();
            
            // Prepare data for chart
            const categories = sortedMonths.map(formatMonthYear);
            const seriesData = sortedMonths.map(month => monthlyRevenue[month]);
            
            // Update chart configuration
            chartConfig.series[0].data = seriesData;
            chartConfig.xaxis.categories = categories;
            
            // Render chart
            chart.updateOptions(chartConfig);
            
            // Update table
            updateRevenueTable(sortedMonths, monthlyRevenue, monthlyPayments);
        }

        // Function to update revenue table
        function updateRevenueTable(months, monthlyRevenue, monthlyPayments) {
            const tableBody = document.getElementById('revenueTableBody');
            tableBody.innerHTML = '';
            
            months.forEach(month => {
                const row = document.createElement('tr');
                
                const monthCell = document.createElement('td');
                monthCell.className = 'py-2 px-4 border-b border-gray-200';
                monthCell.textContent = formatMonthYear(month);
                
                const revenueCell = document.createElement('td');
                revenueCell.className = 'py-2 px-4 border-b border-gray-200';
                revenueCell.textContent = `$${monthlyRevenue[month].toLocaleString()}`;
                
                const paymentsCell = document.createElement('td');
                paymentsCell.className = 'py-2 px-4 border-b border-gray-200';
                paymentsCell.textContent = monthlyPayments[month];
                
                row.appendChild(monthCell);
                row.appendChild(revenueCell);
                row.appendChild(paymentsCell);
                
                tableBody.appendChild(row);
            });
        }

        // Function to filter records by date
        function filterRecordsByDate(records, dateStr) {
            if (!dateStr) return records;
            
            const filterDate = new Date(dateStr);
            const filterYear = filterDate.getFullYear();
            const filterMonth = filterDate.getMonth();
            
            return records.filter(record => {
                const recordDate = new Date(record.date_created);
                return recordDate.getFullYear() === filterYear && recordDate.getMonth() === filterMonth;
            });
        }

        // Initialize date filter
        const filterDateInput = document.getElementById('filterDate');
        filterDateInput.valueAsDate = new Date();
        
        // Function to fetch data from the database
        async function fetchRevenueData() {
            try {
                // In a real implementation, this would be an API call to your backend
                const response = await fetch('functions/chart/revenue.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch revenue data');
                }
                
                const data = await response.json();
                paymentRecords = data;
                
                // Update chart with the fetched data
                updateChartData(paymentRecords);
                
                // Show success message
                console.log('Revenue data loaded successfully');
            } catch (error) {
                console.error('Error fetching revenue data:', error);
                document.querySelector('#bar-chart').innerHTML = 
                    '<div class="text-red-500 text-center py-4">Failed to load revenue data. Please try again.</div>';
            }
        }
        
        // Add event listener for date filter
        filterDateInput.addEventListener('change', function() {
            const filteredRecords = filterRecordsByDate(paymentRecords, this.value);
            updateChartData(filteredRecords.length > 0 ? filteredRecords : paymentRecords);
        });

        // Add event listener for print button
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        // Initial data fetch
        fetchRevenueData();

        // Chart configuration
        const chartConfig = {
            series: [
                {
                    name: "Revenue",
                    data: [],
                },
            ],
            chart: {
                type: "bar",
                height: 240,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                    },
                },
            },
            title: {
                text: "Monthly Revenue",
                align: "center",
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold',
                    color: '#a65f00'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return '$' + val.toLocaleString();
                },
                style: {
                    fontSize: '12px',
                    colors: ['#333']
                }
            },
            colors: ["#f0b100"],
            plotOptions: {
                bar: {
                    columnWidth: "40%",
                    borderRadius: 2,
                },
            },
            xaxis: {
                axisTicks: {
                    show: false,
                },
                axisBorder: {
                    show: false,
                },
                labels: {
                    style: {
                        colors: "#a65f00",
                        fontSize: "12px",
                        fontFamily: "inherit",
                        fontWeight: 400,
                    },
                },
                categories: [],
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    },
                    style: {
                        colors: "#616161",
                        fontSize: "12px",
                        fontFamily: "inherit",
                        fontWeight: 400,
                    },
                },
            },
            grid: {
                show: true,
                borderColor: "#dddddd",
                strokeDashArray: 5,
                xaxis: {
                    lines: {
                        show: true,
                    },
                },
                padding: {
                    top: 5,
                    right: 20,
                },
            },
            fill: {
                opacity: 0.8,
            },
            tooltip: {
                theme: "light",
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
        };

        // Initialize chart
        const chart = new ApexCharts(document.querySelector("#bar-chart"), chartConfig);
        chart.render();
        
        // Initial chart setup - data will be populated by fetchRevenueData()
        
        // Set up print styles
        const style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .p-2, .p-2 * {
                    visibility: visible;
                }
                .p-2 {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                #printBtn {
                    display: none;
                }
            }
        `;
        document.head.appendChild(style);
