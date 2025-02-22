(function($) {
    "use strict";

    $(document).ready(function() {
        console.log("charts.js imported");
    });
})(jQuery);

function createTable(data, container) {
    var tableContainer = document.getElementById(container);
    console.log('go ', tableContainer);

    var table = document.createElement('table');
    var thead = document.createElement('thead');
    var tbody = document.createElement('tbody');

    // Create table header
    var headerRow = document.createElement('tr');
    var headers = ['Антраг', 'Количество'];
    headers.forEach(function(header) {
        var th = document.createElement('th');
        th.textContent = header;
        //headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
	
	console.log('qqqqqqqqq', data);
    // Create table rows
    data.forEach(function(item) {
        var row = document.createElement('tr');

        var typeCell = document.createElement('td');
        typeCell.textContent = item.typeAntrag;
        row.appendChild(typeCell);

        var quantityCell = document.createElement('td');
        quantityCell.textContent = item.quantity;
        row.appendChild(quantityCell);

        tbody.appendChild(row);
    });
    table.appendChild(tbody);

    // Append table to container
    tableContainer.appendChild(table);
}

function createChart(chartType, data, chartDivId, isPercent) {
    document.addEventListener("DOMContentLoaded", function() {
        am5.ready(function() {
			var chartDiv = document.getElementById(chartDivId);
			chartDiv.style.width = 60 + '%';
            chartDiv.style.height = 50 + '%';
			
            var root = am5.Root.new(chartDivId);

            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            var chart, series;

            console.log(['chosen', chartType]);

            switch (chartType) {
                case 'pie':
                    createPieChart(root, data, isPercent);
                    break;
                case 'xy':
                    createXYChart(root, data);
					chartDiv.style.width = 40 + '%';
                    break;
                case 'sidexy':
                    createSideXYChart(root, data);
					chartDiv.style.height = 40 + '%';
                    break;
                case 'radar':
                    createRadarChart(root, data);
                    break;
				case 'map':
					createMap(root, data);
					break;
				case 'tree':
					createTree(root, data);
					break;
				case 'dragpie':
					createDragPie(root, data);
					break;
				case 'donut':
					createDonut(root, data);
					break;
				case 'radiuspie':
					createRadiusPie(root, data);
					break;
				case 'info':
					createInfographic(root, data);
					break;
                default:
                    console.error('Unknown chart type:', chartType);
            }
        });
    });
}

function createPieChart(root, data, isPercent) {
    var chart = root.container.children.push(am5percent.PieChart.new(root, {
        layout: root.verticalLayout
    }));

    var series = chart.series.push(am5percent.PieSeries.new(root, {
        valueField: "quantity",
        categoryField: "typeAntrag"
    }));

    if (isPercent) {
        series.slices.template.set("tooltipText", "{category}: {value.percent.formatNumber('#.0')}%");
        series.labels.template.set("text", "{category}: {value.percent.formatNumber('#.0')}%");
    } else {
        series.slices.template.set("tooltipText", "{category}: {value}");
        series.labels.template.set("text", "{category}: {value}");
    }

    series.data.setAll(data);

    var legend = chart.children.push(am5.Legend.new(root, {
        centerX: am5.percent(50),
        x: am5.percent(50),
        layout: root.horizontalLayout
    }));
    legend.data.setAll(series.dataItems);

    series.appear(1000, 100);
}

function createXYChart(root, data) {
    var chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: true,
        panY: true,
        wheelX: "panX",
        wheelY: "zoomX",
        pinchZoomX: true,
        layout: root.verticalLayout,
        //paddingRight: 200,
        //paddingLeft: 200
    }));

    var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
    cursor.lineY.set("visible", false);

    var categoryAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        renderer: am5xy.AxisRendererX.new(root, {}),
        categoryField: "typeAntrag"
    }));

    var valueAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {}),
        strictMinMax: true,
        min: 0,
        maxPrecision: 0
    }));

    var categoryList = data.map(function(item) {
        return { typeAntrag: item.typeAntrag };
    });

    categoryAxis.data.setAll(categoryList);

    var series = chart.series.push(am5xy.ColumnSeries.new(root, {
        xAxis: categoryAxis,
        yAxis: valueAxis,
        valueYField: "quantity",
        categoryXField: "typeAntrag"
    }));

    series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5, strokeOpacity: 0 });
    series.columns.template.adapters.add("fill", function(fill, target) {
        return chart.get("colors").getIndex(series.columns.indexOf(target));
    });

    series.columns.template.adapters.add("stroke", function(stroke, target) {
        return chart.get("colors").getIndex(series.columns.indexOf(target));
    });

    series.columns.template.set("tooltipText", "{categoryX}: {valueY}");

    series.data.setAll(data);
    series.appear(1000, 100);
}

