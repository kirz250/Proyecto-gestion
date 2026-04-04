<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botón con opciones - Bootstrap 5</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <br>
    <!-- Botón con menú desplegable -->
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            Opciones
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item" href="mañana.php">Mañana</a></li>
            <li><a class="dropdown-item" href="tarde.php">Tarde</a></li>
        </ul>
    </div>
    <br>
    
    <div class="container">
        <a href="paginaprincipal.php" class="btn btn-danger">Volver a la pagina principal</a>
    </div>

    <!-- Bootstrap JS (necesario para dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
