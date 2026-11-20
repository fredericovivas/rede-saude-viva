<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$papel = obterPapel();
$erro    = '';
$sucesso = '';

// Processar cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    $consulta_id = $_POST['cancelar_id'];

    // Verificar se a consulta pertence ao utente e se pode ser cancelada
    $stmt = $pdo->prepare("
        SELECT c.*, h.data, h.hora_inicio
        FROM consultas c
        JOIN horarios h ON c.horario_id = h.id
        JOIN utentes ut ON c.utente_id = ut.id
        WHERE c.id = ? AND ut.utilizador_id = ? AND c.estado = 'agendada'
    ");
    $stmt->execute([$consulta_id, $_SESSION['utilizador_id']]);
    $consulta = $stmt->fetch();

    if (!$consulta) {
        $erro = 'Consulta não encontrada ou não pode ser cancelada.';
    } else {
        // Verificar 24h de antecedência
        $data_consulta = strtotime($consulta['data'] . ' ' . $consulta['hora_inicio']);
        $agora         = time();
        $diferenca     = $data_consulta - $agora;

        if ($diferenca < 86400) {
            $erro = 'Não é possível cancelar com menos de 24 horas de antecedência.';
        } else {
            // Cancelar consulta
            $stmt = $pdo->prepare("UPDATE consultas SET estado = 'cancelada' WHERE id = ?");
            $stmt->execute([$consulta_id]);

            // Libertar o horário
            $stmt = $pdo->prepare("UPDATE horarios SET disponivel = 1 WHERE id = ?");
            $stmt->execute([$consulta['horario_id']]);

            $sucesso = 'Consulta cancelada com sucesso.';
        }
    }
}

// Buscar consultas conforme o papel
if ($papel === 'utente') {
    $stmt = $pdo->prepare("
        SELECT c.id, c.estado, h.data, h.hora_inicio, h.hora_fim,
               u.nome AS medico_nome, e.nome AS especialidade
        FROM consultas c
        JOIN horarios h ON c.horario_id = h.id
        JOIN medicos m ON c.medico_id = m.id
        JOIN utilizadores u ON m.utilizador_id = u.id
        JOIN especialidades e ON m.especialidade_id = e.id
        JOIN utentes ut ON c.utente_id = ut.id
        WHERE ut.utilizador_id = ?
        ORDER BY h.data DESC, h.hora_inicio DESC
    ");
    $stmt->execute([$_SESSION['utilizador_id']]);

} elseif ($papel === 'medico') {
    $stmt = $pdo->prepare("
        SELECT c.id, c.estado, h.data, h.hora_inicio, h.hora_fim,
               u.nome AS utente_nome
        FROM consultas c
        JOIN horarios h ON c.horario_id = h.id
        JOIN utentes ut ON c.utente_id = ut.id
        JOIN utilizadores u ON ut.utilizador_id = u.id
        JOIN medicos m ON c.medico_id = m.id
        WHERE m.utilizador_id = ?
        ORDER BY h.data DESC, h.hora_inicio DESC
    ");
    $stmt->execute([$_SESSION['utilizador_id']]);

} else {
    // Rececionista e admin veem todas
    $stmt = $pdo->query("
        SELECT c.id, c.estado, h.data, h.hora_inicio, h.hora_fim,
               u.nome AS utente_nome, um.nome AS medico_nome, e.nome AS especialidade
        FROM consultas c
        JOIN horarios h ON c.horario_id = h.id
        JOIN utentes ut ON c.utente_id = ut.id
        JOIN utilizadores u ON ut.utilizador_id = u.id
        JOIN medicos m ON c.medico_id = m.id
        JOIN utilizadores um ON m.utilizador_id = um.id
        JOIN especialidades e ON m.especialidade_id = e.id
        ORDER BY h.data DESC, h.hora_inicio DESC
    ");
}

$consultas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas — Rede Saúde Viva</title>
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
            <?php if ($papel === 'utente'): ?>
                <a href="/saude_viva/consultas/marcar.php">📅 Marcar Consulta</a>
                <a href="/saude_viva/consultas/lista.php" class="ativo">📋 As Minhas Consultas</a>
                <a href="/saude_viva/historico/index.php">📁 Histórico Clínico</a>
            <?php elseif ($papel === 'medico'): ?>
                <a href="/saude_viva/dashboard/agenda.php">📅 Minha Agenda</a>
                <a href="/saude_viva/consultas/lista.php" class="ativo">📋 Consultas</a>
                <a href="/saude_viva/historico/index.php">📁 Histórico Utentes</a>
            <?php else: ?>
                <a href="/saude_viva/consultas/lista.php" class="ativo">📋 Consultas do Dia</a>
                <a href="/saude_viva/consultas/marcar.php">📅 Nova Marcação</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>📋 Consultas</h1>
            <p>
                <?php if ($papel === 'utente'): ?>
                    O teu histórico de consultas
                <?php elseif ($papel === 'medico'): ?>
                    As tuas consultas agendadas
                <?php else: ?>
                    Todas as consultas do sistema
                <?php endif; ?>
            </p>
        </div>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <?php if (count($consultas) === 0): ?>
            <div class="card">
                <p>Não há consultas para mostrar.</p>
                <?php if ($papel === 'utente'): ?>
                    <br><a href="/saude_viva/consultas/marcar.php" class="btn btn-primario">Marcar Consulta</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="tabela-container">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <?php if ($papel === 'utente'): ?>
                            <th>Médico</th>
                            <th>Especialidade</th>
                        <?php elseif ($papel === 'medico'): ?>
                            <th>Utente</th>
                        <?php else: ?>
                            <th>Utente</th>
                            <th>Médico</th>
                            <th>Especialidade</th>
                        <?php endif; ?>
                        <th>Estado</th>
                        <?php if ($papel === 'utente'): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultas as $c): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($c['data'])) ?></td>
                        <td><?= substr($c['hora_inicio'], 0, 5) ?> – <?= substr($c['hora_fim'], 0, 5) ?></td>
                        <?php if ($papel === 'utente'): ?>
                            <td>Dr(a). <?= htmlspecialchars($c['medico_nome']) ?></td>
                            <td><?= htmlspecialchars($c['especialidade']) ?></td>
                        <?php elseif ($papel === 'medico'): ?>
                            <td><?= htmlspecialchars($c['utente_nome']) ?></td>
                        <?php else: ?>
                            <td><?= htmlspecialchars($c['utente_nome']) ?></td>
                            <td>Dr(a). <?= htmlspecialchars($c['medico_nome']) ?></td>
                            <td><?= htmlspecialchars($c['especialidade']) ?></td>
                        <?php endif; ?>
                        <td>
                            <span class="badge badge-<?= $c['estado'] ?>">
                                <?= ucfirst($c['estado']) ?>
                            </span>
                        </td>
                        <?php if ($papel === 'utente' && $c['estado'] === 'agendada'): ?>
                        <td>
                            <form method="POST" action=""
                                  onsubmit="return confirm('Tens a certeza que queres cancelar esta consulta?')">
                                <input type="hidden" name="cancelar_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-perigo"
                                        style="padding:4px 12px;font-size:13px;">
                                    Cancelar
                                </button>
                            </form>
                        </td>
                        <?php elseif ($papel === 'utente'): ?>
                            <td>—</td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>