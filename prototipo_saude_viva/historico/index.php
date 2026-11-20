<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$papel = obterPapel();

// Médico pode pesquisar qualquer utente
// Utente vê apenas o seu próprio histórico
$utente_id = null;
$utente_info = null;

if ($papel === 'medico') {
    $pesquisa = trim($_GET['pesquisa'] ?? '');
    $utentes  = [];

    if ($pesquisa) {
        $stmt = $pdo->prepare("
            SELECT ut.id, u.nome, u.email, ut.nif, ut.telefone
            FROM utentes ut
            JOIN utilizadores u ON ut.utilizador_id = u.id
            WHERE u.nome LIKE ? OR ut.nif LIKE ? OR u.email LIKE ?
            ORDER BY u.nome
        ");
        $like = "%$pesquisa%";
        $stmt->execute([$like, $like, $like]);
        $utentes = $stmt->fetchAll();
    }

    $utente_id = $_GET['utente_id'] ?? null;
    if ($utente_id) {
        $stmt = $pdo->prepare("
            SELECT ut.id, u.nome, u.email, ut.nif,
                   ut.telefone, ut.data_nascimento
            FROM utentes ut
            JOIN utilizadores u ON ut.utilizador_id = u.id
            WHERE ut.id = ?
        ");
        $stmt->execute([$utente_id]);
        $utente_info = $stmt->fetch();
    }

} elseif ($papel === 'utente') {
    $stmt = $pdo->prepare("
        SELECT ut.id, u.nome, u.email, ut.nif,
               ut.telefone, ut.data_nascimento
        FROM utentes ut
        JOIN utilizadores u ON ut.utilizador_id = u.id
        WHERE ut.utilizador_id = ?
    ");
    $stmt->execute([$_SESSION['utilizador_id']]);
    $utente_info = $stmt->fetch();
    $utente_id   = $utente_info['id'] ?? null;
}

// Buscar histórico de consultas com notas
$historico = [];
if ($utente_id) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.estado, h.data, h.hora_inicio,
               u.nome AS medico_nome, e.nome AS especialidade,
               n.diagnostico, n.sintomas, n.tratamento,
               n.observacoes, n.criado_em AS nota_data
        FROM consultas c
        JOIN horarios h      ON c.horario_id = h.id
        JOIN medicos m       ON c.medico_id  = m.id
        JOIN utilizadores u  ON m.utilizador_id = u.id
        JOIN especialidades e ON m.especialidade_id = e.id
        LEFT JOIN notas_clinicas n ON n.consulta_id = c.id
        WHERE c.utente_id = ?
        ORDER BY h.data DESC, h.hora_inicio DESC
    ");
    $stmt->execute([$utente_id]);
    $historico = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Clínico — Rede Saúde Viva</title>
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
                <a href="/saude_viva/consultas/lista.php">📋 As Minhas Consultas</a>
                <a href="/saude_viva/historico/index.php" class="ativo">📁 Histórico Clínico</a>
            <?php elseif ($papel === 'medico'): ?>
                <a href="/saude_viva/dashboard/agenda.php">📅 Minha Agenda</a>
                <a href="/saude_viva/consultas/lista.php">📋 Consultas</a>
                <a href="/saude_viva/historico/index.php" class="ativo">📁 Histórico Utentes</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>📁 Histórico Clínico</h1>
            <p>
                <?= $papel === 'medico'
                    ? 'Consulta o histórico clínico dos utentes'
                    : 'O teu histórico clínico completo' ?>
            </p>
        </div>

        <?php if ($papel === 'medico'): ?>
        <!-- Pesquisa de utente -->
        <div class="card" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px; color:#0D4F7A;">🔍 Pesquisar Utente</h3>
            <form method="GET" action="" style="display:flex; gap:10px;">
                <input type="text" name="pesquisa"
                       value="<?= htmlspecialchars($pesquisa ?? '') ?>"
                       placeholder="Nome, NIF ou email do utente..."
                       style="flex:1; padding:10px 14px; border:1.5px solid #d0d7de;
                              border-radius:8px; font-size:15px;">
                <button type="submit" class="btn btn-primario">Pesquisar</button>
            </form>

            <?php if (!empty($utentes)): ?>
            <div style="margin-top:16px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f0f4f8;">
                            <th style="padding:10px; text-align:left; font-size:13px;">Nome</th>
                            <th style="padding:10px; text-align:left; font-size:13px;">NIF</th>
                            <th style="padding:10px; text-align:left; font-size:13px;">Email</th>
                            <th style="padding:10px; text-align:left; font-size:13px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utentes as $ut): ?>
                        <tr style="border-top:1px solid #f0f4f8;">
                            <td style="padding:10px; font-size:14px;"><?= htmlspecialchars($ut['nome']) ?></td>
                            <td style="padding:10px; font-size:14px;"><?= htmlspecialchars($ut['nif']) ?></td>
                            <td style="padding:10px; font-size:14px;"><?= htmlspecialchars($ut['email']) ?></td>
                            <td style="padding:10px;">
                                <a href="?utente_id=<?= $ut['id'] ?>"
                                   class="btn btn-secundario"
                                   style="padding:4px 12px; font-size:13px;">
                                    Ver histórico
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif (isset($pesquisa) && $pesquisa !== ''): ?>
                <p style="margin-top:12px; color:#666; font-size:14px;">
                    Nenhum utente encontrado para "<?= htmlspecialchars($pesquisa) ?>".
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($utente_info): ?>
        <!-- Info do utente -->
        <div class="card" style="margin-bottom:20px; display:flex; gap:32px; flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;color:#666;">Nome</div>
                <div style="font-weight:600;"><?= htmlspecialchars($utente_info['nome']) ?></div>
            </div>
            <div>
                <div style="font-size:12px;color:#666;">NIF</div>
                <div style="font-weight:600;"><?= htmlspecialchars($utente_info['nif']) ?></div>
            </div>
            <?php if ($utente_info['data_nascimento']): ?>
            <div>
                <div style="font-size:12px;color:#666;">Data de Nascimento</div>
                <div style="font-weight:600;">
                    <?= date('d/m/Y', strtotime($utente_info['data_nascimento'])) ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($utente_info['telefone']): ?>
            <div>
                <div style="font-size:12px;color:#666;">Telefone</div>
                <div style="font-weight:600;"><?= htmlspecialchars($utente_info['telefone']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Histórico -->
        <?php if (count($historico) === 0): ?>
            <div class="card">
                <p style="color:#666;">Não há consultas registadas para este utente.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <?php foreach ($historico as $c): ?>
                <div class="card" style="border-left: 4px solid <?= $c['estado'] === 'realizada' ? '#1a6b30' : ($c['estado'] === 'cancelada' ? '#dc3545' : '#1B6CA8') ?>;">

                    <!-- Cabeçalho da consulta -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <div>
                            <span style="font-weight:700; font-size:16px; color:#0D4F7A;">
                                <?= date('d/m/Y', strtotime($c['data'])) ?>
                                às <?= substr($c['hora_inicio'], 0, 5) ?>
                            </span>
                            <span style="margin-left:12px; color:#666; font-size:14px;">
                                Dr(a). <?= htmlspecialchars($c['medico_nome']) ?>
                                — <?= htmlspecialchars($c['especialidade']) ?>
                            </span>
                        </div>
                        <span class="badge badge-<?= $c['estado'] ?>">
                            <?= ucfirst($c['estado']) ?>
                        </span>
                    </div>

                    <!-- Nota clínica -->
                    <?php if ($c['diagnostico']): ?>
                    <div style="background:#f8fafc; border-radius:8px; padding:16px; margin-top:8px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div>
                                <div style="font-size:12px; font-weight:600; color:#1B6CA8; margin-bottom:4px;">
                                    DIAGNÓSTICO
                                </div>
                                <div style="font-size:14px;">
                                    <?= nl2br(htmlspecialchars($c['diagnostico'])) ?>
                                </div>
                            </div>
                            <?php if ($c['sintomas']): ?>
                            <div>
                                <div style="font-size:12px; font-weight:600; color:#1B6CA8; margin-bottom:4px;">
                                    SINTOMAS
                                </div>
                                <div style="font-size:14px;">
                                    <?= nl2br(htmlspecialchars($c['sintomas'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($c['tratamento']): ?>
                            <div>
                                <div style="font-size:12px; font-weight:600; color:#1B6CA8; margin-bottom:4px;">
                                    TRATAMENTO
                                </div>
                                <div style="font-size:14px;">
                                    <?= nl2br(htmlspecialchars($c['tratamento'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($c['observacoes']): ?>
                            <div>
                                <div style="font-size:12px; font-weight:600; color:#1B6CA8; margin-bottom:4px;">
                                    OBSERVAÇÕES
                                </div>
                                <div style="font-size:14px;">
                                    <?= nl2br(htmlspecialchars($c['observacoes'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($c['estado'] === 'realizada'): ?>
                        <p style="color:#666; font-size:13px; margin-top:8px;">
                            Consulta realizada sem nota clínica registada.
                        </p>
                    <?php endif; ?>

                    <?php if ($papel === 'medico' && $c['estado'] === 'agendada'): ?>
                    <div style="margin-top:12px;">
                        <a href="/saude_viva/historico/nota.php?consulta_id=<?= $c['id'] ?>"
                           class="btn btn-primario"
                           style="padding:6px 14px; font-size:13px;">
                            📝 Registar Nota
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php elseif ($papel === 'medico'): ?>
            <div class="card">
                <p style="color:#666;">Pesquisa um utente acima para ver o seu histórico clínico.</p>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>