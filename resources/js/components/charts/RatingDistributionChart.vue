<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';

// Chart.js registrieren
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    data: {
        type: Object,
        required: true,
        default: () => ({
            1: 0,
            2: 0,
            3: 0,
            4: 0,
            5: 0
        })
    }
});

const chartData = computed(() => ({
    labels: ['1 ⭐', '2 ⭐', '3 ⭐', '4 ⭐', '5 ⭐'],
    datasets: [
        {
            label: 'Anzahl Bewertungen',
            data: [
                props.data[1] || 0,
                props.data[2] || 0,
                props.data[3] || 0,
                props.data[4] || 0,
                props.data[5] || 0
            ],
            backgroundColor: [
                'rgba(239, 68, 68, 0.8)',  // 1 Stern - Rot
                'rgba(249, 115, 22, 0.8)', // 2 Sterne - Orange
                'rgba(234, 179, 8, 0.8)',  // 3 Sterne - Gelb
                'rgba(132, 204, 22, 0.8)', // 4 Sterne - Hellgrün
                'rgba(34, 197, 94, 0.8)'   // 5 Sterne - Grün
            ],
            borderColor: [
                'rgb(239, 68, 68)',
                'rgb(249, 115, 22)',
                'rgb(234, 179, 8)',
                'rgb(132, 204, 22)',
                'rgb(34, 197, 94)'
            ],
            borderWidth: 1
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            },
            grid: {
                color: 'rgba(0, 0, 0, 0.05)'
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};
</script>

<template>
    <div class="h-[300px] w-full">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
