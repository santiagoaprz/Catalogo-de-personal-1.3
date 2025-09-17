<?php
// modulos/actas/dashboard.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();
require_once __DIR__.'/includes/actas_db.php';

$usuario_id = $_SESSION['user']['id'];
$rol = $_SESSION['user']['rol'];

// Estadísticas según rol
if ($rol === 'CAPTURISTA') {
    $estadisticas = ejecutarConsulta(
        "SELECT estado, COUNT(*) as total 
         FROM oficios 
         WHERE remitente_id = ? OR destinatario_id = ?
         GROUP BY estado",
        [$usuario_id, $usuario_id],
        "ii"
    );
} else {
    $estadisticas = ejecutarConsulta(
        "SELECT estado, COUNT(*) as total FROM oficios GROUP BY estado"
    );
}
?>

<div class="col-md-6">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-file-signature"></i> Resumen de Actas</h5>
        </div>
        <div class="card-body">
            <canvas id="chartActas" height="200"></canvas>
            
            <div class="mt-3">
                <a href="modulos/actas/consultar.php" class="btn btn-sm btn-outline-primary">
                    Ver todas las actas
                </a>
                <?php if (in_array($rol, ['SISTEMAS', 'ADMIN'])): ?>
                <a href="modulos/actas/crear.php" class="btn btn-sm btn-primary ml-2">
                    <i class="fas fa-plus"></i> Nueva Acta
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfica de estado de actas
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartActas').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php 
                $labels = [];
                $data = [];
                $colors = [];
                while ($row = mysqli_fetch_assoc($estadisticas)) {
                    $labels[] = "'".$row['estado']."'";
                    $data[] = $row['total'];
                   
                }
                echo implode(', ', $labels);
            ?>],
            datasets: [{
                data: [<?= implode(', ', $data) ?>],
                backgroundColor: [<?= "'".implode("','", $colors)."'" ?>],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

function getEstadoColor(estado) {
    const colores = {
        'Pendiente': '#ffc107',
        'Recepcionado': '#17a2b8',
        'Respondido': '#28a745',
        'Finalizado': '#6c757d'
    };
    return colores[estado] || '#cccccc';
}
</script>