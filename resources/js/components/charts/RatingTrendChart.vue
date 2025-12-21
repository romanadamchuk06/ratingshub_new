<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

// Chart.js registrieren
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    data: {
        type: Object,
        required: true,
        default: () => ({
            labels: [],
            values: []
        })
    }
});

const chartData = computed(() => ({
    labels: props.data.labels || [],
    datasets: [
        {
            label: 'Durchschnittsbewertung',
            data: props.data.values || [],
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
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
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 5,
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
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
