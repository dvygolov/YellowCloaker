// Dashboard 1 Morris-chart

Morris.Area({
        element: 'morris-area-chart',
        data: [{
            period: '2012',
            Bags: 0,
            Shoes: 0,
            Jewelery: 0
        }, {
            period: '2013',
            Bags: 130,
            Shoes: 100,
            Jewelery: 80
        }, {
            period: '2014',
            Bags: 80,
            Shoes: 60,
            Jewelery: 70
        }, {
            period: '2015',
            Bags: 70,
            Shoes: 200,
            Jewelery: 160
        }, {
            period: '2016',
            Bags: 180,
            Shoes: 150,
            Jewelery: 120
        }, {
            period: '2017',
            Bags: 105,
            Shoes: 100,
            Jewelery: 90
        },
         {
            period: '2018',
            Bags: 250,
            Shoes: 150,
            Jewelery: 200
        }],
        xkey: 'period',
        ykeys: ['Bags', 'Shoes', 'Jewelery'],
        labels: ['Bags', 'Shoes', 'Jewelery'],
        pointSize: 0,
        fillOpacity: 0.6,
        pointStrokeColors:['#f75b36', '#00b5c2 ', '#008efa'],
        behaveLikeLine: true,
        gridLineColor: '#e0e0e0',
        lineWidth:0,
        hideHover: 'auto',
        lineColors: ['#f75b36', '#00b5c2 ', '#008efa'],
        resize: true
        
    });

Morris.Area({
        element: 'extra-area-chart',
        data: [{
                    period: '2012',
                    Bags: 0,
                    Shoes: 0,
                    Jewelery: 0
                }, {
                    period: '2013',
                    Bags: 50,
                    Shoes: 15,
                    Jewelery: 5
                }, {
                    period: '2014',
                    Bags: 20,
                    Shoes: 50,
                    Jewelery: 65
                }, {
                    period: '2015',
                    Bags: 60,
                    Shoes: 12,
                    Jewelery: 7
                }, {
                    period: '2016',
                    Bags: 30,
                    Shoes: 20,
                    Jewelery: 120
                }, {
                    period: '2017',
                    Bags: 25,
                    Shoes: 80,
                    Jewelery: 40
                }, {
                    period: '2018',
                    Bags: 10,
                    Shoes: 10,
                    Jewelery: 10
                }


                ],
                lineColors: ['#f75b36', '#00b5c2', '#8698b7'],
                xkey: 'period',
                ykeys: ['Bags', 'Shoes', 'Jewelery'],
                labels: ['Bags', 'Shoes', 'Jewelery'],
                pointSize: 0,
                lineWidth: 0,
                resize:true,
                fillOpacity: 0.8,
                behaveLikeLine: true,
                gridLineColor: '#e0e0e0',
                hideHover: 'auto'
        
    });
