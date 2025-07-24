<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Colaboradores</title>
    <style>
        .popup{ animation: transitionIn-Y-bottom 0.5s; }
        .sub-table{ animation: transitionIn-Y-bottom 0.5s; }
    </style>
</head>
<body>
<?php
session_start();

// Validar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

include("../connection.php");

// Opcional: mostrar nombre del admin logueado
$cedulaAdmin = $_SESSION['usuario'];
$adminName = "Administrador";

$query = $database->prepare("SELECT nombre FROM usuario WHERE cedula = ?");
$query->bind_param("s", $cedulaAdmin);
$query->execute();
$result = $query->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $adminName = $row['nombre'];
}
$query->close();
?>
    <div class="container">
        <div class="menu">
            <table class="menu-container" border="0">
                <tr>
                    <td style="padding:10px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px">
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title"><?php echo htmlspecialchars($adminName); ?></p>
                                    <p class="profile-subtitle"><?php echo htmlspecialchars($cedulaAdmin); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="../login.php"><input type="button" value="Cerrar sesión" class="logout-btn btn-primary-soft btn"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-dashbord menu-active menu-icon-dashbord-active">
                        <a href="index.php" class="non-style-link-menu non-style-link-menu-active">
                            <div><p class="menu-text">Dashboard</p></div>
                        </a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-doctor">
                        <a href="colaboradores.php" class="non-style-link-menu">
                            <div><p class="menu-text">Colaboradores</p></div>
                        </a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-patient">
                        <a href="pacientes.php" class="non-style-link-menu">
                            <div><p class="menu-text">Pacientes</p></div>
                        </a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="ingresos.php" class="non-style-link-menu">
                            <div><p class="menu-text">Ingresos</p></div>
                        </a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="dash-body">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
                <tr>
                    <td width="13%">
                        <a href="colaboradores.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">Volver</button></a>
                    </td>
                    <td>
                        <form action="" method="post" class="header-search">
                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Buscar por nombre o cédula" list="colaboradores">&nbsp;&nbsp;
                            <?php
                                echo '<datalist id="colaboradores">';
                                $list11 = $database->query("SELECT nombre, cedula FROM usuario WHERE rol='colaborador'");
                                while($row00=$list11->fetch_assoc()){
                                    echo "<option value='".$row00["nombre"]."'>";
                                    echo "<option value='".$row00["cedula"]."'>";
                                }
                                echo '</datalist>';
                            ?>
                            <input type="Submit" value="Buscar" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Fecha</p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php date_default_timezone_set('America/Guayaquil'); echo date('Y-m-d'); ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding-top:30px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Agregar Nuevo Colaborador</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add&id=none&error=0" class="non-style-link">
                            <button class="login-btn btn-primary btn button-icon" style="display: flex;justify-content: center;align-items: center;margin-left:75px;background-image: url('../img/icons/add.svg');">Agregar</button>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">
                            Todos los colaboradores (<?php
                                $list11 = $database->query("SELECT * FROM usuario WHERE rol='colaborador'");
                                echo $list11->num_rows;
                            ?>)
                        </p>
                    </td>
                </tr>

                <?php
                    if($_POST){
                        $keyword = $_POST["search"];
                        $sqlmain = "SELECT * FROM usuario WHERE rol='colaborador' AND (nombre LIKE '%$keyword%' OR cedula LIKE '%$keyword%')";
                    }else{
                        $sqlmain = "SELECT * FROM usuario WHERE rol='colaborador' ORDER BY id DESC";
                    }
                ?>

                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                            <thead>
                                <tr>
                                    <th class="table-headin">Nombre</th>
                                    <th class="table-headin">Cédula</th>
                                    <th class="table-headin">Rol</th>
                                    <th class="table-headin">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $result = $database->query($sqlmain);
                                if($result->num_rows==0){
                                    echo '<tr><td colspan="4"><br><center>
                                        <img src="../img/notfound.svg" width="25%">
                                        <p class="heading-main12">No se encontraron coincidencias</p>
                                        <a href="colaboradores.php"><button class="login-btn btn-primary-soft btn">Mostrar todos</button></a>
                                        </center><br></td></tr>';
                                }else{
                                    while($row=$result->fetch_assoc()){
                                        $id = $row["id"];
                                        $nombre = $row["nombre"];
                                        $cedula = $row["cedula"];
                                        $rol = ucfirst($row["rol"]);
                                        echo '<tr>
                                            <td>&nbsp;'.substr($nombre,0,30).'</td>
                                            <td>'.$cedula.'</td>
                                            <td>'.$rol.'</td>
                                            <td>
                                                <div style="display:flex;justify-content: center;">
                                                    <a href="?action=edit&id='.$id.'&error=0" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-edit">Editar</button></a>&nbsp;&nbsp;&nbsp;
                                                    <a href="?action=view&id='.$id.'" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view">Ver</button></a>&nbsp;&nbsp;&nbsp;
                                                    <a href="?action=drop&id='.$id.'&name='.$nombre.'" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-delete">Eliminar</button></a>
                                                </div>
                                            </td>
                                        </tr>';
                                    }
                                }
                            ?>
                            </tbody>
                        </table>
                        </div>
                        </center>
                   </td>
                </tr>
            </table>
        </div>
    </div>

    <?php
    if($_GET){
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='drop'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>¿Está seguro?</h2>
                        <a class="close" href="colaboradores.php">&times;</a>
                        <div class="content">Desea eliminar este registro<br>('.substr($nameget,0,40).').</div>
                        <div style="display:flex;justify-content:center;">
                            <a href="delete-colaborador.php?id='.$id.'" class="non-style-link"><button class="btn-primary btn">&nbsp;Sí&nbsp;</button></a>&nbsp;&nbsp;&nbsp;
                            <a href="colaboradores.php" class="non-style-link"><button class="btn-primary btn">&nbsp;No&nbsp;</button></a>
                        </div>
                    </center>
                </div>
            </div>';
        }elseif($action=='view'){
            $sqlmain= "SELECT * FROM usuario WHERE id='$id'";
            $result=$database->query($sqlmain);
            $row=$result->fetch_assoc();
            $nombre=$row["nombre"];
            $cedula=$row["cedula"];
            $rol=$row["rol"];
            $estado=$row["estado"] ? 'Activo' : 'Inactivo';
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2></h2><a class="close" href="colaboradores.php">&times;</a>
                        <div class="content">Detalles del colaborador</div>
                        <table width="80%" class="sub-table scrolldown" border="0">
                            <tr><td><p style="font-size:25px;">Detalles</p></td></tr>
                            <tr><td class="label-td"><label>Nombre:</label></td></tr>
                            <tr><td class="label-td">'.$nombre.'</td></tr>
                            <tr><td class="label-td"><label>Cédula:</label></td></tr>
                            <tr><td class="label-td">'.$cedula.'</td></tr>
                            <tr><td class="label-td"><label>Rol:</label></td></tr>
                            <tr><td class="label-td">'.$rol.'</td></tr>
                            <tr><td class="label-td"><label>Estado:</label></td></tr>
                            <tr><td class="label-td">'.$estado.'</td></tr>
                            <tr><td><a href="colaboradores.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a></td></tr>
                        </table>
                    </center>
                </div>
            </div>';
        }elseif($action=='add'){
            $error_1=$_GET["error"];
            $errorlist=array(
                '1'=>'<label style="color:red;">Ya existe un usuario con esa cédula.</label>',
                '2'=>'<label style="color:red;">Las contraseñas no coinciden.</label>',
                '0'=>'','4'=>'');
            if($error_1!='4'){
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <a class="close" href="colaboradores.php">&times;</a>
                            <div style="display:flex;justify-content:center;">
                                <div class="abc">
                                    <table width="80%" class="sub-table" border="0">
                                        <tr><td colspan="2">'.$errorlist[$error_1].'</td></tr>
                                        <tr><td><p style="font-size:25px;">Agregar Colaborador</p></td></tr>
                                        <form action="add-colaborador.php" method="POST" class="add-new-form">
                                        <tr><td class="label-td"><label>Nombre:</label></td></tr>
                                        <tr><td><input type="text" name="name" class="input-text" placeholder="Nombre completo" required></td></tr>
                                        <tr><td class="label-td"><label>Cédula:</label></td></tr>
                                        <tr><td><input type="text" name="cedula" class="input-text" placeholder="Cédula" required></td></tr>
                                        <tr><td class="label-td"><label>Correo:</label></td></tr>
                                        <tr><td><input type="email" name="correo" class="input-text" placeholder="correo@ejemplo.com" required></td></tr>
                                        <tr><td class="label-td"><label>Teléfono:</label></td></tr>
                                        <tr><td><input type="text" name="telefono" class="input-text" placeholder="Teléfono"></td></tr>
                                        <tr><td class="label-td"><label>Área de trabajo:</label></td></tr>
                                        <tr><td><input type="text" name="area_trabajo" class="input-text" placeholder="Ej: Terapia emocional"></td></tr>
                                        <tr><td class="label-td"><label>Contraseña:</label></td></tr>
                                        <tr><td><input type="password" name="password" class="input-text" placeholder="Contraseña" required></td></tr>
                                        <tr><td class="label-td"><label>Confirmar:</label></td></tr>
                                        <tr><td><input type="password" name="cpassword" class="input-text" placeholder="Repetir contraseña" required></td></tr>
                                        <tr><td colspan="2">
                                            <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;
                                            <input type="submit" value="Agregar" class="login-btn btn-primary btn">
                                        </td></tr>
                                        </form>
                                    </table>
                                </div>
                            </div>
                        </center>
                    </div>
                </div>';
            }else{
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup"><center><br>
                        <h2>¡Agregado con éxito!</h2>
                        <a class="close" href="colaboradores.php">&times;</a>
                        <div style="display:flex;justify-content:center;">
                            <a href="colaboradores.php" class="non-style-link"><button class="btn-primary btn">&nbsp;&nbsp;OK&nbsp;&nbsp;</button></a>
                        </div><br>
                    </center></div>
                </div>';
            }
        }elseif($action=='edit'){
            $sqlmain="SELECT * FROM usuario WHERE id='$id'";
            $result=$database->query($sqlmain);
            $row=$result->fetch_assoc();
            $nombre=$row["nombre"];
            $cedula=$row["cedula"];
            $error_1=$_GET["error"];
            $errorlist=array(
                '1'=>'<label style="color:red;">Ya existe un usuario con esa cédula.</label>',
                '2'=>'<label style="color:red;">Las contraseñas no coinciden.</label>',
                '0'=>'','4'=>'');
            if($error_1!='4'){
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <a class="close" href="colaboradores.php">&times;</a>
                            <div style="display:flex;justify-content:center;">
                                <div class="abc">
                                    <table width="80%" class="sub-table" border="0">
                                        <tr><td colspan="2">'.$errorlist[$error_1].'</td></tr>
                                        <tr><td><p style="font-size:25px;">Editar Colaborador</p><br>ID: '.$id.'</td></tr>
                                        <form action="edit-colaborador.php" method="POST">
                                        <tr><td class="label-td"><label>Nombre:</label></td></tr>
                                        <tr><td><input type="hidden" value="'.$id.'" name="id00">
                                               <input type="text" name="name" value="'.$nombre.'" class="input-text" required></td></tr>
                                        <tr><td class="label-td"><label>Cédula:</label></td></tr>
                                        <tr><td><input type="text" name="cedula" value="'.$cedula.'" class="input-text" required></td></tr>
                                        <tr><td class="label-td"><label>Nueva Contraseña:</label></td></tr>
                                        <tr><td><input type="password" name="password" class="input-text" placeholder="Nueva contraseña" required></td></tr>
                                        <tr><td class="label-td"><label>Confirmar:</label></td></tr>
                                        <tr><td><input type="password" name="cpassword" class="input-text" placeholder="Repetir contraseña" required></td></tr>
                                        <tr><td colspan="2">
                                            <input type="reset" value="Limpiar" class="login-btn btn-primary-soft btn">&nbsp;&nbsp;&nbsp;
                                            <input type="submit" value="Guardar" class="login-btn btn-primary btn">
                                        </td></tr>
                                        </form>
                                    </table>
                                </div>
                            </div>
                        </center>
                    </div>
                </div>';
            }else{
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup"><center><br>
                        <h2>¡Actualizado con éxito!</h2>
                        <a class="close" href="colaboradores.php">&times;</a>
                        <div style="display:flex;justify-content:center;">
                            <a href="colaboradores.php" class="non-style-link"><button class="btn-primary btn">&nbsp;&nbsp;OK&nbsp;&nbsp;</button></a>
                        </div><br>
                    </center></div>
                </div>';
            }
        }
    }
    ?>
</body>
</html>