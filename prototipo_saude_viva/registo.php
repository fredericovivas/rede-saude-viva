<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
iniciarSessao();

if (estaLogado()) {
    header('Location: /saude_viva/dashboard/index.php');
    exit;
}

$erro   = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome            = trim($_POST['nome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmar       = $_POST['confirmar'] ?? '';
    $nif             = trim($_POST['nif'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $telefone        = trim($_POST['telefone'] ?? '');

    // Validações
    if (empty($nome) || empty($email) || empty($password) || empty($nif)) {
        $erro = 'Por favor preenche todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'O formato do email não é válido.';
    } elseif (strlen($password) < 8) {
        $erro = 'A password deve ter pelo menos 8 caracteres.';
    } elseif ($password !== $confirmar) {
        $erro = 'As passwords não coincidem.';
    } elseif (!preg_match('/^\d{9}$/', $nif)) {
        $erro = 'O NIF deve ter exatamente 9 dígitos.';
    } else {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Este email já está registado. Tenta fazer login.';
        } else {
            // Verificar se NIF já existe
            $stmt = $pdo->prepare("SELECT id FROM utentes WHERE nif = ?");
            $stmt->execute([$nif]);
            if ($stmt->fetch()) {
                $erro = 'Este NIF já está registado.';
            } else {
                // Criar utilizador
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO utilizadores (nome, email, password, papel)
                    VALUES (?, ?, ?, 'utente')
                ");
                $stmt->execute([$nome, $email, $hash]);
                $utilizador_id = $pdo->lastInsertId();

                // Criar perfil de utente
                $stmt = $pdo->prepare("
                    INSERT INTO utentes (utilizador_id, nif, data_nascimento, telefone)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $utilizador_id,
                    $nif,
                    $data_nascimento ?: null,
                    $telefone ?: null
                ]);

                $sucesso = 'Conta criada com sucesso! Podes fazer login agora.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar — Rede Saúde Viva</title>
    <link rel="stylesheet" href="/saude_viva/assets/css/style.css">
</head>
<body class="pagina-login">

    <div class="login-container" style="max-width:480px;">
        <div class="login-logo">
            <h1>🏥 Rede Saúde Viva</h1>
            <p>Criar nova conta</p>
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <?php if (!$sucesso): ?>
        <form method="POST" action="">
            <div class="campo">
                <label for="nome">Nome completo *</label>
                <input type="text" id="nome" name="nome"
                       placeholder="O teu nome completo"
                       value="<?= htmlspecialchars($nome ?? '') ?>"
                       required>
            </div>
            <div class="campo">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email"
                       placeholder="o.teu@email.pt"
                       value="<?= htmlspecialchars($email ?? '') ?>"
                       required>
            </div>
            <div class="campo">
                <label for="nif">NIF *</label>
                <input type="text" id="nif" name="nif"
                       placeholder="123456789"
                       maxlength="9"
                       value="<?= htmlspecialchars($nif ?? '') ?>"
                       required>
            </div>
            <div class="campo">
                <label for="data_nascimento">Data de Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento"
                       value="<?= htmlspecialchars($data_nascimento ?? '') ?>">
            </div>
            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone"
                       placeholder="912345678"
                       value="<?= htmlspecialchars($telefone ?? '') ?>">
            </div>
            <div class="campo">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password"
                       placeholder="Mínimo 8 caracteres" required>
            </div>
            <div class="campo">
                <label for="confirmar">Confirmar Password *</label>
                <input type="password" id="confirmar" name="confirmar"
                       placeholder="Repete a password" required>
            </div>
            <button type="submit" class="btn btn-primario btn-bloco">
                Criar Conta
            </button>
        </form>
        <?php endif; ?>

        <div class="login-rodape">
            <p>Já tens conta? <a href="/saude_viva/index.php">Fazer login</a></p>
        </div>
    </div>

</body>
</html>