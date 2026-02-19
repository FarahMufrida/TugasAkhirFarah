import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import Chart from "chart.js/auto";

document.addEventListener("DOMContentLoaded", function () {

    /* ================= PIE CHART ================= */
    const pieCtx = document.getElementById("pieChart");

    if (pieCtx) {
        new Chart(pieCtx, {
            type: "pie",
            data: {
                labels: ["Positif", "Negatif", "Netral"],
                datasets: [{
                    data: [68, 20, 12],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }

    /* ================= BAR CHART ================= */
    const barCtx = document.getElementById("barChart");

    if (barCtx) {
        new Chart(barCtx, {
            type: "bar",
            data: {
                labels: ["Papuma", "Watu Ulo", "Sukorambi", "Rembangan"],
                datasets: [
                    {
                        label: "Positif",
                        data: [20, 15, 25, 18],
                    },
                    {
                        label: "Negatif",
                        data: [10, 5, 12, 8],
                    },
                    {
                        label: "Netral",
                        data: [10, 5, 8, 4],
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

});
