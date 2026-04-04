<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" 
    crossorigin="anonymous">
  </head>
  <body>
    <h1 class="bg-warning p-2 text-white text-center">CRUD</h1>
    <br>

    <div class="container">
        <a href="turno.php" class="btn btn-danger">Volver a opciones</a>
    </div>
    <br>

    <!-- Botón para actualizar -->
    <button id="btnActualizar" class="btn btn-primary mb-3">
        <i class="bi bi-arrow-clockwise"></i> Actualizar Tabla
    </button>

    <div class="container bg-liggh p-3 border border-dark rounded">
    <h2 class="text-center">Cantidad de alumnos de primaria</h2>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped align-middle" id="tablaGrados">
            <thead class="table-dark text-center">
                <tr>
                    <th scope="col">ID</th>
                    <th>Grado</th>
                    <th>Sección A</th>
                    <th>Sección B</th>
                    <th>Total</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="tablaCuerpo">
                <tr>
                    <th scope="row">1</th>
                    <td>1°Grado</td>
                    <td contenteditable="true" class="editable">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row">2</th>
                    <td>2°Grado</td>
                    <td contenteditable="true">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row">3</th>
                    <td>3°Grado</td>
                    <td contenteditable="true">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row">4</th>
                    <td>4°Grado</td>
                    <td contenteditable="true">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row">5</th>
                    <td>5°Grado</td>
                    <td contenteditable="true">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row">6</th>
                    <td>6°Grado</td>
                    <td contenteditable="true">0</td>
                    <td contenteditable="true">0</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap 5.2 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<script>

    
    

        function calcularTotales() {
            const filas = document.querySelectorAll("#tablaGrados tbody tr");
            filas.forEach(fila => {
                let seccionA = parseFloat(fila.cells[2].innerText) || 0;
                let seccionB = parseFloat(fila.cells[3].innerText) || 0;
                let total = seccionA + seccionB;
                let subtotal = total + total;
                
                fila.cells[4].innerText = total;
                fila.cells[5].innerText = subtotal.toFixed(1);
            });
        }
        // Calcular al cargar
        calcularTotales();
        // Calcular cada vez que se edite una celda
        document.querySelectorAll("#tablaGrados td[contenteditable='true']").forEach(celda => {
            celda.addEventListener("input", calcularTotales);
            });
            </script>
            <br>

            


    </body>
</html>