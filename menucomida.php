<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Escolar Semanal</title>
    <!-- Bootstrap 5.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <br>
<div class="container">
        <a href="paginaprincipal.php" class="btn btn-danger">Volver a la pagina pricipal</a>
    </div>

<div class="container py-5">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Cálcular las porciones de alimentos</h4>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <label for="estudiantes" class="form-label">Cantidad de estudiantes</label>
        <input type="number" id="estudiantes" class="form-control" placeholder="Ej: 50">
      </div>

      <h5 class="mt-4">Agregar alimentos</h5>
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Nombre del alimento</label>
          <input type="text" id="nombreAlimento" class="form-control" placeholder="Ej: Arroz">
        </div>
        <div class="col-md-5">
          <label class="form-label">Ración por estudiante (gramos)</label>
          <input type="number" id="porcion" class="form-control" placeholder="Ej: 200">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="agregarAlimento()">Agregar</button>
        </div>
      </div>

      <hr>
      <h5>Lista de alimentos</h5>
      <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
          <tr>
            <th>Alimento</th>
            <th>Ración por estudiante (g)</th>
            <th>Total (g)</th>
            <th>Total (kg)</th>
          </tr>
        </thead>
        <tbody id="tablaAlimentos"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let alimentos = [];

function agregarAlimento() {
  const estudiantes = parseInt(document.getElementById('estudiantes').value) || 0;
  const nombre = document.getElementById('nombreAlimento').value.trim();
  const porcion = parseFloat(document.getElementById('porcion').value) || 0;

  if (!nombre || estudiantes <= 0 || porcion <= 0) {
    alert("Por favor, ingresa todos los datos correctamente.");
    return;
  }

  const totalGramos = estudiantes * porcion;
  const totalKilos = totalGramos / 1000;

  alimentos.push({ nombre, porcion, totalGramos, totalKilos });
  mostrarTabla();

  document.getElementById('nombreAlimento').value = '';
  document.getElementById('porcion').value = '';
}

function mostrarTabla() {
  const tabla = document.getElementById('tablaAlimentos');
  tabla.innerHTML = '';

  alimentos.forEach(alimento => {
    tabla.innerHTML += `
      <tr>
        <td>${alimento.nombre}</td>
        <td>${alimento.porcion}</td>
        <td>${alimento.totalGramos}</td>
        <td>${alimento.totalKilos.toFixed(2)}</td>
      </tr>
    `;
  });
}
</script>

<!-- Bootstrap 5.2 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

            
              
</body>
</html>

