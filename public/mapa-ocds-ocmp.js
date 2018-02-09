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
    var container, svg, map, features, options, selectedFeature = null, that = this, data = null;
    this.init = function (_map, _options) {
        map = _map;
        options = _options;
        container = map.getContainer();
        svg = d3.select(container).append("svg");
        map.on("zoom", updateCoords);
        map.on("viewreset", updateCoords);
        map.on("move", updateCoords);
        map.on("moveend", updateCoords);
        return this;
    };
    this.render = function (_data) {
        if (_data) data = _data;
        var legendWidget = d3.select(container).append("div").attr("class", "map-legend-container")
          .append("div").attr("class", "map-legend")
          .selectAll("div").data(options.series);
        var legendLabels = legendWidget.enter()
            .append("div")
        legendLabels
            .append("span").attr("class", "legend-symbol")
            .style("background-color", function (d) { return d.color; } );
        legendLabels
            .append("span").attr("class", "legend-name")
            .text(function (d) {return d.title})
        legendWidget.exit().remove();

        var featuresHandler = svg.selectAll("g")
            .data(data);
        features = featuresHandler.enter().append("g");
        features.attr({
                "cursor": "pointer",
                "opacity": 0.85
            });
        features
            .on("mouseover", function () {
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
        features.append("rect").attr({
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
        features.append("text").attr({
                "class": "point-label",
                "fill": "black",
                x: 0,
                y: 35,
                "font-size": 11,
                "font-weight": "bold",
                "text-anchor": "middle"
            }).text(function (d) {return d.title;});
        var arcs = d3.svg.arc().startAngle(-Math.PI*0.50);
        features.append("path")
            .attr({
                "d": arcs.innerRadius(15).outerRadius(18.5).endAngle(2 * Math.PI),
                "fill": options.series[0].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.innerRadius(15).outerRadius(18.5).endAngle(function (d) {return (d[options.series[0].field]-0.25) * 2 * Math.PI;}),
                "fill": options.series[0].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        features.append("path")
            .attr({
                "d": arcs.innerRadius(11).outerRadius(14.5).endAngle(2 * Math.PI),
                "fill": options.series[1].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.innerRadius(11).outerRadius(14.5).endAngle(function (d) {return (d[options.series[1].field]-0.25)* 2 * Math.PI;}),
                "fill": options.series[1].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        features.append("path")
            .attr({
                "d": arcs.innerRadius(7).outerRadius(10.5).endAngle(2 * Math.PI),
                "fill": options.series[2].bgcolor,
                "fill-opacity": 0.4
            });
        features.append("path")
            .attr({
                "d": arcs.innerRadius(7).outerRadius(10.5).endAngle(function (d) {return (d[options.series[2].field]-0.25) * 2 * Math.PI;}),
                "fill": options.series[2].color,
                "stroke-width": "1",
                "stroke": "black"
            });
        /* on click trigger callback */
        features.on("click", featureClickHandler);
        featuresHandler.exit().remove();
        var transform = d3.geo.transform({point: projectFeature});
        updateCoords();
        return this;
    };
    function featureClickHandler(d, i) {
        map.setView(new L.LatLng(d.lat, d.lon), options.onSelectZoom, {animate: true});
        d3.select(selectedFeature).attr("class", "");
        d3.select(this).attr("class", "selected-feature");
        selectedFeature = this;
        this.parentElement.appendChild(this);
        if (options.onFeatureClick) options.onFeatureClick(d,i);
    }
    function projectFeature(d) {
        var point = map.latLngToContainerPoint(L.latLng(d.lat,d.lon))
        return point;
    }
    function updateCoords() {
        if (features) {
            features.attr("transform", function (d) { return "translate("+projectFeature(d).x + ", " + projectFeature(d).y + ")"; });
        }
        return this;
    };
    return this;
}
var initLat = 14.6, initLon = -90.5, initZoom = 10;
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
            d3.select("#ficha-mapa .ocdsrecord-permalink").attr("xlink:href", d.data.permalink);
            /*
            d3.select("#ficha-mapa .field-fisico").text(Math.round((d.fisico-0.01)/0.0095) + "%")
            d3.select("#ficha-mapa .field-financiero").text(Math.round((d.financiero-0.01)/0.0095) + "%");
            d3.select("#ficha-mapa .field-tiempo").text(Math.round((d.tiempo)/0.99*100) + "%");
            */
        },
        onSelectZoom: 14
    });
    d3.json("/open-contracting/wp-content/plugins/wp-ocds/ocds-api.php?/records/summary", function(data) {
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
                title: "Obra", titleWidth: 50
            };
            return retItem;
        });
        grafica.render(preparedData);
    });
    function resetMapView() {
        map.setView(new L.LatLng(initLat, initLon), initZoom);
        d3.select("#ficha-mapa .inicio").style("display", "block");
        d3.select("#ficha-mapa .contenido").style("display", "none");
    }
}
