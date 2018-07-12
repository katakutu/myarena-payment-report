var Index = function () {
    return {
        initCharts: function (dataPenjualan, dataPembelian) {
            if (!jQuery.plot) {
                return;
            }

            function showTooltip(title, x, y, contents) {
                $('<div id="tooltip" class="chart-tooltip"><div class="date">' + title + '<\/div><div>Rp ' + contents.toString().replace(/\B(?=(?:\d{3})+(?!\d))/g, ".") + '<\/div><\/div>').css({
                    position: 'absolute',
                    display: 'none',
                    top: y - 60,
                    width: 150,
                    left: x - 60,
                    border: '0px solid #ccc',
                    padding: '2px 6px',
                    'background-color': '#fff',
                }).appendTo("body").fadeIn(200);
            }

            var penjualan = dataPenjualan;

            var pembelian = dataPembelian;

            if ($('#site_statistics').size() != 0) {

                $('#site_statistics_loading').hide();
                $('#site_statistics_content').show();

                var plot_statistics = $.plot($("#site_statistics"), [{
                        data: penjualan,
                        label: "Penjualan"
                    }, {
                        data: pembelian,
                        label: "Pembelian"
                    }
                ], {
                    series: {
                        lines: {
                            show: true,
                            lineWidth: 2,
                            fill: true,
                            fillColor: {
                                colors: [{
                                        opacity: 0.05
                                    }, {
                                        opacity: 0.01
                                    }
                                ]
                            }
                        },
                        points: {
                            show: true
                        },
                        shadowSize: 2
                    },
                    grid: {
                        hoverable: true,
                        clickable: true,
                        tickColor: "#eee",
                        borderWidth: 0
                    },
                    colors: ["#37b7f3", "#852B99", "#52e136"],
                    xaxis: {
                        mode: "time",
                        timeformat: "%d/%m",
                        minTickSize: [1, "day"]
                        
                    },
                    yaxis: {
                        ticks: 10,
                        tickDecimals: 0,
                        tickFormatter: function numberWithCommas(x) {
                            return x.toString().replace(/\B(?=(?:\d{3})+(?!\d))/g, ".");
                        }
                    }
                });

                var previousPoint = null;
                $("#site_statistics").bind("plothover", function (event, pos, item) {
                    $("#x").text(pos.x.toFixed(2));
                    $("#y").text(pos.y.toFixed(2));
                    if (item) {
                        if (previousPoint != item.dataIndex) {
                            previousPoint = item.dataIndex;

                            $("#tooltip").remove();
                            var y = item.datapoint[1];

                            showTooltip(item.series.label, item.pageX, item.pageY, y);
                        }
                    } else {
                        $("#tooltip").remove();
                        previousPoint = null;
                    }
                });
            }               

        }
    };

}();