<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePapel(['utente', 'rececionista']);

$papel   = obterPapel();
$erro    = '';
$sucesso = '';

// ── Para que utente se marca? ──
// Utente: sempre o próprio perfil (ignora qualquer utente_id vindo do URL).
// Rececionista: o utente escolhido em "Utentes" (validado na BD).
$utente_id   = null;
$utente_nome = '';

if ($papel === 'utente') {
    $stmt = $pdo->prepare("SELECT id FROM utentes WHERE utilizador_id = ?");
    $stmt->execute([$_SESSION['utilizador_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $utente_id = (int)$row['id'];
    } else {
        $erro = 'Perfil de utente não encontrado.';
    }
} else {
    $pedido = (int)($_POST['utente_id'] ?? $_GET['utente_id'] ?? 0);
    if ($pedido > 0) {
        $stmt = $pdo->prepare("
            SELECT ut.id, u.nome
            FROM utentes ut
            JOIN utilizadores u ON ut.utilizador_id = u.id
            WHERE ut.id = ?
        ");
        $stmt->execute([$pedido]);
        $row = $stmt->fetch();
        if ($row) {
            $utente_id   = (int)$row['id'];
            $utente_nome = $row['nome'];
        }
    }
}

// Campo escondido para manter o utente nos formulários (só rececionista)
$campo_utente = ($papel === 'rececionista' && $utente_id)
    ? '<input type="hidden" name="utente_id" value="' . $utente_id . '">'
    : '';

// ── Parâmetros do URL (validados) ──
$especialidade_id = (int)($_GET['especialidade_id'] ?? 0);
$medico_id        = (int)($_GET['medico_id'] ?? 0);
$data_selecionada = $_GET['data'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_selecionada)) {
    $data_selecionada = '';
}

$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nome")->fetchAll();
$medicos  = [];
$horarios = [];

if ($especialidade_id) {
    $stmt = $pdo->prepare("
        SELECT m.id, u.nome
        FROM medicos m
        JOIN utilizadores u ON m.utilizador_id = u.id
        WHERE m.especialidade_id = ?
    ");
    $stmt->execute([$especialidade_id]);
    $medicos = $stmt->fetchAll();
}

if ($medico_id && $data_selecionada) {
    $stmt = $pdo->prepare("
        SELECT * FROM horarios
        WHERE medico_id = ? AND `data` = ? AND disponivel = 1
        ORDER BY hora_inicio ASC
    ");
    $stmt->execute([$medico_id, $data_selecionada]);
    $horarios = $stmt->fetchAll();
}

// ── Processar marcação ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $horario_id = (int)($_POST['horario_id'] ?? 0);

    if (!$utente_id) {
        $erro = $papel === 'rececionista'
            ? 'Seleciona primeiro um utente.'
            : 'Perfil de utente não encontrado.';
    } elseif ($horario_id <= 0) {
        $erro = 'Por favor seleciona um horário.';
    } else {
        try {
            $pdo->beginTransaction();

            // Reservar o horário de forma atómica
            $stmt = $pdo->prepare("
                UPDATE horarios SET disponivel = 0
                WHERE id = ? AND disponivel = 1 AND `data` >= CURDATE()
            ");
            $stmt->execute([$horario_id]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                $erro = 'Este horário já não está disponível. Por favor escolhe outro.';
            } else {
                // O médico vem do horário (não do formulário)
                $stmt = $pdo->prepare("SELECT medico_id FROM horarios WHERE id = ?");
                $stmt->execute([$horario_id]);
                $mid = $stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    INSERT INTO consultas (utente_id, medico_id, horario_id, estado)
                    VALUES (?, ?, ?, 'agendada')
                ");
                $stmt->execute([$utente_id, $mid, $horario_id]);

                $pdo->commit();
                $sucesso = 'Consulta marcada com sucesso!';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro marcação: ' . $e->getMessage());
            $erro = 'Ocorreu um erro ao marcar a consulta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcar Consulta — Rede Saúde Viva</title>
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
            <?php if ($papel === 'rececionista'): ?>
                <a href="/saude_viva/consultas/lista.php">📋 Consultas do Dia</a>
                <a href="/saude_viva/consultas/marcar.php" class="ativo">📅 Nova Marcação</a>
                <a href="/saude_viva/dashboard/utentes.php">👥 Utentes</a>
            <?php else: ?>
                <a href="/saude_viva/consultas/marcar.php" class="ativo">📅 Marcar Consulta</a>
                <a href="/saude_viva/consultas/lista.php">📋 As Minhas Consultas</a>
                <a href="/saude_viva/historico/index.php">📁 Histórico Clínico</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>📅 Marcar Consulta</h1>
            <p>
                <?php if ($papel === 'rececionista' && $utente_nome): ?>
                    A marcar para: <strong><?= htmlspecialchars($utente_nome) ?></strong>
                <?php else: ?>
                    Escolhe a especialidade, médico e horário disponível
                <?php endif; ?>
            </p>
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso">
                <?= htmlspecialchars($sucesso) ?>
                <br><a href="/saude_viva/consultas/lista.php">Ver consultas →</a>
            </div>
        <?php endif; ?>

        <?php if ($papel === 'rececionista' && !$utente_id && !$sucesso): ?>
            <div class="alerta alerta-erro">
                Escolhe primeiro o utente em
                <a href="/saude_viva/dashboard/utentes.php">Utentes</a>
                e clica em "Marcar".
            </div>
        <?php endif; ?>

        <?php if ($utente_id && !$sucesso): ?>

        <!-- Passo 1: Especialidade -->
        <div class="card" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px; color:#0D4F7A;">Passo 1 — Escolher Especialidade</h3>
            <form method="GET" action="">
                <?= $campo_utente ?>
                <div class="campo">
                    <label for="especialidade_id">Especialidade</label>
                    <select id="especialidade_id" name="especialidade_id"
                            onchange="this.form.submit()">
                        <option value="">-- Seleciona uma especialidade --</option>
                        <?php foreach ($especialidades as $esp): ?>
                            <option value="<?= (int)$esp['id'] ?>"
                                <?= $especialidade_id == $esp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($esp['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Passo 2: Médico -->
        <?php if ($especialidade_id && count($medicos) > 0): ?>
        <div class="card" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px; color:#0D4F7A;">Passo 2 — Escolher Médico</h3>
            <form method="GET" action="">
                <?= $campo_utente ?>
                <input type="hidden" name="especialidade_id" value="<?= $especialidade_id ?>">
                <div class="campo">
                    <label for="medico_id">Médico</label>
                    <select id="medico_id" name="medico_id"
                            onchange="this.form.submit()">
                        <option value="">-- Seleciona um médico --</option>
                        <?php foreach ($medicos as $med): ?>
                            <option value="<?= (int)$med['id'] ?>"
                                <?= $medico_id == $med['id'] ? 'selected' : '' ?>>
                                Dr(a). <?= htmlspecialchars($med['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <?php elseif ($especialidade_id): ?>
            <div class="alerta alerta-erro">Não há médicos disponíveis para esta especialidade.</div>
        <?php endif; ?>

        <!-- Passo 3: Data -->
        <?php if ($medico_id): ?>
        <div class="card" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px; color:#0D4F7A;">Passo 3 — Escolher Data</h3>
            <form method="GET" action="">
                <?= $campo_utente ?>
                <input type="hidden" name="especialidade_id" value="<?= $especialidade_id ?>">
                <input type="hidden" name="medico_id" value="<?= $medico_id ?>">
                <div class="campo">
                    <label for="data">Data da consulta</label>
                    <input type="date" id="data" name="data"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($data_selecionada) ?>"
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Passo 4: Horário -->
        <?php if ($medico_id && $data_selecionada && count($horarios) > 0): ?>
        <div class="card">
            <h3 style="margin-bottom:16px; color:#0D4F7A;">Passo 4 — Escolher Horário</h3>
            <form method="POST" action="">
                <?= $campo_utente ?>
                <div class="horarios-grid" style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($horarios as $h): ?>
                        <label style="cursor:pointer;">
                            <input type="radio" name="horario_id"
                                   value="<?= (int)$h['id'] ?>"
                                   style="display:none;"
                                   onchange="document.getElementById('btn-confirmar').style.display='block'">
                            <span class="badge badge-agendada"
                                  style="padding:8px 14px;font-size:14px;cursor:pointer;"
                                  onclick="this.style.background='#1B6CA8';this.style.color='#fff';">
                                <?= substr($h['hora_inicio'], 0, 5) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <br>
                <button type="submit" id="btn-confirmar" class="btn btn-primario"
                        style="display:none;">
                    ✅ Confirmar Marcação
                </button>
            </form>
        </div>
        <?php elseif ($medico_id && $data_selecionada): ?>
            <div class="alerta alerta-erro">Não há horários disponíveis para este dia. Escolhe outra data.</div>
        <?php endif; ?>

        <?php endif; // utente_id && !sucesso ?>

    </main>
</div>
</body>
</html>