function createSideXYChart(root, data) {
    var chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false,
        panY: false,
        wheelX: "none",
        wheelY: "none",
        paddingLeft: 0
    }));

    var yRenderer = am5xy.AxisRendererY.new(root, {
        minGridDistance: 30,
        minorGridEnabled: true
    });
    yRenderer.grid.template.set("location", 1);

    var yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
        maxDeviation: 0,
        categoryField: "typeAntrag",
        renderer: yRenderer,
        tooltip: am5.Tooltip.new(root, { themeTags: ["axis"] })
    }));

    var xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
        maxDeviation: 0,
        min: 0,
        extraMax: 0.1,
        maxPrecision: 0,
        renderer: am5xy.AxisRendererX.new(root, {
            strokeOpacity: 0.1,
            minGridDistance: 80
        })
    }));

    var series = chart.series.push(am5xy.ColumnSeries.new(root, {
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "quantity",
        categoryYField: "typeAntrag",
        tooltip: am5.Tooltip.new(root, {
            pointerOrientation: "left",
            labelText: "{valueX}"
        })
    }));

    series.columns.template.setAll({
        cornerRadiusTR: 5,
        cornerRadiusBR: 5,
        strokeOpacity: 0
    });

    series.columns.template.adapters.add("fill", function(fill, target) {
        return chart.get("colors").getIndex(series.columns.indexOf(target));
    });

    series.columns.template.adapters.add("stroke", function(stroke, target) {
        return chart.get("colors").getIndex(series.columns.indexOf(target));
    });

    yAxis.data.setAll(data);
    series.data.setAll(data);
    series.appear(1000, 100);
}

function createRadarChart(root, data) {

    var chart = root.container.children.push(
        am5radar.RadarChart.new(root, {
            panX: false,
            panY: false,
            wheelX: "panX",
            wheelY: "zoomX",
            //paddingRight: 200,
            //paddingLeft: 200,
            innerRadius: am5.p50,
            layout: root.verticalLayout
        })
    );

    var xRenderer = am5radar.AxisRendererCircular.new(root, {});
    xRenderer.labels.template.setAll({
        textType: "adjusted"
    });

    var xAxis = chart.xAxes.push(
        am5xy.CategoryAxis.new(root, {
            maxDeviation: 0,
            categoryField: "typeAntrag",
            renderer: xRenderer,
            tooltip: am5.Tooltip.new(root, {})
        })
    );

    var yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
            renderer: am5radar.AxisRendererRadial.new(root, {})
        })
    );

    for (var i = 0; i < 4; i++) {
        var series = chart.series.push(
            am5radar.RadarColumnSeries.new(root, {
                stacked: true,
                name: "Series " + i,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "quantity",
                categoryXField: "typeAntrag"
            })
        );

        series.columns.template.setAll({
            tooltipText: "{name}: {valueY}"
        });

        series.data.setAll(data);
        series.appear(1000);
    }

    // slider
    var slider = chart.children.push(
        am5.Slider.new(root, {
            orientation: "horizontal",
            start: 1,
            width: am5.percent(60),
            centerY: am5.p50,
            centerX: am5.p50,
            x: am5.p50
        })
    );
    slider.events.on("rangechanged", function() {
        var start = slider.get("start");
        var startAngle = 270 - start * 179 - 1;
        var endAngle = 270 + start * 179 + 1;

        chart.setAll({ startAngle: startAngle, endAngle: endAngle });
        yAxis.get("renderer").set("axisAngle", startAngle);
    });

    xAxis.data.setAll(data);
    chart.appear(1000, 100);
}

