/* =========================
   LINE CHART
========================= */

new Chart(document.getElementById('lineChart'), {

    type: 'line',

    data: {

        labels: ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'],

        datasets: [{

            label: 'Sessions',

            data: [12,19,9,25,15,30,20],

            borderColor: '#2563EB',

            backgroundColor: 'rgba(37,99,235,0.2)',

            tension: 0.4,

            fill: true

        }]
    }
});

/* =========================
   PIE CHART
========================= */

new Chart(document.getElementById('pieChart'), {

    type: 'doughnut',

    data: {

        labels: [
            'Cash',
            'Mobile Money',
            'Carte'
        ],

        datasets: [{

            data: [60,30,10],

            backgroundColor: [
                '#16A34A',
                '#2563EB',
                '#F59E0B'
            ]
        }]
    }
});