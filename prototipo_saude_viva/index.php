<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
iniciarSessao();

// Se já está logado, redireciona para o dashboard
if (estaLogado()) {
    header('Location: /saude_viva/dashboard/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $erro = 'Por favor preenche todos os campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $utilizador = $stmt->fetch();

        if ($utilizador && password_verify($password, $utilizador['password'])) {
            session_regenerate_id(true);
            $_SESSION['utilizador_id'] = $utilizador['id'];
            $_SESSION['nome']          = $utilizador['nome'];
            $_SESSION['papel']         = $utilizador['papel'];
            header('Location: /saude_viva/dashboard/index.php');
            exit;
        } else {
            $erro = 'Email ou password incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rede Saúde Viva — Login</title>
    <link rel="stylesheet" href="/saude_viva/assets/css/style.css">
</head>
<body class="pagina-login">

    <div class="login-container">
        <div class="login-logo">
            <h1>🏥 Rede Saúde Viva</h1>
            <p>Sistema Integrado de Gestão Clínica</p>
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="o.teu@email.pt"
                       value="<?= htmlspecialchars($email ?? '') ?>"
                       required>
            </div>
            <div class="campo">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primario btn-bloco">
                Entrar
            </button>
        </form>

        <div class="login-rodape">
            <p>Ainda não tens conta? <a href="/saude_viva/registo.php">Registar</a></p>
        </div>
    </div>

</body>
</html>