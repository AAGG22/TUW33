<?php
session_start();
var_dump($_SESSION); // Esto te mostrará si las variables existen

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

// Verificar si se enviaron los datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    var_dump($_POST);
    
// Obtener la calificación y el comentario desde el formulario
    
    // Capturar si se presionó el botón "Omitir"
    $calificacion = isset($_POST['omitir']) ? 0 : (int)$_POST['rating'];
    //$calificacion = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
    $id_solicitante = isset($_POST['id_solicitante']) ? trim($_POST['id_solicitante']) : '';
    // Obtener el ID del envío
    $id_envio = isset($_POST['id_envio']) ? (int)$_POST['id_envio'] : 0;

    // Debugging: Imprimir el ID de envío recibido
    echo "ID de envío recibido: " . $id_envio . "<br>";

    // Verificar si el ID del envío es válido
    if ($id_envio <= 0) {
        die("ID de envío no válido.");
    }

    // Verificar si se ingresó una calificación válida
    if ($calificacion < 0 || $calificacion > 5) {
        die("Calificación no válida.");
    }

    // Insertar los datos en la base de datos
    $sql = "INSERT INTO calificacion_asolicitante (id_envio, calificacion, comentario, id_solicitante) VALUES (?, ?, ?, ?)";
    // Preparar la consulta
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("iiss", $id_envio, $calificacion, $comentario, $id_solicitante);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo "Calificación guardada correctamente.";

        // **Obtener las últimas calificaciones del solicitante**
        $sql_calificaciones = "
            SELECT calificacion 
            FROM calificacion_asolicitante 
            WHERE id_solicitante = ? 
            ORDER BY fecha DESC 
            LIMIT 5";
        $stmt_calificaciones = $conn->prepare($sql_calificaciones);
        $stmt_calificaciones->bind_param("s", $id_solicitante);
        $stmt_calificaciones->execute();
        $result_calificaciones = $stmt_calificaciones->get_result();

        $calificaciones = [];
        while ($row = $result_calificaciones->fetch_assoc()) {
            $calificaciones[] = $row['calificacion'];
        }

        // Calcular promedio de calificaciones del solicitante
        // Asignar el id_solicitante a id_usuario desde el principio
        $id_usuario = $id_solicitante; 

        // **Paso 1: Verificar 3 últimas calificaciones consecutivas con promedio < 40%**
        if (count($calificaciones) >= 3) {
            $ultimas_tres = array_slice($calificaciones, 0, 3); // Esto ya toma las más recientes
            $promedio_ultimas_3 = (array_sum($ultimas_tres) / count($ultimas_tres)) * 20;
            
            if ($promedio_ultimas_3 < 40) {
                $responsable = 0;
            } else {
                // **Paso 2: Verificar si cumple con 5 últimas calificaciones > 80%**
                if (count($calificaciones) == 5) {
                    $promedio_ultimas_5 = (array_sum($calificaciones) / 5) * 20;
                    $responsable = $promedio_ultimas_5 >= 80 ? 1 : 0;
                } else {
                    // **Paso 3: Si tiene entre 3-4 calificaciones y promedio > 80%**
                    $promedio = (array_sum($calificaciones) / count($calificaciones)) * 20;
                    $responsable = $promedio >= 80 ? 1 : 0;
                }
            }
        } else {
            // Si tiene menos de 3 calificaciones, no es responsable
            $responsable = 0;
        }
        // **Actualizar estado de responsabilidad del usuario**
        //var_dump($id_usuario);
        $sql_update = "UPDATE usuarios SET responsable = ? WHERE id_usuario = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("is", $responsable, $id_usuario);
        if ($stmt_update->execute()) {
            echo "Estado de responsabilidad actualizado correctamente.";
        } else {
            echo "Error al actualizar el estado de responsabilidad: " . $stmt_update->error;
        }


        // **Aquí comienza el código para penalizar calificaciones faltantes**

        // Fecha límite para agregar calificaciones (hace 1 semana)
        $fecha_limite = date('Y-m-d H:i:s', strtotime('-1 week'));

        // Consulta para obtener los id_solicitante de envios sin calificación
        $sql_penalizar = "
            SELECT e.id_solicitante 
            FROM envio e 
            LEFT JOIN calificacion_asolicitante c ON e.id_envio = c.id_envio
            WHERE e.fecha_envio <= ? AND c.calificacion = 0
            
        ";
        //WHERE e.fecha_envio <= ? AND c.id_envio IS NULL
        $stmt_penalizar = $conn->prepare($sql_penalizar);
        $stmt_penalizar->bind_param("s", $fecha_limite);
        $stmt_penalizar->execute();
        $resultado_penalizar = $stmt_penalizar->get_result();

        if ($resultado_penalizar->num_rows > 0) {
            while ($row = $resultado_penalizar->fetch_assoc()) {
                // Obtener id_solicitante del resultado
                $id_solicitante = $row['id_solicitante'];
                echo "Penalizando al solicitante con ID: $id_solicitante<br>";

                // **Asignar el id_solicitante a id_usuario**
                $id_usuario = $id_solicitante; // Asignar directamente aquí

                // Actualizar las calificaciones negativas para el usuario encontrado
                $sql_update_negativas = "
                    UPDATE usuarios 
                    SET calificaciones_negativas = calificaciones_negativas + 1 
                    WHERE id_usuario = ?
                ";
                $stmt_update_negativas = $conn->prepare($sql_update_negativas);
                $stmt_update_negativas->bind_param("s", $id_usuario);
                $stmt_update_negativas->execute();

                // Verificar si el usuario pierde la responsabilidad
                $sql_verificar_responsable = "
                    SELECT calificaciones_negativas 
                    FROM usuarios 
                    WHERE id_usuario = ?
                ";
                $stmt_verificar = $conn->prepare($sql_verificar_responsable);
                $stmt_verificar->bind_param("s", $id_usuario);
                $stmt_verificar->execute();
                $res_negativas = $stmt_verificar->get_result()->fetch_assoc();

                if ($res_negativas['calificaciones_negativas'] >= 2) {
                    $sql_perder_responsabilidad = "
                        UPDATE usuarios 
                        SET responsable = 0 
                        WHERE id_usuario = ?
                    ";
                    $stmt_perder_responsabilidad = $conn->prepare($sql_perder_responsabilidad);
                    $stmt_perder_responsabilidad->bind_param("s", $id_usuario);
                    $stmt_perder_responsabilidad->execute();
                }
            }
        } else {
            echo "No se encontraron envíos sin calificación.";
        }

    } else {
        echo "Error al guardar la calificación: " . $conn->error;
    }
    // Cerrar la consulta
    $stmt->close();
}

// Cerrar la conexión
$conn->close();
?>