function createMap(root, data){
	var chart = root.container.children.push(am5map.MapChart.new(root, {
	  panX: "rotateX",
	  projection: am5map.geoMercator(),
	  layout: root.horizontalLayout
	}));

	   var geo = am5.JSONParser.parse({"country_code":"DE","country_name":"Germany"});
	   loadGeodata(geo.country_code);

	var polygonSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
	  calculateAggregates: true,
	  valueField: "value"
	}));

	polygonSeries.mapPolygons.template.setAll({
	  tooltipText: "{name}",
	  interactive: true
	});

	polygonSeries.mapPolygons.template.states.create("hover", {
	  fill: am5.color(0x677935)
	});

	polygonSeries.set("heatRules", [{
	  target: polygonSeries.mapPolygons.template,
	  dataField: "value",
	  min: am5.color(0x8ab7ff),
	  max: am5.color(0x25529a),
	  key: "fill"
	}]);

	polygonSeries.mapPolygons.template.events.on("pointerover", function(ev) {
	  heatLegend.showValue(ev.target.dataItem.get("value"));
	});

	function loadGeodata(country) {

	  // Default map
	  var defaultMap = "Germany";
	  
	  chart.set("projection", am5map.geoMercator());

	  // calculate which map to be used
	  var currentMap = defaultMap;
	  var title = "";
	  if (am5geodata_data_countries2[country] !== undefined) {
		currentMap = am5geodata_data_countries2[country]["maps"][0];

		// add country title
		if (am5geodata_data_countries2[country]["country"]) {
		  title = am5geodata_data_countries2[country]["country"];
		}
	  }
	  
		am5.net.load("https://cdn.amcharts.com/lib/5/geodata/json/" + currentMap + ".json", chart).then(function (result) {
		var geodata = am5.JSONParser.parse(result.response);
		var data = [];
		for(var i = 0; i < geodata.features.length; i++) {
		  data.push({
			id: geodata.features[i].id,
			value: Math.round( Math.random() * 10000 )
		  });
		}

		polygonSeries.set("geoJSON", geodata);
		polygonSeries.data.setAll(data)
		});
	  
	  chart.seriesContainer.children.push(am5.Label.new(root, {
		x: 5,
		y: 5,
		text: title,
		background: am5.RoundedRectangle.new(root, {
		  fill: am5.color(0xffffff),
		  fillOpacity: 0.2
		})
	  }))

	}

	var heatLegend = chart.children.push(
	  am5.HeatLegend.new(root, {
		orientation: "vertical",
		startColor: am5.color(0x8ab7ff),
		endColor: am5.color(0x25529a),
		startText: "Lowest",
		endText: "Highest",
		stepCount: 5
	  })
	);

	heatLegend.startLabel.setAll({
	  fontSize: 12,
	  fill: heatLegend.get("startColor")
	});

	heatLegend.endLabel.setAll({
	  fontSize: 12,
	  fill: heatLegend.get("endColor")
	});

	// change this to template when possible
	polygonSeries.events.on("datavalidated", function () {
	  heatLegend.set("startValue", polygonSeries.getPrivate("valueLow"));
	  heatLegend.set("endValue", polygonSeries.getPrivate("valueHigh"));
	});
}

