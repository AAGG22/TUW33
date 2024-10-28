
<?php
session_start();
//var_dump($_SESSION); // Esto te mostrará si las variables existen

// Conectar a la base de datos
$host = "localhost";  // Servidor de la base de datos
$dbname = "calificacion";  // Nombre de la base de datos
$username = "user_vuelos";  // Usuario de la base de datos
$password = "030817Fs";  // Contraseña de la base de datos

// Crear conexión
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de envío
$id_envio = isset($_GET['id_envio']) ? (int)$_GET['id_envio'] : 0;

// Obtener el ID de solicitante
$id_solicitante = isset($_SESSION['id_solicitante']) ? $_SESSION['id_solicitante'] : 0;

// Obtener el ID de publicación a partir del ID de envío
$sql_publicacion = "SELECT id_publicacion, id_solicitante, id_postulante FROM envio WHERE id_envio = ?";
$stmt_publicacion = $conn->prepare($sql_publicacion);
$stmt_publicacion->bind_param("i", $id_envio);
$stmt_publicacion->execute();
$result_publicacion = $stmt_publicacion->get_result();

if ($result_publicacion->num_rows > 0) {
    $data = $result_publicacion->fetch_assoc();
    $id_publicacion = $data['id_publicacion'];
    $id_solicitante = $data['id_solicitante'];
    $id_postulante = $data['id_postulante'];
} else {
    die("No se encontró la publicación para el ID de envío: " . htmlspecialchars($id_envio));
}

// Obtener el nombre del solicitante

$sql_nombre = "SELECT nombre FROM usuarios WHERE id_usuario = ?";
$stmt_nombre = $conn->prepare($sql_nombre);
$stmt_nombre->bind_param("s", $id_solicitante);
$stmt_nombre->execute();
$result_nombre = $stmt_nombre->get_result();
$nombre_solicitante = $result_nombre->fetch_assoc()['nombre'];

// Mostrar el formulario
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificación a solicitante</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Oregano:ital@0;1&display=swap');
        body {
            font-family: Arial, sans-serif;
            font-family: "Oregano", cursive;
            font-size: 20px;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            
        }
        header {
            text-align: center;
            background-color: #615778;
            background: linear-gradient(#e66465, #9198e5);
            background: linear-gradient(to right, #615778, #B3A1DE);
            color: white;
            padding: 20px;
        }
        article {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 8px rgba(128, 0, 128, 0.5);  /* Sombra púrpura */
            max-width: 600px;
            margin: 20px auto; /* Centra horizontalmente el article */
            text-align: center; /* Centra el contenido dentro del article */
            position: relative; /* Para posicionar el vehículo relativo al contenedor */
        }
        img {
            display: block;
            max-width: 100px;
            margin: 10px auto;
        }
        h2 {
            text-align: center;
        }
        .calificacion {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .calificacion input[type="radio"] {
            display: none;
        }
        .calificacion label {
            font-size: 30px;
            color: gray;
            cursor: pointer;
        }
        .calificacion input[type="radio"]:checked ~ label {
            color: gold;
        }
        textarea {
            width: 94%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        input[type="submit"] {
            display: block;
            width: 100%;
            background-color: #007bff;
            background: linear-gradient(to right, #615778, #B3A1DE);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
        }
        .contenedor-imagen {
            position: relative; /* Hace que el contenedor sea el punto de referencia para el vehículo */
            display: inline-block; /* Mantiene las imágenes juntas */
        }
        .imagen-postulante {
            max-width: 250px;
            display: block;
        }
        .imagen-vehiculo {
            position: absolute;
            bottom: -20px; /* Posiciona la imagen en la parte inferior */
            right: -35px;  /* Posiciona la imagen en la esquina derecha */
            max-width: 120px;
            border-radius: 50%; /* Hace la imagen del vehículo circular, si lo prefieres */
        }
        .error {
            color: red;
            display: none; /* Oculta el mensaje por defecto */
        }
    </style>
</head>
<body>
    <header>
        <h1>Califica tu experiencia</h1>
        <h2>Publicación N°: <?php echo htmlspecialchars($id_publicacion); ?></h2>
    </header>
    <article>
        <!-- Fecha con hora dinámica -->
        <h2><span id="fecha-hora"></span></h2>
         <!-- Contenedor que agrupa las imágenes del postulante y del vehículo -->
         <div class="contenedor-imagen">
            <img src="/imagenes/solicitante.jpg" alt="Imagen del empleado" class="imagen-postulante" />
            <img src="/imagenes/paquete.jpg" alt="Transporte del delivery" class="imagen-vehiculo" />
        </div>
        <p><strong>Nombre del solicitante:</strong> <?php echo htmlspecialchars($nombre_solicitante); ?></p>
        
        <form action="procesar_calificacionaS.php" method="POST" ><!-- onsubmit="return validarComentario()" -->
        <input type="hidden" name="id_envio" value="<?php echo $id_envio; ?>">
        <input type="hidden" name="id_solicitante" value="<?php echo $id_solicitante; ?>">
        <input type="hidden" name="id_postulante" value="<?php echo $id_postulante; ?>">
        <h3>Calificación:</h3>
        <div class="calificacion">
            <input type="radio" name="rating" id="star1" value="5" >
            <label for="star1">&#9733;</label>
            <input type="radio" name="rating" id="star2" value="4">
            <label for="star2">&#9733;</label>
            <input type="radio" name="rating" id="star3" value="3">
            <label for="star3">&#9733;</label>
            <input type="radio" name="rating" id="star4" value="2">
            <label for="star4">&#9733;</label>
            <input type="radio" name="rating" id="star5" value="1">
            <label for="star5">&#9733;</label>
        </div>
       
        <label for="comentario">Deja tu comentario:</label>
            <textarea name="comentario" id="comentario" rows="4" placeholder="Escribe tu comentario aquí..." ></textarea>
            <div class="error" id="error-message">Debes escribir al menos 2 palabras.</div>
        <input type="submit" value="Enviar calificación" style="font-family: Oregano, cursive;font-size: 20px;">
        <input type="submit" value="Omitir por ahora" name="omitir" >Omitir por ahora</input>    <!-- onclick="window.location.href='publicaciones.php'" -->
    </form>
    </article>
    
    <script>
        // Obtener la fecha y hora actual
        function obtenerFechaHora() {
            const fecha = new Date();
            const opciones = { 
                year: 'numeric', month: 'long', day: 'numeric', 
                hour: 'numeric', minute: 'numeric', second: 'numeric' 
            };
            const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
            document.getElementById('fecha-hora').textContent = fechaFormateada;
        }

        // Actualizar el elemento al cargar la página
        window.onload = obtenerFechaHora;

        function contarLetras(texto) {
            // Elimina los espacios en blanco y cuenta solo letras
            texto = texto.replace(/\s+/g, ''); // Elimina todos los espacios
            return texto.length; // Devuelve la longitud del texto sin espacios
        }

        function validarComentario() {
            const textarea = document.getElementById('comentario');
            const errorMessage = document.getElementById('error-message');
            const minimoLetras = 7;  // Define el mínimo de letras

            const numLetras = contarLetras(textarea.value);

            if (numLetras < minimoLetras) {
                errorMessage.style.display = 'block';  // Muestra el mensaje de error
                return false;  // Evita el envío del formulario
            } else {
                errorMessage.style.display = 'none';  // Oculta el mensaje de error
                return true;  // Permite el envío del formulario
            }
        }
    </script>
</body>
</html>
