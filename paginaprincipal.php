<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link href="https://unpkg.com/ionicons@4.5.10-0/dist/css/ionicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="styke.css">

    <title>P.A.E</title>
  </head>
  <body>
    <div class="d-flex">
        <div id="sidebar-container" class="bg-primary">
            <div class="logo">
                <h4 class="text-light font-weight-bold">Sistema de gestión<br> 
                    de alimentos "P.A.E"</h4>
            </div>

            <div class="menu">
              <div class="toggle-btn" onclick="toggleMenu()">☰ Menú</div>
              <div class="menu" id="menu">
                <a href="turno.php"><i class="icon ion-md-apps mr-2 lead"></i> Consultar menú</a>
                <a href="menucomida.php"><i class="icon ion-md-apps mr-2 lead"></i> Reportar necesidades</a>
                <a href="#"><i class="icon ion-md-apps mr-2 lead"></i> Visualizar el inventario</a>
                <a href="#"><i class="icon ion-md-apps mr-2 lead"></i> Administrar usuario</a>
              </div>
                
            </div>


        </div>

        <div class="w-95">
          <h2 class="set">U.E. "Nueva Toledo"</h2>

          <div class="cardBox">
            <div class="card">
              <div>
                <div class="numbers">Administrador <br>
                de menu</div>
              </div>
            </div>
            <div class="card2">
              <div>
                <div class="numbers">Administrador <br>
                 de menu</div>
              </div>
          </div>
          <div class="card3">
              <div>
                <div class="numbers">Administrador <br> 
                 de menu</div>
              </div>
        </div>
        <br>
        <div class="container">
        <a href="login.php" class="btn btn-secondary">Login</a>
        </div>


      </div>
      <!-- Optional JavaScript -->
      <!-- jQuery first, then Popper.js, then Bootstrap JS -->
      <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2
      +poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
      integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
      </script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"
      integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>
    <script>

<script>
    function toggleMenu() {
        document.getElementById("menu").classList.toggle("expandido");
    }
</script>
  </body>
</html>