function createTree(root, data){
	// Create wrapper container
var container = root.container.children.push(am5.Container.new(root, {
  width: am5.percent(90),
  height: am5.percent(90),
  layout: root.verticalLayout
}));


// Create series
// https://www.amcharts.com/docs/v5/charts/hierarchy/#Adding
var series = container.children.push(am5hierarchy.ForceDirected.new(root, {
  singleBranchOnly: false,
  downDepth: 1,
  initialDepth: 2,
  valueField: "value",
  categoryField: "name",
  childDataField: "children",
  centerStrength: 0.5
}));


// Generate and set data
// https://www.amcharts.com/docs/v5/charts/hierarchy/#Setting_data
var maxLevels = 2;
var maxNodes = 5;
var maxValue = 100;

var data = {
  name: "Планы",
  children: []
}
generateLevel(data, "", 0);

series.data.setAll([data]);
series.set("selectedDataItem", series.dataItems[0]);

function generateLevel(data, name, level) {
  for (var i = 0; i < Math.ceil(maxNodes * Math.random()) + 1; i++) {
    var nodeName = name + "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[i];
    var child;
    if (level < maxLevels) {
      child = {
        name: nodeName + level
      }

      if (level > 0 && Math.random() < 0.5) {
        child.value = Math.round(Math.random() * maxValue);
      }
      else {
        child.children = [];
        generateLevel(child, nodeName + i, level + 1)
      }
    }
    else {
      child = {
        name: name + i,
        value: Math.round(Math.random() * maxValue)
      }
    }
    data.children.push(child);
  }

  level++;
  return data;
}


// Make stuff animate on load
series.appear(1000, 100);
}

