<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Acceso al Sistema</h2>
    
    <form action="<?= base_url('login/autenticar') ?>" method="POST">
        <label>Usuario:</label>
        <input type="text" name="username" required>
        <br><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>