(function ($) {
 "use strict";
	 /*----------------------------------------*/
	/*  1.  Bar Chart
	/*----------------------------------------*/

	var ctx = document.getElementById("barchart1");
	var barchart1 = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ["Red", "Blue", "Yellow", "Green", "Purple", "Orange"],
			datasets: [{
				label: 'Bar Chart',
				data: [12, 19, 3, 5, 2, 3],
				backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
				borderWidth: 1
			}]
		},
		options: {
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}],
				yAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}]
			}
		}
	});
	/*----------------------------------------*/
	/*  2.  Bar Chart vertical
	/*----------------------------------------*/
	var ctx = document.getElementById("barchart2");
	var barchart2 = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
                label: 'Dataset 1',
				data: [12, 19, 3, 5, 2, 3, 9],
				borderWidth: 1,
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
            }, {
                label: 'Dataset 2',
				data: [-3, -6, -5, -9, -15, -20],
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
				borderWidth: 1
            }]
		},
		options: {
			responsive: true,
			legend: {
				position: 'top',
			},
			title: {
				display: true,
				text: 'Bar Chart Vertical'
			},
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}],
				yAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}]
			}
		}
	});
	/*----------------------------------------*/
	/*  3.  Bar Chart Horizontal
	/*----------------------------------------*/
	var ctx = document.getElementById("barchart3");
	var barchart3 = new Chart(ctx, {
		type: 'horizontalBar',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
                label: 'Dataset 1',
				data: [12, 19, 3, 5, 2, 3, 9],
				borderWidth: 1,
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
            }, {
                label: 'Dataset 2',
				data: [-3, -6, -5, -9, -15, -20],
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
				borderWidth: 1
            }]
		},
		options: {
			responsive: true,
			legend: {
				position: 'top',
			},
			title: {
				display: true,
				text: 'Bar Chart horizontal'
			},
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}],
				yAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}]
			}
		}
	});
	
	/*----------------------------------------*/
	/*  4.  Bar Chart Multi axis
	/*----------------------------------------*/
	var ctx = document.getElementById("barchart4");
	var barchart4 = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
                label: 'Dataset 1',
				data: [12, 19, 3, 5, 2, 3, 9],
				borderWidth: 1,
				yAxisID: "y-axis-1",
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
            }, {
                label: 'Dataset 2',
				data: [-3, -6, -5, -9, -15, -20],
				borderWidth: 1,
				yAxisID: "y-axis-2",
                backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255,99,132,1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
				
            }]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:"Bar Chart Multi Axis"
			},
			tooltips: {
				mode: 'index',
				intersect: true
			},
			scales: {
				yAxes: [{
					type: "linear",
					display: true,
					position: "left",
					id: "y-axis-1",
				}, {
					type: "linear",
					display: true,
					position: "right",
					id: "y-axis-2",
					gridLines: {
						drawOnChartArea: false
					}
				}],
			}
		}
	});
	
	/*----------------------------------------*/
	/*  5.  Bar Chart Stacked
	/*----------------------------------------*/
	
	var ctx = document.getElementById("barchart5");
	var barchart5 = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
                label: 'Dataset 1',
                backgroundColor: '#303030',
				data: [12, 19, 3, -5, -2, 3, 9]
            }, {
                label: 'Dataset 2',
                backgroundColor: '#03a9f4',
				data: [-10, 15, -7, 7, -2, 4, 9]
				
            }, {
                label: 'Dataset 3',
               backgroundColor: '#FFC13B',
			   data: [15, -18, 3, 6, 5, -3, 7]
				
            }]
		},
		options: {
			title:{
				display:true,
				text:"Bar Chart Stacked"
			},
			tooltips: {
				mode: 'index',
				intersect: false
			},
			responsive: true,
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}],
				yAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					}
				}]
			}
		}
	});
	
	
		
		
})(jQuery); 