function createDragPie(root, data){
	var container = root.container.children.push(am5.Container.new(root, {
  width: am5.p100,
  height: am5.p100,
  layout: root.horizontalLayout
}));

// Create first chart
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/
var chart0 = container.children.push(am5percent.PieChart.new(root, {
  innerRadius: am5.p50,
  tooltip: am5.Tooltip.new(root, {})
}));

// Create series
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
var series0 = chart0.series.push(am5percent.PieSeries.new(root, {
  valueField: "quantity",
  categoryField: "typeAntrag",
  alignLabels: false
}));

series0.labels.template.setAll({
  textType: "circular",
  templateField: "dummyLabelSettings"
});

series0.ticks.template.set("forceHidden", true);

var sliceTemplate0 = series0.slices.template;
sliceTemplate0.setAll({
  draggable: true,
  templateField: "settings",
  cornerRadius: 5
});

// Separator line
container.children.push(am5.Line.new(root, {
  layer: 1,
  height: am5.percent(60),
  y: am5.p50,
  centerY: am5.p50,
  strokeDasharray: [4, 4],
  stroke: root.interfaceColors.get("alternativeBackground"),
  strokeOpacity: 0.5
}));

// Label
container.children.push(am5.Label.new(root, {
  layer: 1,
  text: "Перетащите фрагменты за линию",
  y: am5.p50,
  textAlign: "center",
  rotation: -90,
  isMeasured: false
}));

// Create second chart
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/
var chart1 = container.children.push(am5percent.PieChart.new(root, {
  innerRadius: am5.p50,
  tooltip: am5.Tooltip.new(root, {})
}));

// Create series
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
var series1 = chart1.series.push(am5percent.PieSeries.new(root, {
  valueField: "quantity",
  categoryField: "typeAntrag",
  alignLabels: false
}));

series1.labels.template.setAll({
  textType: "circular",
  radius: 20,
  templateField: "dummyLabelSettings"
});

series1.ticks.template.set("forceHidden", true);

var sliceTemplate1 = series1.slices.template;
sliceTemplate1.setAll({
  draggable: true,
  templateField: "settings",
  cornerRadius: 5
});

var previousDownSlice;

// change layers when down
sliceTemplate0.events.on("pointerdown", function (e) {
  if (previousDownSlice) {
    //  previousDownSlice.set("layer", 0);
  }
  e.target.set("layer", 1);
  previousDownSlice = e.target;
});

sliceTemplate1.events.on("pointerdown", function (e) {
  if (previousDownSlice) {
    // previousDownSlice.set("layer", 0);
  }
  e.target.set("layer", 1);
  previousDownSlice = e.target;
});

// when released, do all the magic
sliceTemplate0.events.on("pointerup", function (e) {
  series0.hideTooltip();
  series1.hideTooltip();

  var slice = e.target;
  if (slice.x() > container.width() / 4) {
    var index = series0.slices.indexOf(slice);
    slice.dataItem.hide();

    var series1DataItem = series1.dataItems[index];
    series1DataItem.show();
    series1DataItem.get("slice").setAll({ x: 0, y: 0 });

    handleDummy(series0);
    handleDummy(series1);
  } else {
    slice.animate({
      key: "x",
      to: 0,
      duration: 500,
      easing: am5.ease.out(am5.ease.cubic)
    });
    slice.animate({
      key: "y",
      to: 0,
      duration: 500,
      easing: am5.ease.out(am5.ease.cubic)
    });
  }
});

sliceTemplate1.events.on("pointerup", function (e) {
  var slice = e.target;

  series0.hideTooltip();
  series1.hideTooltip();

  if (slice.x() < container.width() / 4) {
    var index = series1.slices.indexOf(slice);
    slice.dataItem.hide();

    var series0DataItem = series0.dataItems[index];
    series0DataItem.show();
    series0DataItem.get("slice").setAll({ x: 0, y: 0 });

    handleDummy(series0);
    handleDummy(series1);
  } else {
    slice.animate({
      key: "x",
      to: 0,
      duration: 500,
      easing: am5.ease.out(am5.ease.cubic)
    });
    slice.animate({
      key: "y",
      to: 0,
      duration: 500,
      easing: am5.ease.out(am5.ease.cubic)
    });
  }
});

// data
 var seriesdata = [
  {
    typeAntrag: "Dummy",
    quantity: 1000,
    settings: {
      fill: am5.color(0xdadada),
      stroke: am5.color(0xdadada),
      fillOpacity: 0.3,
      strokeDasharray: [4, 4],
      tooltipText: null,
      draggable: false
    },
    dummyLabelSettings: {
      forceHidden: true
    }
  }
];
data = seriesdata.concat(data.flat());
//var data = data;

// show/hide dummy slice depending if there are other visible slices
function handleDummy(series) {
  // count visible data items
  var visibleCount = 0;
  am5.array.each(series.dataItems, function (dataItem) {
    if (!dataItem.isHidden()) {
      visibleCount++;
    }
  });
  // if all hidden, show dummy
  if (visibleCount == 0) {
    series.dataItems[0].show();
  } else {
    series.dataItems[0].hide();
  }
}
// set data
series0.data.setAll(data);
series1.data.setAll(data);

// hide all except dummy
am5.array.each(series1.dataItems, function (dataItem) {
  if (dataItem.get("category") != "Dummy") {
    dataItem.hide(0);
  }
});

// hide dummy
series0.dataItems[0].hide(0);

// reveal container
container.appear(1000, 100);
}

function createDonut(root, data){
	var chart = root.container.children.push(am5percent.PieChart.new(root, {
  radius: am5.percent(90),
  innerRadius: am5.percent(50),
  layout: root.horizontalLayout
}));

// Create series
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
var series = chart.series.push(am5percent.PieSeries.new(root, {
  name: "Series",
  valueField: "quantity",
  categoryField: "typeAntrag"
}));

// Set data
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Setting_data
series.data.setAll(data);

// Disabling labels and ticks
series.labels.template.set("visible", false);
series.ticks.template.set("visible", false);

// Adding gradients
series.slices.template.set("strokeOpacity", 0);
series.slices.template.set("fillGradient", am5.RadialGradient.new(root, {
  stops: [{
    brighten: -0.8
  }, {
    brighten: -0.8
  }, {
    brighten: -0.5
  }, {
    brighten: 0
  }, {
    brighten: -0.5
  }]
}));

// Create legend
// https://www.amcharts.com/docs/v5/charts/percent-charts/legend-percent-series/
var legend = chart.children.push(am5.Legend.new(root, {
  centerY: am5.percent(50),
  y: am5.percent(50),
  layout: root.verticalLayout
}));
// set value labels align to right
legend.valueLabels.template.setAll({ textAlign: "right" })
// set width and max width of labels
legend.labels.template.setAll({ 
  maxWidth: 140,
  width: 140,
  oversizedBehavior: "wrap"
});

legend.data.setAll(series.dataItems);


// Play initial series animation
// https://www.amcharts.com/docs/v5/concepts/animations/#Animation_of_series
series.appear(1000, 100);
}

