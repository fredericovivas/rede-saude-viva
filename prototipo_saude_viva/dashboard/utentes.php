<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePapel(['rececionista', 'admin']);

$pesquisa = trim($_GET['pesquisa'] ?? '');
$utentes  = [];

if ($pesquisa) {
    $stmt = $pdo->prepare("
        SELECT ut.id, u.nome, u.email, ut.nif,
               ut.telefone, ut.data_nascimento,
               COUNT(c.id) AS total_consultas
        FROM utentes ut
        JOIN utilizadores u ON ut.utilizador_id = u.id
        LEFT JOIN consultas c ON c.utente_id = ut.id
        WHERE u.nome LIKE ? OR ut.nif LIKE ? OR u.email LIKE ?
        GROUP BY ut.id
        ORDER BY u.nome
    ");
    $like = "%$pesquisa%";
    $stmt->execute([$like, $like, $like]);
    $utentes = $stmt->fetchAll();
} else {
    // Mostrar todos por defeito
    $stmt = $pdo->query("
        SELECT ut.id, u.nome, u.email, ut.nif,
               ut.telefone, ut.data_nascimento,
               COUNT(c.id) AS total_consultas
        FROM utentes ut
        JOIN utilizadores u ON ut.utilizador_id = u.id
        LEFT JOIN consultas c ON c.utente_id = ut.id
        GROUP BY ut.id
        ORDER BY u.nome
    ");
    $utentes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utentes — Rede Saúde Viva</title>
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
            <a href="/saude_viva/consultas/lista.php">📋 Consultas do Dia</a>
            <a href="/saude_viva/consultas/marcar.php">📅 Nova Marcação</a>
            <a href="/saude_viva/dashboard/utentes.php" class="ativo">👥 Utentes</a>
        </nav>
        <div class="sidebar-rodape">
            <a href="/saude_viva/logout.php">🚪 Sair</a>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <h1>👥 Utentes</h1>
            <p>Pesquisa e consulta informação dos utentes</p>
        </div>

        <!-- Pesquisa -->
        <div class="card" style="margin-bottom:20px;">
            <form method="GET" action="" style="display:flex; gap:10px;">
                <input type="text" name="pesquisa"
                       value="<?= htmlspecialchars($pesquisa) ?>"
                       placeholder="Pesquisar por nome, NIF ou email..."
                       style="flex:1; padding:10px 14px; border:1.5px solid #d0d7de;
                              border-radius:8px; font-size:15px;"
                       autofocus>
                <button type="submit" class="btn btn-primario">🔍 Pesquisar</button>
                <?php if ($pesquisa): ?>
                    <a href="/saude_viva/dashboard/utentes.php"
                       class="btn btn-secundario">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resultados -->
        <?php if (count($utentes) === 0): ?>
            <div class="card">
                <p style="color:#666;">
                    <?= $pesquisa
                        ? "Nenhum utente encontrado para \"" . htmlspecialchars($pesquisa) . "\"."
                        : "Não há utentes registados no sistema." ?>
                </p>
            </div>
        <?php else: ?>
            <div class="tabela-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>NIF</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Dt. Nascimento</th>
                            <th>Consultas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utentes as $ut): ?>
                        <tr>
                            <td style="font-weight:600;">
                                <?= htmlspecialchars($ut['nome']) ?>
                            </td>
                            <td><?= htmlspecialchars($ut['nif']) ?></td>
                            <td style="font-size:13px; color:#666;">
                                <?= htmlspecialchars($ut['email']) ?>
                            </td>
                            <td><?= htmlspecialchars($ut['telefone'] ?? '—') ?></td>
                            <td>
                                <?= $ut['data_nascimento']
                                    ? date('d/m/Y', strtotime($ut['data_nascimento']))
                                    : '—' ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-agendada">
                                    <?= $ut['total_consultas'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="/saude_viva/consultas/marcar.php?utente_id=<?= (int)$ut['id'] ?>"
                                   class="btn btn-primario"
                                   style="padding:4px 10px; font-size:12px;">
                                    📅 Marcar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="font-size:13px; color:#666; margin-top:8px; text-align:right;">
                <?= count($utentes) ?> utente(s) encontrado(s)
            </p>
        <?php endif; ?>

    </main>
</div>
</body>
</html>