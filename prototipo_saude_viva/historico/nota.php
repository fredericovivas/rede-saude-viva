<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePapel(['medico']);

$consulta_id = $_GET['consulta_id'] ?? '';
$erro    = '';
$sucesso = '';

if (!$consulta_id) {
    header('Location: /saude_viva/dashboard/agenda.php');
    exit;
}

// Buscar dados da consulta
$stmt = $pdo->prepare("
    SELECT c.id, c.estado, h.data, h.hora_inicio,
           u.nome AS utente_nome, ut.nif, ut.telefone
    FROM consultas c
    JOIN horarios h ON c.horario_id = h.id
    JOIN utentes ut ON c.utente_id = ut.id
    JOIN utilizadores u ON ut.utilizador_id = u.id
    JOIN medicos m ON c.medico_id = m.id
    WHERE c.id = ? AND m.utilizador_id = ?
");
$stmt->execute([$consulta_id, $_SESSION['utilizador_id']]);
$consulta = $stmt->fetch();

if (!$consulta) {
    header('Location: /saude_viva/dashboard/agenda.php');
    exit;
}

// Verificar se já existe nota
$stmt = $pdo->prepare("SELECT * FROM notas_clinicas WHERE consulta_id = ?");
$stmt->execute([$consulta_id]);
$nota_existente = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $sintomas    = trim($_POST['sintomas']    ?? '');
    $tratamento  = trim($_POST['tratamento']  ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (empty($diagnostico)) {
        $erro = 'O diagnóstico é obrigatório.';
    } else {
        if ($nota_existente) {
            // Verificar se ainda está dentro de 24h
            $criado = strtotime($nota_existente['criado_em']);
            if ((time() - $criado) > 86400) {
                $erro = 'Não é possível editar notas com mais de 24 horas.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE notas_clinicas
                    SET diagnostico=?, sintomas=?, tratamento=?, observacoes=?
                    WHERE consulta_id=?
                ");
                $stmt->execute([$diagnostico,$sintomas,$tratamento,$observacoes,$consulta_id]);
                $sucesso = 'Nota clínica atualizada com sucesso.';
                $nota_existente = $stmt->fetch();
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO notas_clinicas
                (consulta_id, diagnostico, sintomas, tratamento, observacoes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$consulta_id,$diagnostico,$sintomas,$tratamento,$observacoes]);

            // Marcar consulta como realizada
            $stmt = $pdo->prepare("UPDATE consultas SET estado='realizada' WHERE id=?");
            $stmt->execute([$consulta_id]);

            $sucesso = 'Nota clínica registada com sucesso.';
        }

        // Recarregar nota
        $stmt = $pdo->prepare("SELECT * FROM notas_clinicas WHERE consulta_id = ?");
        $stmt->execute([$consulta_id]);
        $nota_existente = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Clínica — Rede Saúde Viva</title>
    <link rel="stylesheet" href="/saude_viva/assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h2>🏥 Saúde Viva</h2>
            <p><?= htmlspecialchars($_SESSION['nome']) ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="/saude_viva/dashboard/index.php">🏠 Início</a>
            <a href="/saude_viva/dashboard/agenda.php">📅 Minha Agenda</a>
            <a href="/saude_viva/consultas/lista.php">📋 Consultas</a>
            <a href="/saude_viva/historico/index.php" class="ativo">📁 Histórico Utentes</a>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>📝 Nota Clínica</h1>
            <p>
                <?= htmlspecialchars($consulta['utente_nome']) ?> —
                <?= date('d/m/Y', strtotime($consulta['data'])) ?>
                às <?= substr($consulta['hora_inicio'], 0, 5) ?>
            </p>
        </div>

        <!-- Info do utente -->
        <div class="card" style="margin-bottom:20px; display:flex; gap:32px;">
            <div>
                <div style="font-size:12px;color:#666;">Utente</div>
                <div style="font-weight:600;"><?= htmlspecialchars($consulta['utente_nome']) ?></div>
            </div>
            <div>
                <div style="font-size:12px;color:#666;">NIF</div>
                <div style="font-weight:600;"><?= htmlspecialchars($consulta['nif']) ?></div>
            </div>
            <?php if ($consulta['telefone']): ?>
            <div>
                <div style="font-size:12px;color:#666;">Telefone</div>
                <div style="font-weight:600;"><?= htmlspecialchars($consulta['telefone']) ?></div>
            </div>
            <?php endif; ?>
            <div>
                <div style="font-size:12px;color:#666;">Estado da Consulta</div>
                <span class="badge badge-<?= $consulta['estado'] ?>">
                    <?= ucfirst($consulta['estado']) ?>
                </span>
            </div>
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:20px; color:#0D4F7A;">
                <?= $nota_existente ? 'Editar Nota Clínica' : 'Registar Nova Nota Clínica' ?>
            </h3>

            <form method="POST" action="">
                <div class="campo">
                    <label for="diagnostico">Diagnóstico *</label>
                    <textarea id="diagnostico" name="diagnostico" rows="3"
                              style="width:100%;padding:10px 14px;border:1.5px solid #d0d7de;border-radius:8px;font-size:15px;font-family:inherit;"
                              required><?= htmlspecialchars($nota_existente['diagnostico'] ?? '') ?></textarea>
                </div>
                <div class="campo">
                    <label for="sintomas">Sintomas</label>
                    <textarea id="sintomas" name="sintomas" rows="3"
                              style="width:100%;padding:10px 14px;border:1.5px solid #d0d7de;border-radius:8px;font-size:15px;font-family:inherit;"><?= htmlspecialchars($nota_existente['sintomas'] ?? '') ?></textarea>
                </div>
                <div class="campo">
                    <label for="tratamento">Tratamento</label>
                    <textarea id="tratamento" name="tratamento" rows="3"
                              style="width:100%;padding:10px 14px;border:1.5px solid #d0d7de;border-radius:8px;font-size:15px;font-family:inherit;"><?= htmlspecialchars($nota_existente['tratamento'] ?? '') ?></textarea>
                </div>
                <div class="campo">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                              style="width:100%;padding:10px 14px;border:1.5px solid #d0d7de;border-radius:8px;font-size:15px;font-family:inherit;"><?= htmlspecialchars($nota_existente['observacoes'] ?? '') ?></textarea>
                </div>
                <div style="display:flex; gap:12px; margin-top:8px;">
                    <button type="submit" class="btn btn-primario">
                        💾 <?= $nota_existente ? 'Atualizar Nota' : 'Guardar Nota' ?>
                    </button>
                    <a href="/saude_viva/dashboard/agenda.php" class="btn btn-secundario">
                        ← Voltar à Agenda
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>