function createRadiusPie(root, data){
	var chart = root.container.children.push(am5percent.PieChart.new(root, {
  layout: root.verticalLayout
}));


// Create series
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
var series = chart.series.push(am5percent.PieSeries.new(root, {
  alignLabels: true,
  calculateAggregates: true,
  valueField: "quantity",
  categoryField: "typeAntrag"
}));

series.slices.template.setAll({
  strokeWidth: 3,
  stroke: am5.color(0xffffff)
});

series.labelsContainer.set("paddingTop", 30)


// Set up adapters for variable slice radius
// https://www.amcharts.com/docs/v5/concepts/settings/adapters/
series.slices.template.adapters.add("radius", function (radius, target) {
  var dataItem = target.dataItem;
  var high = series.getPrivate("valueHigh");

  if (dataItem) {
    var value = target.dataItem.get("valueWorking", 0);
    return radius * value / high
  }
  return radius;
});


// Set data
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Setting_data
series.data.setAll(data);


// Create legend
// https://www.amcharts.com/docs/v5/charts/percent-charts/legend-percent-series/
var legend = chart.children.push(am5.Legend.new(root, {
  centerX: am5.p50,
  x: am5.p50,
  marginTop: 15,
  marginBottom: 15
}));

legend.data.setAll(series.dataItems);


// Play initial series animation
// https://www.amcharts.com/docs/v5/concepts/animations/#Animation_of_series
series.appear(1000, 100);
}

