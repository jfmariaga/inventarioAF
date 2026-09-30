import './bootstrap';

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
window.Chart = Chart;

const BRAND = '#0f766e'; // brand-700

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

window.Swal = Swal;

/** Toast de notificación (reemplaza los mensajes flash). */
window.toast = (icon, title) => Toast.fire({ icon: icon || 'success', title });

/** Previsualización de una foto en un modal grande (activos inventariados). */
window.verFoto = (src, titulo) => {
    Swal.fire({
        imageUrl: src,
        imageAlt: titulo || 'Foto del activo',
        title: titulo || null,
        confirmButtonColor: BRAND,
        confirmButtonText: 'Cerrar',
        width: 'auto',
    });
};

// --- Toasts disparados por componentes Livewire ---
document.addEventListener('livewire:init', () => {
    Livewire.on('toast', ({ icon, title }) => window.toast(icon, title));
});

// --- Gráficas (Chart.js) declaradas como <canvas data-chart data-chart-type="..." data-chart-payload="..."> ---
const chartInstances = new Map();

function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const existente = chartInstances.get(canvas);
        if (existente) existente.destroy();

        const tipo = canvas.dataset.chartType;
        const payload = JSON.parse(canvas.dataset.chartPayload || '{}');

        const chart = new Chart(canvas, {
            type: tipo,
            data: payload.data,
            options: payload.options || {},
        });

        chartInstances.set(canvas, chart);
    });
}

document.addEventListener('DOMContentLoaded', renderCharts);
document.addEventListener('livewire:navigated', renderCharts);
