Number.prototype.formatMoney = function(c, d, t){
    var n = this,
    c = isNaN(c = Math.abs(c)) ? 2 : c,
    d = d == undefined ? "." : d,
    t = t == undefined ? "," : t,
    s = n < 0 ? "-" : "",
    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
    j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

/*
initMap
Initialize leaflet map that uses dark theme from mapbox.
Options must contain
@output     Output element id,
@center     Center point for the map.
@zoom       Initial zoom value.
@minZoom
@maxZoom
*/
function initMap (options) {
    var mapId = options.output + "-map";
    var mapEl = d3.select(document.getElementById(options.output))
                .append("div").attr("id", mapId).attr("class", "map")
    var mapstyle = "https://api.mapbox.com/styles/v1/elguille/cj8mg3oa26rrr2rphl2qtsnv2/tiles/256/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZWxndWlsbGUiLCJhIjoiY2o4bWZ4dGtqMHpncDMybXpld3M2cDFmbCJ9.kxAfb6LwG-sL_LXHrKs-tQ";
    var map = L.map(mapEl[0][0]).setView(options.center, options.zoom);

    L.tileLayer(
        mapstyle, {
            minZoom: options.minZoom,
            maxZoom: options.maxZoom,
            zoomControl: true
        }).addTo(map);

    if (map.scrollWheelZoom) {
        map.scrollWheelZoom.disable();
    }

    return map;
}
/*

Map visualization class.
Plots ring widgets with 3 indicators in a map.
Initialize the map with init function, pass map and options.
Render a dataset with render function.
Data must have lat and lon properties with geolocation and the 3 fields
as described in opions.series.
*/
function MultiPieChartMap() {
    // private
    var container, svg, map, arcs, individualMarkers, clusterMarkers, options, selectedFeature = null, that = this, data = null;
    var max_distance = 70, cluster_tabs = ["A", "B", "C", "D", "E", "F"];
    this.init = function (_map, _options) {
        this.map = _map;
        this.options = _options;
        container = this.map.getContainer();
        svg = d3.select(container).append("svg");
        this.map.on("zoom", updateMarkers);
        this.map.on("viewreset", updateMarkers);
        this.map.on("move", updateCoords);
        this.map.on("moveend", updateCoords);
        return this;
    };
    this.render = function (_data) {
        if (_data) data = _data;
        for (var i = 0; i<data.length; i++) {
            data[i].index = i;
        }
        // Setup html list of links to works

        var avgLon = initLon, avgLat = initLat;
        var list_output = d3.select("div#municipio_obras_output");

        if (data.length==0) {
            list_output.append("h5").style("text-align", "center").text("No hay obras por el momento");
            return [];
        }
        if (list_output) {
            list_output.append("div").selectAll("h5").data(data).enter().append("h5")
                .style("text-align", "center")
                .append("a").text(function (d) { return d.data.nombre; }).property("href", function (d) {return d.data.permalink;});
        }
        data.forEach(function (element, index) {
            avgLon += +element.lon;
            avgLat += +element.lat;
        });
        avgLon = avgLon / (data.length+1);
        avgLat = avgLat / (data.length+1);

        that.options.center = [avgLat, avgLon];
        that.map.setView(new L.LatLng(avgLat, avgLon), initZoom);

        // Prepare map visualization
        var legendWidget = d3.select(container).append("div").attr("class", "map-legend-container")
          .append("div").attr("class", "map-legend")
          .selectAll("div").data(that.options.series);
        var legendLabels = legendWidget.enter()
            .append("div")
        legendLabels
            .append("span").attr("class", "legend-symbol")
            .style("background-color", function (d) { return d.color; } );
        legendLabels
            .append("span").attr("class", "legend-name")
            .text(function (d) {return d.title})
        legendWidget.exit().remove();
        arcs = d3.svg.arc().startAngle(-Math.PI*0.50);
        var transform = d3.geo.transform({point: projectFeature});
        updateMarkers();
        return this;
    };
    function _multiDonutChart (data, wrapper, arcs) {
        if (!arcs)
            arcs = d3.svg.arc().startAngle(-Math.PI*0.50);
        var featuresHandler = wrapper.selectAll("g.multipiechart")
            .data(data);
        var features = featuresHandler.enter().append("g");
        features.attr({
                "cursor": "pointer",
                "opacity": 0.85
            }).classed("multipiechart", true).classed("selected_datapoint", function (d) { return d.index == selectedFeature; });
        features.on("mouseover", function () {
                d3.select(this).attr("opacity", 1.0);
            })
            .on("mouseout", function () {
                d3.select(this).attr("opacity", 0.85);
            })
        features.append("circle").attr({
                "r": 20,
                "fill": "#DDD",
                "opacity": 0.85,
                class: "circle-marker"
            });
        features.append("circle").attr({
                r: 6.5,
                fill: "#333",
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(15).outerRadius(18.5).endAngle(2 * Math.PI),
                "fill": that.options.series[0].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(15).outerRadius(18.5).endAngle(function (d) {return (d[that.options.series[0].field]-0.25) * 2 * Math.PI;}),
                "fill": that.options.series[0].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(11).outerRadius(14.5).endAngle(2 * Math.PI),
                "fill": that.options.series[1].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(11).outerRadius(14.5).endAngle(function (d) {return (d[that.options.series[1].field]-0.25)* 2 * Math.PI;}),
                "fill": that.options.series[1].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(7).outerRadius(10.5).endAngle(2 * Math.PI),
                "fill": that.options.series[2].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.startAngle(-Math.PI*0.50).innerRadius(7).outerRadius(10.5).endAngle(function (d) {return (d[that.options.series[2].field]-0.25) * 2 * Math.PI;}),
                "fill": that.options.series[2].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        /* on click trigger callback */
        features.on("click", featureClickHandler);
        featuresHandler.exit().remove();

        return features;
    }
    function featureClickHandler(d, i) {
        svg.selectAll(".selected_datapoint").classed("selected_datapoint", false);
        selectedFeature = d.index;
        d3.select(this).classed("selected_datapoint", true);
        that.map.setView(new L.LatLng(+d.lat + 0.0085, d.lon-0.006), that.options.onSelectZoom, {animate: true});
        //this.parentElement.appendChild(this);
        if (that.options.onFeatureClick) that.options.onFeatureClick(d,i);
    }
    function projectFeature(d) {
        var point = that.map.latLngToContainerPoint(L.latLng(d.lat,d.lon))
        return point;
    }
    function updateMarkers() {
        var distances = [];
        for(var i = 0; i<data.length; i++) {
            data[i].point = projectFeature(data[i]);
            data[i].clustered = false;
        }
        for(var i = 0; i<data.length; i++) {
            for(var j = 0; j<data.length; j++) {
                if (i<j) {
                    distances.push([i,j,
                        Math.sqrt(Math.pow(data[i].point.x - data[j].point.x, 2) +
                        Math.pow(data[i].point.y - data[j].point.y, 2)), false ]);
                }
            }
        }
        distances.sort(function (a,b) { return a[2] - b[2];  });
        var max_distance_index = 0;
        for (var i=0; i<distances.length; i++) {
            if (distances[i][2] > max_distance) {
                max_distance_index = i;
                break;
            }
        }
        // Compute the clusters for close markers
        var clusters = [];
        while (true) {
            var cluster = {
                x: 0, y: 0, dataPoints: []
            };
            var centerPair = null;
            for (var i=0; i<max_distance_index; i++) {
                if (!distances[i][3] && !data[distances[i][0]].clustered) {
                    centerPair = distances[i];
                    break;
                }
            }
            if (!centerPair) break;
            cluster.point = data[centerPair[0]].point;
            cluster.lat = data[centerPair[0]].lat;
            cluster.lon = data[centerPair[0]].lon;
            // Get all the nearby markers
            for (var i=0; i<max_distance_index; i++) {
                if (distances[i][3]) continue;
                if (distances[i][0] == centerPair[0] && !data[distances[i][1]].clustered) {
                    cluster.dataPoints.push({"index": distances[i][1]});
                    distances[i][3] = true;
                }
                else if (distances[i][1] == centerPair[0]  && !data[distances[i][0]].clustered) {
                    cluster.dataPoints.push({"index": distances[i][0]});
                    distances[i][3] = true;
                }
            }
            // Mark pair as processed (avoid infinite loop)
            centerPair[3] = true;
            // Put cluster only if it has more than 0 data points and add the center of the cluster as a data point. Mark all data points as clustered.
            if (cluster.dataPoints.length > 0) {
                cluster.dataPoints.push({"index": centerPair[0]});
                for(var i=0; i<cluster.dataPoints.length; i++) {
                    data[cluster.dataPoints[i].index].clustered = true;
                }
                clusters.push(cluster);
            }
        }
        // Clean
        if (individualMarkers) individualMarkers.remove();
        if (clusterMarkers) clusterMarkers.remove();
        // Redraw
        // Plot only non clustered items
        individualMarkers = _multiDonutChart(data.filter(function(item) { return !item.clustered;}), svg, arcs);
        /*individualMarkers.append("rect").attr({
                x: function (d) {return -d.titleWidth/2;},
                y: 25,
                width: function (d) {return d.titleWidth;},
                height: 13,
                fill: "white",
                opacity: 0.5,
                rx: 5,
                ry: 5,
                class: "text-background"
            })
        individualMarkers.append("text").attr({
                "class": "point-label",
                "fill": "black",
                x: 0,
                y: 35,
                "font-size": 11,
                "font-weight": "bold",
                "text-anchor": "middle"
            }).text(function (d) {return d.title;});*/
        // Now plot clusters:
        clusterMarkers = svg.selectAll("g.cluster").data(clusters);
        clusterMarkers.enter().append("g").classed("ocmp-marker-cluster", true);
        /*clusterMarkers.append("rect").attr({
                x: -20,
                y: 25,
                width: 40,
                height: 13,
                fill: "white",
                opacity: 0.5,
                rx: 5,
                ry: 5,
                class: "text-background"
            })
        clusterMarkers.append("text").attr({
                "class": "point-label",
                "fill": "black",
                x: 0,
                y: 35,
                "font-size": 11,
                "font-weight": "bold",
                "text-anchor": "middle"
            }).text(function (d) {return "Obras";}); */
        clusterMarkers.append("circle").attr({
                "r": 20,
                "fill": "#DDD",
                "opacity": 0.85,
                "class": "circle-marker"
            });
        clusterMarkers.append("circle").attr({
                r: 6.5,
                fill: "#333"
            });
        clusterMarkers.each(function (d) {
                var cluster =  d3.select(this);
                var more = false, offset, selectedItem = null;
                if (d.dataPoints.length > 6) {
                    more = true;
                    offset = -2.4;
                }
                else {
                    offset = - d.dataPoints.length * 0.8 / 2;
                }
                for (var i=0; i< d3.min([d.dataPoints.length, 6]); i++) {
                    var start = i*0.8+offset+0.07;
                    var label = cluster.append("g").classed("label", true).attr("data-index", i);
                    label.on("mouseover", clusterTabShowHandler).classed("selected", i==0);
                    label.append("path")
                    .attr({
                        "d": arcs.innerRadius(19).outerRadius(34).startAngle(start).endAngle(start+0.65).cornerRadius(5),
                        "fill": "#777", "opacity": 0.7
                    }).classed("background", true);
                    label.append("text").attr({
                        x: 0, y: -23, transform: "rotate("+(start*180/Math.PI+18)+")",
                        fill: "#444", "font-size": 10, "text-anchor": "middle", "font-weight": "bold"
                    }).text((more && i == 5)? "\267\267\267" : cluster_tabs[i]);

                    // Reset arc corner radius (this was causing mis rendering.
                    arcs.cornerRadius(0);

                    var wrapper = cluster.append("g").classed("chart_wrapper", true);
                    d.dataPoints[i].feature = _multiDonutChart([data[d.dataPoints[i].index]], wrapper, arcs);
                    d.dataPoints[i].feature.attr({
                        "data-index": i,
                        "visibility": (i>0) ? "hidden": "visible",
                    });
                    if(i==0) d.dataPoints[i].feature.classed("selected_chart", true);
                    if (selectedFeature == d.dataPoints[i].index) selectedItem = label;
                }
                if (selectedItem !== null) {
                    clusterTabClickHandler.bind(selectedItem[0][0])(d);
                }
            });
        updateCoords();
    }
    function clusterTabShowHandler(d) {
        var parent = d3.select(this.parentNode), element = d3.select(this);
        var index = element.attr("data-index");
        parent.selectAll(".label.selected").classed("selected", false);
        parent.selectAll(".selected_chart").attr("visibility", "hidden").classed("selected_chart", false);
        d.dataPoints[index].feature.attr("visibility", "visible").classed("selected_chart", true);
        element.classed("selected", true);
    }
    function updateCoords() {
        for(var i = 0; i<data.length; i++) {
            if (!data.clustered)
                data[i].point = projectFeature(data[i]);
        }
        if (individualMarkers) {
            individualMarkers.attr("transform", function (d) { return "translate("+d.point.x + ", " + d.point.y + ")"; });
        }
        if (clusterMarkers) {
            clusterMarkers.attr("transform", function (d) {
                var point = projectFeature(d);
                return "translate("+point.x + ", " + point.y + ")"; });
        }
        return this;
    };

    this.resetMapView = function() {
        that.map.setView(new L.LatLng(that.options.center[0], that.options.center[1]), that.options.zoom);
        d3.select("#ficha-mapa .inicio").style("display", "block");
        d3.select("#ficha-mapa .contenido").style("display", "none");
        selectedFeature = null;
    }
    return this;
}
function ObrasMapa (initLat, initLon, initZoom, dataURL, transformData) {
    var map = initMap({
        output: "ocmp-obras",
        center: [initLat, initLon],
        zoom: initZoom,
        minZoom: 9,
        maxZoom: 15
    });
    var grafica = new MultiPieChartMap();
    grafica.init(map, {
        center: [initLat, initLon],
        zoom: initZoom,
        "series": [
            {
                bgcolor: "#69F", color: "#4AC",
                title: "Porcentaje de avance fí­sico",
                field: "fisico"
            },
            {
                bgcolor: "#E97", color: "#C47",
                title: "Porcentaje de avance financiero",
                field: "financiero"
            },
            {
                bgcolor: "#9B5", color: "#5B5",
                title: "Tiempo transcurrido",
                field: "tiempo"
            }
        ],
        "onFeatureClick": function (d, i) {
            d3.select("#ficha-mapa .inicio").style("display", "none");
            d3.select("#ficha-mapa .contenido").style("display", "block");
            d3.select("#ficha-mapa .field-titulo").text(d.data.municipalidad);
            d3.select("#ficha-mapa .field-descripcion").text(d.data.nombre);
            /*
            d3.select("#ficha-mapa .field-nog").text(d.data["NOG"]);
            d3.select("#ficha-mapa .field-snip").text(d.data["SNIP"]);
            */
            d3.select("#ficha-mapa .field-monto").text("Q. " + parseFloat(d.data.monto).formatMoney());
            d3.select("#ficha-mapa .field-proveedor").text(d.data.proveedor);
            d3.select("#ficha-mapa .field-fuente").text(d.data.fuentefinanciamiento);
            d3.select("#ficha-mapa .field-alcalde").text(d.data.alcalde + " - " + d.data.partido);
            d3.select("#ficha-mapa .ocdsrecord-permalink")[0][0].href = d.data.permalink;
            /*
            d3.select("#ficha-mapa .field-fisico").text(Math.round((d.fisico-0.01)/0.0095) + "%")
            d3.select("#ficha-mapa .field-financiero").text(Math.round((d.financiero-0.01)/0.0095) + "%");
            d3.select("#ficha-mapa .field-tiempo").text(Math.round((d.tiempo)/0.99*100) + "%");
            */
        },
        onSelectZoom: 14
    });
    d3.json(dataURL, function(data) {
        var preparedData = _.map(data.records, function(item) {
            var inicio = moment(item.inicio_contrato),
                final = moment(item.final_contrato),
                ahora = moment(),
                transcurrido = (ahora-inicio) / (final-inicio);
            transcurrido = transcurrido || 0.01;
            transcurrido = d3.min([1.0, transcurrido]);
            transcurrido = d3.max([0.01, transcurrido]);
            retItem = {
                lat: item.coordinates.lat,
                lon: item.coordinates.lon,
                fisico: parseFloat(item.avance_fisico)*0.01*0.95 + 0.01,
                financiero: parseFloat(item.avance_financiero)*0.01*0.95 + 0.01,
                tiempo: transcurrido*0.99,
                data: item,
                title: "Obra", titleWidth: 40
            };
            return retItem;
        });
        if (transformData)
            preparedData = transformData(preparedData);

        grafica.render(preparedData);
    });
    return grafica;
}