function createInfographic(root, data){
	var chart = root.container.children.push(
  am5xy.XYChart.new(root, {
    panX: false,
    panY: false,
    wheelX: "panX",
    wheelY: "zoomX",
    layout: root.horizontalLayout,
    arrangeTooltips: false
  })
);

// Use only absolute numbers
root.numberFormatter.set("numberFormat", "#.#s'%");

// Add legend
// https://www.amcharts.com/docs/v5/charts/xy-chart/legend-xy-series/
var legend = chart.children.push(
  am5.Legend.new(root, {
    centerY: am5.p50,
    y: am5.p50,
    //useDefaultMarker: true,
    layout: root.verticalLayout
  })
);

legend.markers.template.setAll({
  width: 50,
  height: 50
})

// Data
var data = [{
  category: "Маркетинг",
  male: -36,
  maleMax: -100,
  female: 64,
  femaleMax: 100
}, {
  category: "Разработка",
  male: -58,
  maleMax: -100,
  female: 42,
  femaleMax: 100
}, {
  category: "Руководство",
  male: -59,
  maleMax: -100,
  female: 41,
  femaleMax: 100
}, {
  category: "Администраторы",
  male: -41,
  maleMax: -100,
  female: 59,
  femaleMax: 100
}, {
  category: "Техподдержка",
  male: -50,
  maleMax: -100,
  female: 50,
  femaleMax: 100
}, {
  category: "Другие",
  male: -36,
  maleMax: -100,
  female: 64,
  femaleMax: 100
}];

// Create axes
// https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
var yAxis = chart.yAxes.push(
  am5xy.CategoryAxis.new(root, {
    categoryField: "category",
    renderer: am5xy.AxisRendererY.new(root, {
      inversed: true,
      cellStartLocation: 0.1,
      cellEndLocation: 0.9
    })
  })
);

var yRenderer = yAxis.get("renderer");
yRenderer.grid.template.setAll({
  visible: false
});

yAxis.data.setAll(data);

var xAxis = chart.xAxes.push(
  am5xy.ValueAxis.new(root, {
    calculateTotals: true,
    min: -100,
    max: 100,
    renderer: am5xy.AxisRendererX.new(root, {
      minGridDistance: 80
    })
  })
);

var xRenderer = xAxis.get("renderer");
xRenderer.grid.template.setAll({
  visible: false
});

var rangeDataItem = xAxis.makeDataItem({
  value: 0
});

var range = xAxis.createAxisRange(rangeDataItem);

range.get("grid").setAll({
  stroke: am5.color(0xeeeeee),
  strokeOpacity: 1,
  location: 1,
  visible: true
});

// Add series
// https://www.amcharts.com/docs/v5/charts/xy-chart/series/
function createSeries(field, name, color, icon, inlegend) {
  var series = chart.series.push(
    am5xy.ColumnSeries.new(root, {
      xAxis: xAxis,
      yAxis: yAxis,
      name: name,
      valueXField: field,
      categoryYField: "category",
      sequencedInterpolation: true,
      fill: color,
      stroke: color,
      clustered: false
    })
  );

  series.columns.template.setAll({
    height: 50,
    fillOpacity: 0,
    strokeOpacity: 0
  });
  
  if (icon) {
    series.columns.template.set("fillPattern", am5.PathPattern.new(root, {
      color: color,
      repetition: "repeat-x",
      width: 50,
      height: 50,
      fillOpacity: 0,
      svgPath: icon
    }));
  }

  series.data.setAll(data);
  series.appear();

  if (inlegend) {
    legend.data.push(series);
  }

  return series;
}

var femaleColor = am5.color(0xf25f5c);
var maleColor = am5.color(0x247ba0);
var placeholderColor = am5.color(0xeeeeee);

var maleIcon = "M 25.1 10.7 c 2.1 0 3.7 -1.7 3.7 -3.7 c 0 -2.1 -1.7 -3.7 -3.7 -3.7 c -2.1 0 -3.7 1.7 -3.7 3.7 C 21.4 9 23 10.7 25.1 10.7 z M 28.8 11.5 H 25.1 h -3.7 c -2.8 0 -4.7 2.5 -4.7 4.8 V 27.7 c 0 2.2 3.1 2.2 3.1 0 V 17.2 h 0.6 v 28.6 c 0 3 4.2 2.9 4.3 0 V 29.3 h 0.7 h 0.1 v 16.5 c 0.2 3.1 4.3 2.8 4.3 0 V 17.2 h 0.5 v 10.5 c 0 2.2 3.2 2.2 3.2 0 V 16.3 C 33.5 14 31.6 11.5 28.8 11.5 z";
var femaleIcon = "M 18.4 15.1 L 15.5 25.5 c -0.6 2.3 2.1 3.2 2.7 1 l 2.6 -9.6 h 0.7 l -4.5 16.9 H 21.3 v 12.7 c 0 2.3 3.2 2.3 3.2 0 V 33.9 h 1 v 12.7 c 0 2.3 3.1 2.3 3.1 0 V 33.9 h 4.3 l -4.6 -16.9 h 0.8 l 2.6 9.6 c 0.7 2.2 3.3 1.3 2.7 -1 l -2.9 -10.4 c -0.4 -1.2 -1.8 -3.3 -4.2 -3.4 h -4.7 C 20.1 11.9 18.7 13.9 18.4 15.1 z M 28.6 7.2 c 0 -2.1 -1.6 -3.7 -3.7 -3.7 c -2 0 -3.7 1.7 -3.7 3.7 c 0 2.1 1.6 3.7 3.7 3.7 C 27 10.9 28.6 9.2 28.6 7.2 z";

createSeries("maleMax", "Мужчины", placeholderColor, maleIcon, false);
createSeries("male", "Мужчины", maleColor, maleIcon, true);
createSeries("femaleMax", "Женщины", placeholderColor, femaleIcon, false);
createSeries("female", "Женщины", femaleColor, femaleIcon, true);


// Make stuff animate on load
// https://www.amcharts.com/docs/v5/concepts/animations/
chart.appear(1000, 100);
}
