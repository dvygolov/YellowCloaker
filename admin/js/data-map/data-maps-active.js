(function ($) {
 "use strict";
	
	var basic_choropleth = new Datamap({
			  element: document.getElementById("basic_choropleth"),
			  projection: 'mercator',
			  fills: {
				defaultFill: "#ABDDA4",
				authorHasTraveledTo: "#fa0fa0"
			  },
			  data: {
				USA: { fillKey: "authorHasTraveledTo" },
				JPN: { fillKey: "authorHasTraveledTo" },
				ITA: { fillKey: "authorHasTraveledTo" },
				CRI: { fillKey: "authorHasTraveledTo" },
				KOR: { fillKey: "authorHasTraveledTo" },
				DEU: { fillKey: "authorHasTraveledTo" },
			  }
			});

			var colors = d3.scale.category10();

			window.setInterval(function() {
			  basic_choropleth.updateChoropleth({
				USA: colors(Math.random() * 10),
				RUS: colors(Math.random() * 100),
				AUS: { fillKey: 'authorHasTraveledTo' },
				BRA: colors(Math.random() * 50),
				CAN: colors(Math.random() * 50),
				ZAF: colors(Math.random() * 50),
				IND: colors(Math.random() * 50),
			  });
			}, 2000);
	
	
        
	
		var basic = new Datamap({
                element: document.getElementById("basic_map"),
                responsive: true,
                fills: {
                    defaultFill: "#DBDAD6"
                },
                geographyConfig: {
                    highlightFillColor: '#03a9f4',
                    highlightBorderWidth: 0,
                },
            });

            var selected_map = new Datamap({
                element: document.getElementById("selected_map"),
                responsive: true,
                fills: {
                    defaultFill: "#DBDAD6",
                    active: "#03a9f4"
                },
                geographyConfig: {
                    highlightFillColor: '#03a9f4',
                    highlightBorderWidth: 0,
                },
                data: {
                    USA: { fillKey: "active" },
                    RUS: { fillKey: "active" },
                    DEU: { fillKey: "active" },
                    BRA: { fillKey: "active" }
                }
            });

            var usa_map = new Datamap({
                element: document.getElementById("usa_map"),
                responsive: true,
                scope: 'usa',
                fills: {
                    defaultFill: "#DBDAD6",
                    active: "#03a9f4"
                },
                geographyConfig: {
                    highlightFillColor: '#03a9f4',
                    highlightBorderWidth: 0
                },
                data: {
                    NE: { fillKey: "active" },
                    CA: { fillKey: "active" },
                    NY: { fillKey: "active" },
                }
            });

			
			var map = new Datamap({
        scope: 'world',
        element: document.getElementById('projection_map'),
        projection: 'orthographic',
        fills: {
          defaultFill: "#ABDDA4",
          gt50: colors(Math.random() * 20),
          eq50: colors(Math.random() * 20),
          lt25: colors(Math.random() * 10),
          gt75: colors(Math.random() * 200),
          lt50: colors(Math.random() * 20),
          eq0: colors(Math.random() * 1),
          pink: '#0fa0fa',
          gt500: colors(Math.random() * 1)
        },
        projectionConfig: {
          rotation: [97,-30]
        },
        data: {
          'USA': {fillKey: 'lt50' },
          'MEX': {fillKey: 'lt25' },
          'CAN': {fillKey: 'gt50' },
          'GTM': {fillKey: 'gt500'},
          'HND': {fillKey: 'eq50' },
          'BLZ': {fillKey: 'pink' },
          'GRL': {fillKey: 'eq0' },
          'CAN': {fillKey: 'gt50' }
        }
      });

      map.graticule();

      map.arc([{
        origin: {
          latitude: 61,
          longitude: -149
        },
        destination: {
          latitude: -22,
          longitude: -43
        }
      }], {
        greatArc: true,
        animationSpeed: 2000
      });
 
			
            var arc_map = new Datamap({
                element: document.getElementById("arc_map"),
                responsive: true,
                fills: {
                    defaultFill: "#F2F2F0",
                    active: "#03a9f4",
                    usa: "#03a9f4"
                },
                geographyConfig: {
                    highlightFillColor: '#03a9f4',
                    highlightBorderWidth: 0
                },
                data: {
                    USA: {fillKey: "usa"},
                    RUS: {fillKey: "active"},
                    DEU: {fillKey: "active"},
                    POL: {fillKey: "active"},
                    JAP: {fillKey: "active"},
                    AUS: {fillKey: "active"},
                    BRA: {fillKey: "active"}
                }
            });

            arc_map.arc(
                    [
                        { origin: 'USA', destination: 'RUS'},
                        { origin: 'USA', destination: 'DEU'},
                        { origin: 'USA', destination: 'POL'},
                        { origin: 'USA', destination: 'JAP'},
                        { origin: 'USA', destination: 'AUS'},
                        { origin: 'USA', destination: 'BRA'}
                    ],
                    { strokeColor: '#03a9f4', strokeWidth: 1}
            );
			
	
})(jQuery); 