<?php
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    // Termina a sessão após 30 minutos de inatividade
    if (isset($_SESSION['ultima_atividade'])
        && (time() - $_SESSION['ultima_atividade']) > 1800) {
        $_SESSION = [];
    }
    $_SESSION['ultima_atividade'] = time();
}

function estaLogado() {
    iniciarSessao();
    return isset($_SESSION['utilizador_id']);
}

function obterPapel() {
    iniciarSessao();
    return $_SESSION['papel'] ?? null;
}

function requireLogin() {
    if (!estaLogado()) {
        header('Location: /saude_viva/index.php');
        exit;
    }
}

function requirePapel($papeis) {
    requireLogin();
    if (!in_array(obterPapel(), (array)$papeis)) {
        header('Location: /saude_viva/index.php');
        exit;
    }
}

function terminarSessao() {
    iniciarSessao();
    session_destroy();
    header('Location: /saude_viva/index.php');
    exit;
}
?>