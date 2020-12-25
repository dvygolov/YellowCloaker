(function ($) {
 "use strict";
 
	 /*----------------------------------------*/
	/*  1.  Basic Line Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("basiclinechart");
	var basiclinechart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [3, -5, -2, 3, 9, 12, 19]
            }, {
                label: "My Second dataset",
				fill: false,
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [-12, -3, -4, 6, 3, 7, 10]
				
		}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Basic Line Chart'
			},
			tooltips: {
				mode: 'index',
				intersect: false,
			},
			hover: {
				mode: 'nearest',
				intersect: true
			},
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					},
					scaleLabel: {
						display: true,
						labelString: 'Month'
					}
				}],
				yAxes: [{
					ticks: {
						autoSkip: false,
						maxRotation: 0
					},
					ticks: {
					  fontColor: "#fff", // this here
					},
					scaleLabel: {
						display: true,
						labelString: 'Value'
					}
				}]
			}
		}
	});
	
	 /*----------------------------------------*/
	/*  2.  Line Chart Multi axis
	/*----------------------------------------*/
	var ctx = document.getElementById("linechartmultiaxis");
	var linechartmultiaxis = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [3, -5, -2, 3, 9, 12, 19],
				yAxisID: "y-axis-1"
            }, {
                label: "My Second dataset",
				fill: false,
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [-12, -3, -4, 6, 3, 7, -20],
				yAxisID: "y-axis-2"
				
		}]
		},
		options: {
			responsive: true,
			hoverMode: 'index',
			stacked: false,
			title:{
				display: true,
				text:'Line Chart Multi Axis'
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
						drawOnChartArea: false,
					},
				}],
			}
		}
	});
	
	 /*----------------------------------------*/
	/*  3.  Line Chart stepped
	/*----------------------------------------*/
	var ctx = document.getElementById("linechartstepped");
	var linechartstepped = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6'],
			datasets: [{
				label: "steppedLine",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [3, -5, -2, 3, 9, 12, 19]
            }]
		},
		options: {
			responsive: true,
			title: {
				display: true,
				text:'Line Chart stepped',
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
	/*  4.  Line Chart Interpolation
	/*----------------------------------------*/
	
	var ctx = document.getElementById("linechartinterpolation");
	var linechartinterpolation = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
			datasets: [{
				label: "Cubic interpolation",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [0, 15, 17, 200, 0, 12, -200, 5, 200, 8, 200, 12, 200],
				cubicInterpolationMode: 'monotone'
            }, {
                label: "Cubic interpolation",
				fill: false,
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [-100, 200, 12, -200, 12, 200, 8, -200, 9, 200, -200, -12, -200]
				
		}, {
                label: "Linear interpolation",
				fill: false,
                backgroundColor: '#ff0000',
				borderColor: '#ff0000',
				data: [-8, -9, -10, -11, 0, 0, 0, 12, 10, 8, 9, 7, 12],
				lineTension: 0
				
		}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Line Chart interpolation'
			},
			tooltips: {
				mode: 'index'
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
	/*  5.  Line Chart styles
	/*----------------------------------------*/
	
	var ctx = document.getElementById("linechartstyles");
	var linechartstyles = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "Unfilled",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [0, 15, 17, 200, 0, 12, -200, 5]
            }, {
                label: "Dashed",
				fill: false,
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				borderDash: [5, 5],
				data: [-100, 200, 12, -200, 12, 200, 8]
				
		}, {
                label: "Filled",
				fill: true,
                backgroundColor: '#ff0000',
				borderColor: '#ff0000',
				data: [-200, -9, 200, -11, 0, -200, 0]
				
		}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Line Chart Style'
			},
			tooltips: {
				mode: 'index',
				intersect: false,
			},
			hover: {
				mode: 'nearest',
				intersect: true
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
	/*  6.  Line Chart point circle
	/*----------------------------------------*/
	
	var ctx = document.getElementById("linechartpointcircle");
	var linechartpointcircle = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [0, 10, 20, 30, 40, 50, 60],
				fill: false,
				pointRadius: 4,
				pointHoverRadius: 10,
				showLine: false 
			}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Line Chart Point Circle'
			},
			legend: {
				display: false
			},
			elements: {
				point: {
					pointStyle: 'circle',
				}
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
	/*  6.  Line Chart point rectRot
	/*----------------------------------------*/
	
	var ctx = document.getElementById("linechartpointrectRot");
	var linechartpointrectRot = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [60, 50, 40, 30, 20, 10, 0],
				fill: false,
				pointRadius: 6,
				pointHoverRadius: 10,
				showLine: false 
			}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Line Chart Point rectRot'
			},
			legend: {
				display: false
			},
			elements: {
				point: {
					pointStyle: 'rectRot',
				}
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
	/*  6.  Line Chart point cross
	/*----------------------------------------*/
	
	var ctx = document.getElementById("linechartpointcross");
	var linechartpointcross = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [0, 10, 60, 30, 0, 80, 60],
				fill: false,
				pointRadius: 6,
				pointHoverRadius: 10,
				showLine: false 
			}]
		},
		options: {
			responsive: true,
			title:{
				display:true,
				text:'Line Chart Point cross'
			},
			legend: {
				display: false
			},
			elements: {
				point: {
					pointStyle: 'cross',
				}
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
	
	
		
})(jQuery); 