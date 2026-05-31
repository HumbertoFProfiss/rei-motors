<?php
/**
 * Conexão com Banco de Dados
 * HostGator - Hospedagem Compartilhada
 * PHP 8.2 + MySQL
 */

// Definir timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');

// Credenciais do banco (substituir com dados reais do cPanel)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rei_motors_db');
define('DB_USER', 'root');
define('DB_PASS', '#Admin2026');

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros em produção
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

try {
    // Conexão PDO com charset UTF-8
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Melhor segurança
        ]
    );
} catch (PDOException $e) {
    // Log do erro (não exibir em produção)
    error_log("Erro na conexão com BD: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Contate o administrador.");
}

/**
 * Função para executar queries com prepared statements
 */
function executarQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter uma linha de resultado
 */
function obterUmaLinha($sql, $params = []) {
    $stmt = executarQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

/**
 * Obter todas as linhas
 */
function obterTodas($sql, $params = []) {
    $stmt = executarQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Inserir registro
 */
function inserir($tabela, $dados) {
    global $pdo;
    $colunas = array_keys($dados);
    $valores = array_values($dados);
    $placeholders = array_fill(0, count($dados), '?');
    
    $sql = "INSERT INTO $tabela (" . implode(',', $colunas) . ") VALUES (" . implode(',', $placeholders) . ")";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erro ao inserir em $tabela: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualizar registro
 */
function atualizar($tabela, $dados, $where, $params_where) {
    global $pdo;
    $sets = [];
    $valores = [];
    
    foreach ($dados as $coluna => $valor) {
        $sets[] = "$coluna = ?";
        $valores[] = $valor;
    }
    
    $valores = array_merge($valores, $params_where);
    
    $sql = "UPDATE $tabela SET " . implode(', ', $sets) . " WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar $tabela: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletar registro
 */
function deletar($tabela, $where, $params) {
    global $pdo;
    $sql = "DELETE FROM $tabela WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Erro ao deletar de $tabela: " . $e->getMessage());
        return false;
    }
}
?>
