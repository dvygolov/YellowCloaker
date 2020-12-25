(function ($) {
 "use strict";
 
	 /*----------------------------------------*/
	/*  1.  pie Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("piechart");
	var piechart = new Chart(ctx, {
		type: 'pie',
		data: {
			labels: ["Red", "Orange", "Yellow", "Green", "Blue"],
			datasets: [{
				label: 'pie Chart',
                backgroundColor: [
					'rgb(255, 99, 132)',
					'rgb(255, 159, 64)',
					'rgb(255, 205, 86)',
					'#03a9f4',
					'#303030'
				],
				data: [10, 20, 30, 40, 60]
            }]
		},
		options: {
			responsive: true
		}
	});
	 /*----------------------------------------*/
	/*  2.  polar Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("polarchart");
	var polarchart = new Chart(ctx, {
		type: 'polarArea',
		data: {
			labels: ["Red", "Orange", "Yellow", "Green", "Blue"],
			datasets: [{
				label: 'pie Chart',
				data: [10, 20, 30, 40, 60],
                backgroundColor: [
					'rgb(255, 99, 132)',
					'rgb(255, 159, 64)',
					'rgb(255, 205, 86)',
					'#03a9f4',
					'rgb(201, 203, 207)'
				],
				
            }]
		},
		options: {
            responsive: true,
            legend: {
                 position: 'right',
            },
            title: {
                display: true,
                text: 'Polar Chart'
            },
            scale: {
              ticks: {
                beginAtZero: true
              },
              reverse: false
            },
            animation: {
                animateRotate: false,
                animateScale: true
            }
        }
	});
	
	 /*----------------------------------------*/
	/*  3.  radar Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("radarchart");
	var radarchart = new Chart(ctx, {
		type: 'radar',
		data: {
			labels: ["Design", "Development", "Graphic", "Android", "Games"],
			datasets: [{
				label: "My First dataset",
				data: [90, 20, 30, 40, 10],
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                pointBackgroundColor: '#ff0000',
				
            },{
				label: "My Second dataset",
				data: [50, 20, 10, 30, 90],
                backgroundColor: 'rgb(255, 159, 64)',
                borderColor: 'rgb(255, 159, 64)',
                pointBackgroundColor: '#ff0000',
				
            }]
		},
		options: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Radar Chart'
            },
            scale: {
              ticks: {
                beginAtZero: true
              }
            }
        }
	});
	 /*----------------------------------------*/
	/*  3.  Doughnut Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("Doughnutchart");
	var Doughnutchart = new Chart(ctx, {
		type: 'radar',
		data: {
			labels: ["Red", "Orange", "Yellow", "Green", "Blue"],
			datasets: [{
				label: 'Dataset 1',
				data: [10, 20, 30, 40, 90],
                backgroundColor: 'rgb(255, 99, 132)'
				
            }]
		},
		options: {
            responsive: true,
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Doughnut Chart'
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
	});
	
	

	 
		
})(jQuery); 