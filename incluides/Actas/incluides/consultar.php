<?php
// modulos/actas/consultar.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();

require_once __DIR__.'/includes/actas_db.php';

$filtro = $_GET['filtro'] ?? '';
$pagina = max(1, filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1);
$por_pagina = 10;


// Configuración de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;


// Construir consulta según rol
$usuario_id = $_SESSION['user']['id'];
$rol = $_SESSION['user']['rol'];

if ($rol === 'CAPTURISTA') {
    $where = "WHERE (remitente_id = ? OR destinatario_id = ?)";
    $params = [$usuario_id, $usuario_id];
    $types = "ii";
} else {
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
}

// Aplicar filtros
if ($filtro) {
    $where .= " AND (folio LIKE ? OR asunto LIKE ? OR contenido LIKE ?)";
    $filtro_like = "%$filtro%";
    array_push($params, $filtro_like, $filtro_like, $filtro_like);
    $types .= str_repeat("s", 3);
}




// Obtener total de registros
$total_result = ejecutarConsulta("SELECT COUNT(*) as total FROM oficios $where", $params, $types);
$total = mysqli_fetch_assoc($total_result)['total'];
$total_paginas = ceil($total / $por_pagina);

// Obtener oficios para la página actual
$offset = ($pagina - 1) * $por_pagina;
$oficios = ejecutarConsulta(
    "SELECT o.id, o.folio, o.tipo, o.asunto, o.estado, o.fecha_creacion,
    u1.nombre_completo as remitente, u2.nombre_completo as destinatario
    FROM oficios o
    JOIN usuarios u1 ON o.remitente_id = u1.id
    JOIN usuarios u2 ON o.destinatario_id = u2.id
    $where
    ORDER BY o.fecha_creacion DESC
    LIMIT ? OFFSET ?",
    array_merge($params, [$por_pagina, $offset]),
    $types . "ii"
);


// Consulta para obtener actas
$sql = "SELECT o.id, o.folio, o.tipo, o.asunto, o.estado, o.fecha_creacion,
        u1.nombre_completo as remitente, u2.nombre_completo as destinatario
        FROM oficios o
        JOIN usuarios u1 ON o.remitente_id = u1.id
        JOIN usuarios u2 ON o.destinatario_id = u2.id
        $where
        ORDER BY o.fecha_creacion DESC
        LIMIT ? OFFSET ?";

$params = array_merge($params, [$porPagina, $offset]);
$types .= "ii";
$actas = ejecutarConsulta($sql, $params, $types);

// Contar total para paginación
$totalActas = ejecutarConsulta("SELECT COUNT(*) as total FROM oficios o $where", 
    array_slice($params, 0, count($params)-2), 
    substr($types, 0, -2));
$total = mysqli_fetch_assoc($totalActas)['total'];
$totalPaginas = ceil($total / $porPagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Actas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .card-acta { margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .acta-header { background-color: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #ddd; }
        .acta-body { padding: 15px; }
        .acta-footer { padding: 10px 15px; background-color: #f8f9fa; border-top: 1px solid #ddd; }
        .badge { padding: 5px 10px; border-radius: 3px; font-size: 14px; }
        .badge-pendiente { background-color: #ffc107; color: #212529; }
        .badge-recepcionado { background-color: #17a2b8; color: white; }
        .badge-respondido { background-color: #28a745; color: white; }
        .badge-finalizado { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-file-signature"></i> Actas de Entrega-Recepción</h2>
            <?php if (in_array($rol, ['SISTEMAS', 'ADMIN'])): ?>
            <a href="crear.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Acta
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="folio" class="form-control" placeholder="Buscar por folio" 
                            value="<?= htmlspecialchars($_GET['folio'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Listado de Actas -->
        <?php while ($acta = mysqli_fetch_assoc($actas)): ?>
        <div class="card card-acta">
            <div class="acta-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= htmlspecialchars($acta['folio']) ?></strong> - 
                    <?= htmlspecialchars($acta['tipo']) ?>
                </div>
                <span class="badge badge-<?= strtolower($acta['estado']) ?>">
                    <?= htmlspecialchars($acta['estado']) ?>
                </span>
            </div>
            <div class="acta-body">
                <h5><?= htmlspecialchars($acta['asunto']) ?></h5>
                <p class="mb-1"><strong>Remitente:</strong> <?= htmlspecialchars($acta['remitente']) ?></p>
                <p class="mb-1"><strong>Destinatario:</strong> <?= htmlspecialchars($acta['destinatario']) ?></p>
                <p class="mb-0"><strong>Fecha:</strong> <?= $acta['fecha_creacion'] ?></p>
            </div>
            <div class="acta-footer d-flex justify-content-end">
                <a href="detalle.php?id=<?= $acta['id'] ?>" class="btn btn-sm btn-outline-primary mr-2">
                    <i class="fas fa-eye"></i> Ver Detalle
                </a>
                <?php if ($rol !== 'CAPTURISTA' && $acta['estado'] === 'Pendiente'): ?>
                <a href="recepcionar.php?id=<?= $acta['id'] ?>" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-stamp"></i> Recepcionar
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= $pagina-1 ?>&<?= http_build_query($_GET) ?>">
                        Anterior
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&<?= http_build_query($_GET) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $totalPaginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= $pagina+1 ?>&<?= http_build_query($_GET) ?>">
                        Siguiente
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Oficios</title>
    <style>
        .tabla-oficios { width: 100%; border-collapse: collapse; }
        .tabla-oficios th, .tabla-oficios td { padding: 8px; border: 1px solid #ddd; }
        .tabla-oficios th { background-color: #f2f2f2; }
        .estado-pendiente { color: orange; }
        .estado-recepcionado { color: blue; }
        .estado-respondido { color: green; }
        .estado-finalizado { color: gray; }
        .paginacion { margin-top: 20px; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container">
        <h2>Consulta de Oficios</h2>
        
        <form method="get" class="filtro-form">
            <input type="text" name="filtro" placeholder="Buscar..." value="<?= htmlspecialchars($filtro) ?>">
            <button type="submit">Buscar</button>
        </form>
        
        <table class="tabla-oficios">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Tipo</th>
                    <th>Asunto</th>
                    <th>Remitente</th>
                    <th>Destinatario</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($oficio = mysqli_fetch_assoc($oficios)): ?>
                <tr>
                    <td><?= htmlspecialchars($oficio['folio']) ?></td>
                    <td><?= htmlspecialchars($oficio['tipo']) ?></td>
                    <td><?= htmlspecialchars($oficio['asunto']) ?></td>
                    <td><?= htmlspecialchars($oficio['remitente']) ?></td>
                    <td><?= htmlspecialchars($oficio['destinatario']) ?></td>
                    <td><?= $oficio['fecha_creacion'] ?></td>
                    <td class="estado-<?= strtolower($oficio['estado']) ?>">
                        <?= htmlspecialchars($oficio['estado']) ?>
                    </td>
                    <td>
                        <a href="detalle.php?id=<?= $oficio['id'] ?>">Ver</a>
                        <?php if ($rol !== 'CAPTURISTA' && $oficio['estado'] === 'Pendiente'): ?>
                        | <a href="recepcionar.php?id=<?= $oficio['id'] ?>">Recepcionar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="paginacion">
            <?php if ($pagina > 1): ?>
            <a href="?pagina=<?= $pagina - 1 ?>&filtro=<?= urlencode($filtro) ?>">Anterior</a>
            <?php endif; ?>
            
            Página <?= $pagina ?> de <?= $total_paginas ?>
            
            <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?= $pagina + 1 ?>&filtro=<?= urlencode($filtro) ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>