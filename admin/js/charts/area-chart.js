(function ($) {
 "use strict";
 
	 /*----------------------------------------*/
	/*  1.  Area Chart
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartfalse");
	var areachartfalse = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: false,
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [0, -20, 20, -20, 20, -20, 20]
            }]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			spanGaps: false,
			title:{
				display:true,
				text:'Area Chart Fill False'
			},
			elements: {
				line: {
					tension: 0.000001
				}
			},
			plugins: {
				filler: {
					propagate: false
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
	/*  1.  Area Chart origin
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartorigin");
	var areachartorigin = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: 'origin',
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [0, -20, 20, -20, 20, -20, 20]
            }]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			spanGaps: false,
			title:{
				display:true,
				text:'Area Chart Fill origin'
			},
			elements: {
				line: {
					tension: 0.000001
				}
			},
			plugins: {
				filler: {
					propagate: false
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
	/*  1.  Area Chart start
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartfillstart");
	var areachartfillstart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: 'start',
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [0, 10, 20, 30, 40, 50, 100]
            }]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			spanGaps: false,
			title:{
				display:true,
				text:'Area Chart Fill start'
			},
			elements: {
				line: {
					tension: 0.000001
				}
			},
			plugins: {
				filler: {
					propagate: false
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
	/*  1.  Area Chart end
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartend");
	var areachartend = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
				fill: 'end',
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [100, 90, 70, 60, 50, 40, 0]
            }]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			spanGaps: false,
			title:{
				display:true,
				text:'Area Chart Fill end'
			},
			elements: {
				line: {
					tension: 0.000001
				}
			},
			plugins: {
				filler: {
					propagate: false
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
	/*  1.  Area Chart Datasets
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartDatasets");
	var areachartDatasets = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: 'D0',
                backgroundColor: '#303030',
				borderColor: '#303030',
				data: [100, 90, 70, 60, 50, 40, 0]
            },{
				label: 'D1',
				fill: true,
                backgroundColor: '#03a9f4',
				borderColor: '#03a9f4',
				data: [100, 90, 70, 60, 50, 40, 0]
            },{
				label: 'D2',
				fill: true,
                backgroundColor: '#ff0000',
				borderColor: '#ff0000',
				data: [100, 90, 70, 60, 50, 40, 0]
            }]
		},
		options:{
			maintainAspectRatio: false,
			spanGaps: false,
			elements: {
				line: {
					tension: 0.000001
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
			},
			plugins: {
				filler: {
					propagate: false
				},
				samples_filler_analyser: {
					target: 'chart-analyser'
				}
			}
		}
	});
	
	 /*----------------------------------------*/
	/*  1.  Area Chart Legend
	/*----------------------------------------*/
	var ctx = document.getElementById("areachartLegend");
	var areachartLegend = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [{
				label: "My First dataset",
                backgroundColor: 'rgb(255, 99, 132)',
				borderColor: 'rgb(255, 159, 64)',
				data: [100, 90, 70, 60, 50, 40, 0],
				borderWidth: 1,
				pointStyle: 'rectRot',
				pointRadius: 5,
				pointBorderColor: 'rgb(0, 0, 0)'
            }]
		},
		options: {
			responsive: true,
			legend: {
				labels: {
					usePointStyle: false
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
			},
			title: {
				display: true,
				text: 'Normal Legend'
			}
		}
	});
	
	


	
		
})(jQuery); 