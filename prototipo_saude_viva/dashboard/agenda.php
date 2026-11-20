<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePapel(['medico']);

// Data selecionada (hoje por defeito)
$data_selecionada = $_GET['data'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_selecionada) || !strtotime($data_selecionada)) {
    $data_selecionada = date('Y-m-d');
}
$data_anterior    = date('Y-m-d', strtotime($data_selecionada . ' -1 day'));
$data_seguinte    = date('Y-m-d', strtotime($data_selecionada . ' +1 day'));

$diasemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$meses     = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$dia_nome = $diasemana[date('w', strtotime($data_selecionada))];
$dia_num  = date('d', strtotime($data_selecionada));
$mes_nome = $meses[(int)date('m', strtotime($data_selecionada))];
$ano      = date('Y', strtotime($data_selecionada));

// Buscar consultas do médico na data selecionada
$stmt = $pdo->prepare("
    SELECT c.id, c.estado, h.hora_inicio, h.hora_fim,
           u.nome AS utente_nome, ut.telefone, ut.nif
    FROM consultas c
    JOIN horarios h ON c.horario_id = h.id
    JOIN utentes ut ON c.utente_id = ut.id
    JOIN utilizadores u ON ut.utilizador_id = u.id
    JOIN medicos m ON c.medico_id = m.id
    WHERE m.utilizador_id = ? AND h.data = ?
    ORDER BY h.hora_inicio ASC
");
$stmt->execute([$_SESSION['utilizador_id'], $data_selecionada]);
$consultas = $stmt->fetchAll();

// Contar por estado
$total     = count($consultas);
$agendadas = count(array_filter($consultas, fn($c) => $c['estado'] === 'agendada'));
$realizadas = count(array_filter($consultas, fn($c) => $c['estado'] === 'realizada'));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda — Rede Saúde Viva</title>
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
            <a href="/saude_viva/dashboard/agenda.php" class="ativo">📅 Minha Agenda</a>
            <a href="/saude_viva/consultas/lista.php">📋 Consultas</a>
            <a href="/saude_viva/historico/index.php">📁 Histórico Utentes</a>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>📅 Minha Agenda</h1>
            <p>Consultas agendadas por dia</p>
        </div>

        <!-- Navegação de datas -->
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
            <a href="?data=<?= $data_anterior ?>" class="btn btn-secundario">← Dia anterior</a>
            <div style="text-align:center; flex:1;">
                <span style="font-size:20px; font-weight:700; color:#0D4F7A;">
                    <?= $dia_nome ?>, <?= $dia_num ?> de <?= $mes_nome ?> de <?= $ano ?>
                </span>
            </div>
            <a href="?data=<?= $data_seguinte ?>" class="btn btn-secundario">Dia seguinte →</a>
        </div>

        <!-- Input de data -->
        <div style="text-align:center; margin-bottom:24px;">
            <form method="GET" action="" style="display:inline-flex; gap:8px; align-items:center;">
                <label style="font-size:14px; color:#666;">Ir para data:</label>
                <input type="date" name="data" value="<?= htmlspecialchars($data_selecionada) ?>"
                       style="padding:6px 10px; border:1.5px solid #d0d7de; border-radius:8px;">
                <button type="submit" class="btn btn-primario" style="padding:6px 14px;">Ir</button>
            </form>
        </div>

        <!-- Cards de resumo -->
        <div class="cards-grid" style="margin-bottom:24px;">
            <div class="card">
                <div class="card-numero"><?= $total ?></div>
                <div class="card-label">Total do Dia</div>
            </div>
            <div class="card">
                <div class="card-numero" style="color:#1B6CA8;"><?= $agendadas ?></div>
                <div class="card-label">Agendadas</div>
            </div>
            <div class="card">
                <div class="card-numero" style="color:#1a6b30;"><?= $realizadas ?></div>
                <div class="card-label">Realizadas</div>
            </div>
        </div>

        <!-- Lista de consultas -->
        <?php if (count($consultas) === 0): ?>
            <div class="card">
                <p style="color:#666;">Não tens consultas para este dia.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($consultas as $c): ?>
                <div class="card" style="display:flex; align-items:center; gap:20px;
                     border-left: 4px solid <?= $c['estado'] === 'cancelada' ? '#dc3545' : ($c['estado'] === 'realizada' ? '#1a6b30' : '#1B6CA8') ?>;">

                    <!-- Hora -->
                    <div style="text-align:center; min-width:70px;">
                        <div style="font-size:22px; font-weight:700; color:#0D4F7A;">
                            <?= substr($c['hora_inicio'], 0, 5) ?>
                        </div>
                        <div style="font-size:12px; color:#999;">
                            <?= substr($c['hora_fim'], 0, 5) ?>
                        </div>
                    </div>

                    <!-- Divisor -->
                    <div style="width:1px; height:50px; background:#e0e7ef;"></div>

                    <!-- Info utente -->
                    <div style="flex:1;">
                        <div style="font-weight:600; font-size:16px;">
                            <?= htmlspecialchars($c['utente_nome']) ?>
                        </div>
                        <div style="font-size:13px; color:#666; margin-top:2px;">
                            NIF: <?= htmlspecialchars($c['nif']) ?>
                            <?php if ($c['telefone']): ?>
                                &nbsp;|&nbsp; 📞 <?= htmlspecialchars($c['telefone']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Estado e ações -->
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                        <span class="badge badge-<?= $c['estado'] ?>">
                            <?= ucfirst($c['estado']) ?>
                        </span>
                        <?php if ($c['estado'] === 'agendada' || $c['estado'] === 'realizada'): ?>
                            <a href="/saude_viva/historico/nota.php?consulta_id=<?= $c['id'] ?>"
                               class="btn btn-primario"
                               style="padding:4px 12px; font-size:13px;">
                                📝 Registar Nota
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>