<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
    <title>Login - Yurem Terapias</title>
</head>
<body>
<?php
session_start();

$_SESSION["usuario"] = "";
$_SESSION["rol"] = "";

date_default_timezone_set('America/Guayaquil');
$_SESSION["fecha"] = date('Y-m-d');

include("connection.php");

if ($_POST) {
    $cedula = $_POST['cedula'];
    $contraseña = $_POST['contraseña'];

    $error = '<label for="promter" class="form-label"></label>';

    $result = $database->query("SELECT * FROM usuario WHERE cedula='$cedula'");
    if ($result->num_rows == 1) {
        $data = $result->fetch_assoc();
        if (password_verify($contraseña, $data['contraseña'])) {
            $_SESSION['usuario'] = $cedula;
            $_SESSION['rol'] = $data['rol'];

            if ($data['rol'] === 'administrador') {
                header('Location: admin/index.php');
            } elseif ($data['rol'] === 'colaborador') {
                header('Location: colaborador/index.php');
            }
        } else {
            $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">⚠️ Contraseña incorrecta</label>';
        }
    } else {
        $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">🚫 Cédula no registrada</label>';
    }
} else {
    $error = '<label for="promter" class="form-label">&nbsp;</label>';
}
?>

<center>
    <div class="container">
        <table border="0" style="margin: 0;padding: 0;width: 60%;">
            <tr>
                <td>
                    <p class="header-text">Bienvenido <br> Yurem Terapias</p>
                </td>
            </tr>
        <div class="form-body">
            <tr>
                <td>
                    <p class="sub-text">Ingrese su cédula y contraseña para continuar</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                    <td class="label-td">
                        <label for="cedula" class="form-label">Cédula:</label>
                    </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="text" name="cedula" class="input-text" placeholder="Número de cédula" required>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <label for="contraseña" class="form-label">Contraseña:</label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="password" name="contraseña" class="input-text" placeholder="Contraseña" required>
                </td>
            </tr>
            <tr>
                <td><br>
                    <?php echo $error ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" value="Iniciar Sesión" class="login-btn btn-primary btn">
                </td>
            </tr>
        </div>
            <tr>
                
            </tr>
                </form>
        </table>
    </div>
</center>
</body>
</html>