<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$papel = obterPapel();
$nome  = $_SESSION['nome'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Rede Saúde Viva</title>
    <link rel="stylesheet" href="/saude_viva/assets/css/style.css">
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h2>🏥 Saúde Viva</h2>
            <p><?= htmlspecialchars($nome) ?></p>
        </div>

        <nav class="sidebar-nav">
            <a href="/saude_viva/dashboard/index.php" class="ativo">🏠 Início</a>

            <?php if ($papel === 'utente'): ?>
                <a href="/saude_viva/consultas/marcar.php">📅 Marcar Consulta</a>
                <a href="/saude_viva/consultas/lista.php">📋 As Minhas Consultas</a>
                <a href="/saude_viva/historico/index.php">📁 Histórico Clínico</a>

            <?php elseif ($papel === 'medico'): ?>
                <a href="/saude_viva/dashboard/agenda.php">📅 Minha Agenda</a>
                <a href="/saude_viva/consultas/lista.php">📋 Consultas</a>
                <a href="/saude_viva/historico/index.php">📁 Histórico Utentes</a>

            <?php elseif ($papel === 'rececionista'): ?>
                <a href="/saude_viva/consultas/lista.php">📋 Consultas do Dia</a>
                <a href="/saude_viva/consultas/marcar.php">📅 Nova Marcação</a>
                <a href="/saude_viva/dashboard/utentes.php">👥 Utentes</a>

            <?php elseif ($papel === 'admin'): ?>
                <a href="/saude_viva/dashboard/utilizadores.php">👥 Utilizadores</a>
                <a href="/saude_viva/dashboard/estatisticas.php">📊 Estatísticas</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <!-- Conteúdo principal -->
    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>Bem-vindo, <?= htmlspecialchars($nome) ?>!</h1>
            <p>Painel de controlo — <?= ucfirst($papel) ?></p>
        </div>

        <?php if ($papel === 'utente'): ?>
            <?php
            $stmt = $pdo->prepare("
                SELECT c.*, u.nome as medico_nome, e.nome as especialidade
                FROM consultas c
                JOIN horarios h ON c.horario_id = h.id
                JOIN medicos m ON c.medico_id = m.id
                JOIN utilizadores u ON m.utilizador_id = u.id
                JOIN especialidades e ON m.especialidade_id = e.id
                JOIN utentes ut ON c.utente_id = ut.id
                WHERE ut.utilizador_id = ? AND c.estado = 'agendada'
                ORDER BY h.data ASC, h.hora_inicio ASC
                LIMIT 3
            ");
            $stmt->execute([$_SESSION['utilizador_id']]);
            $proximas = $stmt->fetchAll();
            ?>
            <div class="cards-grid">
                <div class="card">
                    <div class="card-numero"><?= count($proximas) ?></div>
                    <div class="card-label">Consultas Agendadas</div>
                </div>
            </div>

            <?php if (count($proximas) > 0): ?>
                <div class="tabela-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Médico</th>
                                <th>Especialidade</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['medico_nome']) ?></td>
                                <td><?= htmlspecialchars($c['especialidade']) ?></td>
                                <td><span class="badge badge-agendada">Agendada</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card">
                    <p>Não tens consultas agendadas. <a href="/saude_viva/consultas/marcar.php">Marcar consulta</a></p>
                </div>
            <?php endif; ?>

        <?php elseif ($papel === 'medico'): ?>
            <div class="cards-grid">
                <div class="card">
                    <div class="card-numero">—</div>
                    <div class="card-label">Consultas Hoje</div>
                </div>
                <div class="card">
                    <div class="card-numero">—</div>
                    <div class="card-label">Esta Semana</div>
                </div>
            </div>
            <div class="card">
                <p>Usa o menu lateral para aceder à tua agenda e consultas.</p>
            </div>

        <?php elseif ($papel === 'rececionista'): ?>
            <div class="card">
                <p>Usa o menu lateral para gerir consultas e utentes.</p>
            </div>

        <?php elseif ($papel === 'admin'): ?>
            <div class="card">
                <p>Painel de administração. Usa o menu lateral para gerir utilizadores.</p>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>