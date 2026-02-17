
<?php
// src/controllers/DashboardAdminController.php

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DashboardAdminController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getStats() {
        try {
            error_log("DASHBOARD_ADMIN: Buscando estatísticas do dashboard");
            
            // Saldo em Caixa - soma todas as entradas (recargas + compras de planos), independente do método de pagamento
            $cashQuery = "SELECT COALESCE(SUM(amount), 0) as total_cash 
                         FROM central_cash 
                         WHERE transaction_type IN ('entrada', 'recarga', 'plano')";
            $cashStmt = $this->db->prepare($cashQuery);
            $cashStmt->execute();
            $cashResult = $cashStmt->fetch(PDO::FETCH_ASSOC);

            // Planos Comprados - soma o valor total dos planos comprados  
            $plansQuery = "SELECT COALESCE(SUM(CAST(amount AS DECIMAL(10,2))), 0) as plan_sales 
                          FROM central_cash 
                          WHERE transaction_type = 'plano'";
            $plansStmt = $this->db->prepare($plansQuery);
            $plansStmt->execute();
            $plansResult = $plansStmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Log valores dos planos - MAIS DETALHADO
            error_log("DEBUG PLANOS: Query executada: " . $plansQuery);
            error_log("DEBUG PLANOS: Resultado da query: " . json_encode($plansResult));
            error_log("DEBUG PLANOS: Total de planos vendidos = " . $plansResult['plan_sales']);
            error_log("DEBUG PLANOS: Tipo do valor: " . gettype($plansResult['plan_sales']));
            
            // Debug: Verificar se existem registros na tabela central_cash
            $debugAllQuery = "SELECT * FROM central_cash WHERE transaction_type = 'plano' LIMIT 5";
            $debugAllStmt = $this->db->prepare($debugAllQuery);
            $debugAllStmt->execute();
            $debugAllResults = $debugAllStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG PLANOS: Primeiros 5 registros de planos: " . json_encode($debugAllResults));
            
            // Debug: Contar registros na tabela central_cash com transaction_type = 'plano'
            $debugPlansQuery = "SELECT COUNT(*) as count, COALESCE(SUM(CAST(amount AS DECIMAL(10,2))), 0) as total 
                               FROM central_cash 
                               WHERE transaction_type = 'plano'";
            $debugPlansStmt = $this->db->prepare($debugPlansQuery);
            $debugPlansStmt->execute();
            $debugPlansResult = $debugPlansStmt->fetch(PDO::FETCH_ASSOC);
            error_log("DEBUG PLANOS: {$debugPlansResult['count']} registros encontrados, total: {$debugPlansResult['total']}");
            
            // Debug: Verificar a estrutura da tabela central_cash
            $debugStructureQuery = "SHOW COLUMNS FROM central_cash";
            $debugStructureStmt = $this->db->prepare($debugStructureQuery);
            $debugStructureStmt->execute();
            $debugStructureResults = $debugStructureStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG PLANOS: Estrutura da tabela central_cash: " . json_encode($debugStructureResults));

            // Total de Usuários
            $usersQuery = "SELECT COUNT(*) as total_users FROM users WHERE status = 'ativo'";
            $usersStmt = $this->db->prepare($usersQuery);
            $usersStmt->execute();
            $usersResult = $usersStmt->fetch(PDO::FETCH_ASSOC);

            // Indicações - quantidade única (cada indicação real gera 2 registros) e valor total
            $commissionsQuery = "SELECT 
                                   FLOOR(COALESCE(COUNT(*), 0) / 2) as total_referrals,
                                   COALESCE(SUM(amount), 0) as total_commissions_value
                                FROM wallet_transactions 
                                WHERE type = 'indicacao' 
                                AND amount > 0";
            $commissionsStmt = $this->db->prepare($commissionsQuery);
            $commissionsStmt->execute();
            $commissionsResult = $commissionsStmt->fetch(PDO::FETCH_ASSOC);

            // Total de Módulos (soma todos os módulos ativos de todos os painéis)
            $modulesQuery = "SELECT COUNT(*) as total_modules 
                           FROM modules 
                           WHERE is_active = 1 AND operational_status = 'on'";
            $modulesStmt = $this->db->prepare($modulesQuery);
            $modulesStmt->execute();
            $modulesResult = $modulesStmt->fetch(PDO::FETCH_ASSOC);

            // Total em Recargas - apenas recargas válidas com método de pagamento
            $rechargesQuery = "SELECT COALESCE(SUM(amount), 0) as total_recharges 
                              FROM central_cash 
                              WHERE transaction_type = 'recarga' 
                              AND payment_method IS NOT NULL 
                              AND payment_method != ''";
            $rechargesStmt = $this->db->prepare($rechargesQuery);
            $rechargesStmt->execute();
            $rechargesResult = $rechargesStmt->fetch(PDO::FETCH_ASSOC);


            // Total de Saques
            $withdrawalsQuery = "SELECT COALESCE(SUM(amount), 0) as total_withdrawals 
                                FROM central_cash WHERE transaction_type = 'saque'";
            $withdrawalsStmt = $this->db->prepare($withdrawalsQuery);
            $withdrawalsStmt->execute();
            $withdrawalsResult = $withdrawalsStmt->fetch(PDO::FETCH_ASSOC);

            // Consultas Realizadas
            $consultationsQuery = "SELECT COUNT(*) as total_consultations FROM consultations";
            $consultationsStmt = $this->db->prepare($consultationsQuery);
            $consultationsStmt->execute();
            $consultationsResult = $consultationsStmt->fetch(PDO::FETCH_ASSOC);

            // Usuários Online (últimos 5 minutos)
            $onlineUsersQuery = "SELECT COUNT(DISTINCT user_id) as users_online 
                                FROM user_sessions 
                                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND status = 'ativa'";
            $onlineUsersStmt = $this->db->prepare($onlineUsersQuery);
            $onlineUsersStmt->execute();
            $onlineUsersResult = $onlineUsersStmt->fetch(PDO::FETCH_ASSOC);

            // Pagamentos PIX - apenas entradas
            $pixQuery = "SELECT COALESCE(SUM(amount), 0) as total_pix 
                        FROM central_cash 
                        WHERE payment_method = 'pix' 
                        AND transaction_type IN ('entrada', 'recarga', 'plano')";
            $pixStmt = $this->db->prepare($pixQuery);
            $pixStmt->execute();
            $pixResult = $pixStmt->fetch(PDO::FETCH_ASSOC);

            // Pagamentos Cartão de Crédito - apenas entradas
            $cardQuery = "SELECT COALESCE(SUM(amount), 0) as total_card 
                         FROM central_cash 
                         WHERE payment_method = 'credit' 
                         AND transaction_type IN ('entrada', 'recarga', 'plano')";
            $cardStmt = $this->db->prepare($cardQuery);
            $cardStmt->execute();
            $cardResult = $cardStmt->fetch(PDO::FETCH_ASSOC);

            // Pagamentos PayPal - apenas entradas
            $paypalQuery = "SELECT COALESCE(SUM(amount), 0) as total_paypal 
                           FROM central_cash 
                           WHERE payment_method = 'paypal' 
                           AND transaction_type IN ('entrada', 'recarga', 'plano')";
            $paypalStmt = $this->db->prepare($paypalQuery);
            $paypalStmt->execute();
            $paypalResult = $paypalStmt->fetch(PDO::FETCH_ASSOC);

            // Total de Cupons Usados - Tabela correta cupom_uso
            try {
                $couponsQuery = "SELECT COALESCE(SUM(valor_desconto), 0) as total_coupons_used 
                                FROM cupom_uso";
                $couponsStmt = $this->db->prepare($couponsQuery);
                $couponsStmt->execute();
                $couponsResult = $couponsStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Tabela cupom_uso não existe ainda - retornar 0
                error_log("DASHBOARD_ADMIN: Tabela cupom_uso não encontrada - " . $e->getMessage());
                $couponsResult = ['total_coupons_used' => 0];
            }

            // Painéis Ativos - obter da API externa (se configurada)
            $activePanels = $this->getActivePanelsFromExternalAPI();

            $stats = [
                'cash_balance' => floatval($cashResult['total_cash']) ?: 0,
                'active_plans' => $activePanels ?: 0, // Usar valor da API externa
                'total_users' => intval($usersResult['total_users']) ?: 0,
                'total_referrals' => intval($commissionsResult['total_referrals']) ?: 0,
                'total_commissions' => floatval($commissionsResult['total_commissions_value']) ?: 0,
                'total_modules' => intval($modulesResult['total_modules']) ?: 0,
                'total_recharges' => floatval($rechargesResult['total_recharges']) ?: 0,
                'plan_sales' => floatval($plansResult['plan_sales']) ?: 0,
                'total_withdrawals' => floatval($withdrawalsResult['total_withdrawals']) ?: 0,
                'total_consultations' => intval($consultationsResult['total_consultations']) ?: 0,
                'users_online' => intval($onlineUsersResult['users_online']) ?: 0,
                // Novos campos para métodos de pagamento específicos
                'payment_pix' => floatval($pixResult['total_pix']) ?: 0,
                'payment_card' => floatval($cardResult['total_card']) ?: 0,
                'payment_paypal' => floatval($paypalResult['total_paypal']) ?: 0,
                'total_coupons_used' => floatval($couponsResult['total_coupons_used']) ?: 0
            ];
            
            error_log("DASHBOARD_ADMIN: Estatísticas carregadas - " . json_encode($stats));
            Response::success($stats, 'Estatísticas carregadas com sucesso');
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN ERROR: " . $e->getMessage());
            Response::error('Erro ao carregar estatísticas: ' . $e->getMessage(), 500);
        }
    }

    private function getActivePanelsFromExternalAPI() {
        try {
            // Verificar se há configuração para API externa de painéis
            // Por enquanto, vamos usar a contagem de painéis ativos locais
            $panelsQuery = "SELECT COUNT(*) as active_panels 
                           FROM panels 
                           WHERE is_active = 1";
            $panelsStmt = $this->db->prepare($panelsQuery);
            $panelsStmt->execute();
            $panelsResult = $panelsStmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($panelsResult['active_panels']) ?: 0;
        } catch (Exception $e) {
            error_log("Erro ao buscar painéis ativos: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getUsers() {
        try {
            error_log("DASHBOARD_ADMIN: Buscando usuários");
            
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT u.*, 
                             COUNT(DISTINCT c.id) as total_consultations,
                             COALESCE(SUM(wt.amount), 0) as total_spent,
                             MAX(uss.last_activity) as last_login,
                             usub.plan_id as subscription_plan_id,
                             p.discount_percentage as plan_discount_percentage
                      FROM users u
                      LEFT JOIN consultations c ON u.id = c.user_id
                      LEFT JOIN wallet_transactions wt ON u.id = wt.user_id AND wt.type = 'saida'
                      LEFT JOIN user_sessions uss ON u.id = uss.user_id
                      LEFT JOIN user_subscriptions usub ON u.id = usub.user_id AND usub.status = 'active'
                      LEFT JOIN plans p ON usub.plan_id = p.id
                      GROUP BY u.id
                      ORDER BY u.created_at DESC
                      LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit, $offset]);
            
            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['full_name'],
                    'email' => $row['email'],
                    'login' => $row['username'],
                    'cpf' => $row['cpf'],
                    'telefone' => $row['telefone'],
                    'plan' => $row['tipoplano'],
                    'balance' => floatval($row['saldo']),
                    'saldo' => floatval($row['saldo']),
                    'saldo_plano' => floatval($row['saldo_plano']),
                    'data_inicio' => $row['data_inicio'],
                    'data_fim' => $row['data_fim'],
                    'plan_discount' => (int)($row['plan_discount_percentage'] ?? 0),
                    'status' => $row['status'],
                    'user_role' => $row['user_role'],
                    'full_name' => $row['full_name'],
                    'total_consultations' => intval($row['total_consultations']),
                    'total_spent' => floatval($row['total_spent']),
                    'last_login' => $row['last_login'],
                    'created_at' => $row['created_at'],
                    'is_online' => $this->isUserOnline($row['id'])
                ];
            }
            
            // Contar total de usuários (excluindo os excluídos)
            $countQuery = "SELECT COUNT(*) as total FROM users";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute();
            $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DASHBOARD_ADMIN: " . count($users) . " usuários carregados");
            Response::success([
                'users' => $users, 
                'total' => intval($totalResult['total']),
                'page' => intval($page),
                'limit' => intval($limit)
            ], 'Usuários carregados com sucesso');
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN USERS ERROR: " . $e->getMessage());
            Response::error('Erro ao carregar usuários: ' . $e->getMessage(), 500);
        }
    }
    
    public function getActivities() {
        try {
            error_log("DASHBOARD_ADMIN: Buscando atividades recentes");
            
            $type = $_GET['type'] ?? 'all';
            $limit = $_GET['limit'] ?? 20;
            
            // Buscar atividades do system_logs
            $whereClause = $type !== 'all' ? "WHERE sl.action = ?" : "";
            
            $query = "SELECT sl.*, u.full_name as user_name, u.username as user_login
                      FROM system_logs sl
                      LEFT JOIN users u ON sl.user_id = u.id
                      $whereClause
                      ORDER BY sl.created_at DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            
            $params = [];
            if ($type !== 'all') {
                $params[] = $type;
            }
            $params[] = intval($limit);
            
            $stmt->execute($params);
            
            $activities = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activities[] = [
                    'id' => (int)$row['id'],
                    'type' => $row['action'],
                    'description' => $row['description'],
                    'user_name' => $row['user_name'],
                    'user_login' => $row['user_login'],
                    'module' => $row['module'],
                    'level' => $row['log_level'],
                    'created_at' => $row['created_at']
                ];
            }
            
            error_log("DASHBOARD_ADMIN: " . count($activities) . " atividades carregadas");
            Response::success(['activities' => $activities], 'Atividades carregadas com sucesso');
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN ACTIVITIES ERROR: " . $e->getMessage());
            Response::error('Erro ao carregar atividades: ' . $e->getMessage(), 500);
        }
    }
    
    public function getTransactions() {
        try {
            error_log("DASHBOARD_ADMIN: Buscando transações do caixa central, bônus de indicação e consultas");
            
            $limit = $_GET['limit'] ?? 50;
            
            // Query principal incluindo transações do caixa central, indicações E consultas
            $query = "
                (
                    SELECT 
                        cc.id,
                        cc.transaction_type as type,
                        cc.description,
                        cc.amount,
                        cc.balance_before,
                        cc.balance_after,
                        u.full_name as user_name,
                        cc.payment_method,
                        cc.created_at,
                        'central_cash' as source_table,
                        NULL as module_name
                    FROM central_cash cc
                    LEFT JOIN users u ON cc.user_id = u.id
                )
                UNION ALL
                (
                    SELECT 
                        CONCAT('wt_', wt.id) as id,
                        wt.type,
                        CASE 
                            WHEN wt.type = 'indicacao' THEN CONCAT('Bônus de Indicação - ', wt.description)
                            ELSE wt.description
                        END as description,
                        wt.amount,
                        wt.balance_before,
                        wt.balance_after,
                        u.full_name as user_name,
                        COALESCE(wt.payment_method, 'Sistema') as payment_method,
                        wt.created_at,
                        'wallet_transactions' as source_table,
                        NULL as module_name
                    FROM wallet_transactions wt
                    LEFT JOIN users u ON wt.user_id = u.id
                    WHERE wt.type = 'indicacao'
                )
                UNION ALL
                (
                    SELECT 
                        CONCAT('cons_', c.id) as id,
                        'consulta' as type,
                        CONCAT('Consulta: ', c.document) as description,
                        c.cost as amount,
                        0 as balance_before,
                        0 as balance_after,
                        u.full_name as user_name,
                        'saldo' as payment_method,
                        c.created_at,
                        'consultations' as source_table,
                        COALESCE(
                            JSON_UNQUOTE(JSON_EXTRACT(c.metadata, '$.module_title')),
                            CASE 
                                WHEN c.module_type = 'nome' THEN 'NOME COMPLETO'
                                WHEN c.module_type = 'cpf' THEN 'CPF'
                                ELSE UPPER(COALESCE(c.module_type, 'CONSULTA'))
                            END
                        ) as module_name
                    FROM consultations c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.status = 'completed' AND c.cost > 0
                )
                ORDER BY created_at DESC
                LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([intval($limit)]);
            
            $transactions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $transactions[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'description' => $row['description'],
                    'amount' => floatval($row['amount']),
                    'balance_before' => floatval($row['balance_before']),
                    'balance_after' => floatval($row['balance_after']),
                    'user_name' => $row['user_name'],
                    'payment_method' => $row['payment_method'],
                    'created_at' => $row['created_at'],
                    'source' => $row['source_table'],
                    'module_name' => $row['module_name']
                ];
            }
            
            error_log("DASHBOARD_ADMIN: " . count($transactions) . " transações carregadas (incluindo consultas e bônus)");
            Response::success(['transactions' => $transactions], 'Transações carregadas com sucesso');
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN TRANSACTIONS ERROR: " . $e->getMessage());
            Response::error('Erro ao carregar transações: ' . $e->getMessage(), 500);
        }
    }
    
    private function isUserOnline($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM user_sessions 
                     WHERE user_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND status = 'ativa'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($result['count']) > 0;
        } catch (Exception $e) {
            error_log("IS_USER_ONLINE ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    public function getOnlineUsers() {
        try {
            error_log("DASHBOARD_ADMIN: Buscando usuários online detalhados");
            
            // Buscar usuários com sessões ativas nos últimos 5 minutos
            $query = "SELECT DISTINCT u.id, u.username, u.email, u.full_name, u.cpf, u.telefone, 
                             u.tipoplano as plan, u.saldo as balance, u.status, u.user_role,
                             us.last_activity as last_login,
                             us.ip_address, us.user_agent,
                             COUNT(DISTINCT c.id) as total_consultations,
                             COALESCE(SUM(wt.amount), 0) as total_spent
                      FROM users u
                      INNER JOIN user_sessions us ON u.id = us.user_id
                      LEFT JOIN consultations c ON u.id = c.user_id
                      LEFT JOIN wallet_transactions wt ON u.id = wt.user_id AND wt.type = 'saida'
                      WHERE us.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                      AND us.status = 'ativa'
                      GROUP BY u.id, us.last_activity, us.ip_address, us.user_agent
                      ORDER BY us.last_activity DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $onlineUsers = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $onlineUsers[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['full_name'],
                    'email' => $row['email'],
                    'login' => $row['username'],
                    'cpf' => $row['cpf'],
                    'telefone' => $row['telefone'],
                    'plan' => $row['plan'],
                    'balance' => floatval($row['balance']),
                    'status' => $row['status'],
                    'user_role' => $row['user_role'],
                    'full_name' => $row['full_name'],
                    'total_consultations' => intval($row['total_consultations']),
                    'total_spent' => floatval($row['total_spent']),
                    'last_login' => $row['last_login'],
                    'ip_address' => $row['ip_address'],
                    'user_agent' => $row['user_agent'],
                    'is_online' => true
                ];
            }
            
            // Contar total de usuários online
            $countQuery = "SELECT COUNT(DISTINCT user_id) as total 
                          FROM user_sessions 
                          WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                          AND status = 'ativa'";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute();
            $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("DASHBOARD_ADMIN: " . count($onlineUsers) . " usuários online carregados");
            Response::success([
                'users' => $onlineUsers,
                'total' => intval($totalResult['total']),
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Usuários online carregados com sucesso');
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN ONLINE_USERS ERROR: " . $e->getMessage());
            Response::error('Erro ao carregar usuários online: ' . $e->getMessage(), 500);
        }
    }
    
    public function createUser() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            $required = ['email', 'full_name', 'user_role'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    Response::error("Campo {$field} é obrigatório", 400);
                    return;
                }
            }
            
            // Verificar se usuário já existe
            $checkQuery = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['email']]);
            
            if ($checkStmt->fetch()) {
                Response::error('Email já cadastrado', 400);
                return;
            }
            
            $this->db->beginTransaction();
            
            // Criar usuário
            $userQuery = "INSERT INTO users 
                         (username, email, password_hash, full_name, user_role, status, saldo, saldo_plano, tipoplano, aceite_termos, created_at) 
                         VALUES (?, ?, ?, ?, ?, 'ativo', ?, ?, ?, 1, NOW())";
            
            $username = explode('@', $data['email'])[0];
            $password = $data['password'] ?? '123456';
            $saldo = $data['saldo'] ?? 0;
            $saldoPlano = $data['saldo_plano'] ?? 0;
            $tipoplano = $data['tipoplano'] ?? 'Pré-Pago';
            
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->execute([
                $username,
                $data['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $data['full_name'],
                $data['user_role'],
                $saldo,
                $saldoPlano,
                $tipoplano
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Atualizar campos opcionais se fornecidos
            $optionalFields = ['cpf', 'cnpj', 'telefone', 'endereco', 'cep', 'cidade', 'estado', 'data_inicio', 'data_fim'];
            $updateFields = [];
            $updateParams = [];
            
            foreach ($optionalFields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $updateParams[] = $data[$field];
                }
            }
            
            if (!empty($updateFields)) {
                $updateParams[] = $userId;
                $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute($updateParams);
            }
            
            $this->db->commit();
            
            // Após commit, tratar plan_discount separadamente (não é coluna da tabela users)
            if (isset($data['plan_discount'])) {
                $this->updatePlanDiscount($userId, $data);
            }
            
            // Enviar notificação de notas se houver
            if (isset($data['notes']) && !empty(trim($data['notes']))) {
                require_once __DIR__ . '/../services/NotificationService.php';
                $notificationService = new NotificationService($this->db);
                $notificationService->createNotification(
                    $userId,
                    'info',
                    'Mensagem do Administrador',
                    $data['notes'],
                    null,
                    null,
                    'medium'
                );
            }
            
            Response::success([
                'id' => $userId,
                'email' => $data['email'],
                'full_name' => $data['full_name']
            ], 'Usuário criado com sucesso', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("DASHBOARD_ADMIN CREATE_USER ERROR: " . $e->getMessage());
            Response::error('Erro ao criar usuário: ' . $e->getMessage(), 500);
        }
    }
    
    public function updateUser($userId) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Buscar dados atuais do usuário antes de atualizar
            $checkQuery = "SELECT * FROM users WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId]);
            $currentUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }
            
            $allowedFields = [
                'full_name', 'email', 'user_role', 'status', 'saldo', 'saldo_plano', 
                'tipoplano', 'cpf', 'cnpj', 'telefone', 'endereco', 'cep', 'cidade', 'estado',
                'data_inicio', 'data_fim'
            ];
            
            $updateFields = [];
            $updateParams = [];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[] = "{$field} = ?";
                    $updateParams[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                Response::error('Nenhum dado válido para atualização', 400);
                return;
            }
            
            $updateParams[] = $userId;
            $query = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($updateParams);
            
            if ($result) {
                // Se houve mudança de plano, acumular dias restantes + dias do novo plano
                $this->handlePlanChangeWithDays($userId, $currentUser, $data);
                
                // Atualizar desconto do plano na subscription ativa (se enviado)
                $this->updatePlanDiscount($userId, $data);
                
                // Registrar transações de carteira para alterações de saldo
                $this->registerBalanceTransactions($userId, $currentUser, $data);
                
                // Enviar notificações sobre as alterações
                $this->sendUpdateNotifications($userId, $currentUser, $data);
                
                Response::success(null, 'Usuário atualizado com sucesso');
            } else {
                Response::error('Erro ao atualizar usuário', 500);
            }
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN UPDATE_USER ERROR: " . $e->getMessage());
            Response::error('Erro ao atualizar usuário: ' . $e->getMessage(), 500);
        }
    }
    
    private function handlePlanChangeWithDays($userId, $currentUser, $newData) {
        try {
            if (!isset($newData['tipoplano']) || $newData['tipoplano'] === $currentUser['tipoplano']) {
                return; // Sem mudança de plano
            }
            
            // Buscar duration_days e discount_percentage do novo plano
            $planQuery = "SELECT id, duration_days, discount_percentage FROM plans WHERE name = ? AND is_active = 1 LIMIT 1";
            $planStmt = $this->db->prepare($planQuery);
            $planStmt->execute([$newData['tipoplano']]);
            $newPlan = $planStmt->fetch(PDO::FETCH_ASSOC);
            
            $newPlanId = $newPlan ? (int)$newPlan['id'] : null;
            $newPlanDays = $newPlan ? (int)$newPlan['duration_days'] : 30;
            $newDiscountPercentage = $newPlan ? (int)$newPlan['discount_percentage'] : 0;
            
            // Se datas foram enviadas manualmente, usá-las em vez de recalcular
            if (!empty($newData['data_inicio']) && !empty($newData['data_fim'])) {
                $newStartDate = $newData['data_inicio'];
                $newEndDate = $newData['data_fim'];
                error_log("ADMIN_PLAN_CHANGE: User {$userId} - Usando datas manuais: {$newStartDate} a {$newEndDate}");
            } else {
                // Calcular dias restantes do plano atual
                $daysRemaining = 0;
                if (!empty($currentUser['data_fim'])) {
                    $endDate = new DateTime($currentUser['data_fim']);
                    $today = new DateTime();
                    if ($endDate > $today) {
                        $daysRemaining = $today->diff($endDate)->days;
                    }
                }
                
                // Somar dias restantes + dias do novo plano
                $totalDays = $daysRemaining + $newPlanDays;
                $newStartDate = date('Y-m-d');
                $newEndDate = date('Y-m-d', strtotime("+{$totalDays} days"));
            }
            
            // Atualizar datas do plano no users (sobrescreve o que foi salvo pelo allowedFields)
            $updateQuery = "UPDATE users SET data_inicio = ?, data_fim = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$newStartDate, $newEndDate, $userId]);
            
            // Atualizar user_subscriptions com o novo plano e desconto
            if ($newPlanId) {
                // Desativar assinaturas anteriores
                $deactivateQuery = "UPDATE user_subscriptions SET status = 'cancelled', updated_at = NOW() WHERE user_id = ? AND status = 'active'";
                $deactivateStmt = $this->db->prepare($deactivateQuery);
                $deactivateStmt->execute([$userId]);
                
                // Criar nova assinatura ativa com o novo plano
                $subscriptionQuery = "INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, end_date, payment_method, amount_paid, auto_renew, created_at) 
                                     VALUES (?, ?, 'active', ?, ?, 'admin', 0.00, 0, NOW())";
                $subscriptionStmt = $this->db->prepare($subscriptionQuery);
                $subscriptionStmt->execute([$userId, $newPlanId, $newStartDate, $newEndDate]);
                
                error_log("ADMIN_PLAN_CHANGE: User {$userId} - Subscription atualizada - Plan ID: {$newPlanId}, Desconto: {$newDiscountPercentage}%");
            }
            
            error_log("ADMIN_PLAN_CHANGE: User {$userId} - Plano: {$currentUser['tipoplano']} → {$newData['tipoplano']}, Fim: {$newEndDate}, Desconto: {$newDiscountPercentage}%");
            
        } catch (Exception $e) {
            error_log("HANDLE_PLAN_CHANGE ERROR: " . $e->getMessage());
        }
    }
    
    private function updatePlanDiscount($userId, $newData) {
        try {
            if (!isset($newData['plan_discount'])) {
                return;
            }
            
            $discount = (int)$newData['plan_discount'];
            
            // Buscar a subscription ativa do usuário
            $subQuery = "SELECT usub.id, usub.plan_id FROM user_subscriptions usub WHERE usub.user_id = ? AND usub.status = 'active' ORDER BY usub.id DESC LIMIT 1";
            $subStmt = $this->db->prepare($subQuery);
            $subStmt->execute([$userId]);
            $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscription) {
                // Atualizar o discount_percentage no plano associado à subscription
                $updateQuery = "UPDATE plans SET discount_percentage = ?, updated_at = NOW() WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$discount, $subscription['plan_id']]);
                
                error_log("ADMIN_DISCOUNT_UPDATE: User {$userId} - Plan ID: {$subscription['plan_id']}, Desconto atualizado para: {$discount}%");
            } else {
                // Sem subscription ativa - buscar o tipoplano do usuário e criar subscription
                $userQuery = "SELECT tipoplano, data_inicio, data_fim FROM users WHERE id = ?";
                $userStmt = $this->db->prepare($userQuery);
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                $planName = $userData['tipoplano'] ?? 'Pré-Pago';
                
                // Buscar ou criar o plano na tabela plans
                $planQuery = "SELECT id FROM plans WHERE name = ? LIMIT 1";
                $planStmt = $this->db->prepare($planQuery);
                $planStmt->execute([$planName]);
                $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$plan) {
                    // Criar o plano se não existir (ex: Pré-Pago)
                    $createPlanQuery = "INSERT INTO plans (name, price, discount_percentage, is_active, created_at, updated_at) VALUES (?, 0, ?, 1, NOW(), NOW())";
                    $createPlanStmt = $this->db->prepare($createPlanQuery);
                    $createPlanStmt->execute([$planName, $discount]);
                    $planId = $this->db->lastInsertId();
                    error_log("ADMIN_DISCOUNT_UPDATE: Plano '{$planName}' criado com ID {$planId}");
                } else {
                    $planId = $plan['id'];
                    // Atualizar desconto no plano existente
                    $updatePlanQuery = "UPDATE plans SET discount_percentage = ?, updated_at = NOW() WHERE id = ?";
                    $updatePlanStmt = $this->db->prepare($updatePlanQuery);
                    $updatePlanStmt->execute([$discount, $planId]);
                }
                
                // Criar subscription ativa para o usuário
                $startDate = $userData['data_inicio'] ?? date('Y-m-d');
                $endDate = $userData['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
                
                $createSubQuery = "INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, end_date, payment_method, amount_paid, auto_renew, created_at) VALUES (?, ?, 'active', ?, ?, 'admin', 0.00, 0, NOW())";
                $createSubStmt = $this->db->prepare($createSubQuery);
                $createSubStmt->execute([$userId, $planId, $startDate, $endDate]);
                
                error_log("ADMIN_DISCOUNT_UPDATE: User {$userId} - Subscription criada para plano '{$planName}' (ID: {$planId}), Desconto: {$discount}%");
            }
            
        } catch (Exception $e) {
            error_log("UPDATE_PLAN_DISCOUNT ERROR: " . $e->getMessage());
        }
    }
    
    private function registerBalanceTransactions($userId, $currentUser, $newData) {
        try {
            require_once __DIR__ . '/../services/WalletService.php';
            $walletService = new WalletService($this->db);
            
            // Transação para saldo da carteira (main)
            if (isset($newData['saldo'])) {
                $oldSaldo = (float)($currentUser['saldo'] ?? 0);
                $newSaldo = (float)$newData['saldo'];
                $diff = $newSaldo - $oldSaldo;
                
                if ($diff != 0) {
                    $type = $diff > 0 ? 'entrada' : 'saida';
                    $description = $diff > 0 
                        ? 'Créditos adicionados pelo Administrador' 
                        : 'Saldo ajustado pelo Administrador';
                    
                    // Inserir transação diretamente para evitar validação de saldo insuficiente
                    $transactionQuery = "INSERT INTO wallet_transactions (
                        user_id, wallet_type, type, amount, balance_before, balance_after, 
                        description, reference_type, status, created_at
                    ) VALUES (?, 'main', ?, ?, ?, ?, ?, 'admin_adjustment', 'completed', NOW())";
                    
                    $stmt = $this->db->prepare($transactionQuery);
                    $stmt->execute([
                        $userId, $type, abs($diff), $oldSaldo, $newSaldo, $description
                    ]);
                    
                    error_log("ADMIN_BALANCE: Transação carteira registrada - User {$userId}, Diff: R$ {$diff}");
                }
            }
            
            // Transação para saldo do plano
            if (isset($newData['saldo_plano'])) {
                $oldSaldoPlano = (float)($currentUser['saldo_plano'] ?? 0);
                $newSaldoPlano = (float)$newData['saldo_plano'];
                $diffPlano = $newSaldoPlano - $oldSaldoPlano;
                
                if ($diffPlano != 0) {
                    $type = $diffPlano > 0 ? 'entrada' : 'saida';
                    $description = $diffPlano > 0 
                        ? 'Créditos de plano adicionados pelo Administrador' 
                        : 'Saldo do plano ajustado pelo Administrador';
                    
                    $transactionQuery = "INSERT INTO wallet_transactions (
                        user_id, wallet_type, type, amount, balance_before, balance_after, 
                        description, reference_type, status, created_at
                    ) VALUES (?, 'plan', ?, ?, ?, ?, ?, 'admin_adjustment', 'completed', NOW())";
                    
                    $stmt = $this->db->prepare($transactionQuery);
                    $stmt->execute([
                        $userId, $type, abs($diffPlano), $oldSaldoPlano, $newSaldoPlano, $description
                    ]);
                    
                    error_log("ADMIN_BALANCE: Transação plano registrada - User {$userId}, Diff: R$ {$diffPlano}");
                }
            }
            
        } catch (Exception $e) {
            error_log("REGISTER_BALANCE_TRANSACTIONS ERROR: " . $e->getMessage());
        }
    }
    
    private function sendUpdateNotifications($userId, $currentUser, $newData) {
        try {
            require_once __DIR__ . '/../services/NotificationService.php';
            $notificationService = new NotificationService($this->db);
            
            // Notificação de alteração de plano
            if (isset($newData['tipoplano']) && $newData['tipoplano'] !== $currentUser['tipoplano']) {
                // Calcular dias para a notificação
                $planQuery = "SELECT duration_days FROM plans WHERE name = ? AND is_active = 1 LIMIT 1";
                $planStmt = $this->db->prepare($planQuery);
                $planStmt->execute([$newData['tipoplano']]);
                $newPlan = $planStmt->fetch(PDO::FETCH_ASSOC);
                $newPlanDays = $newPlan ? (int)$newPlan['duration_days'] : 30;
                
                $daysRemaining = 0;
                if (!empty($currentUser['data_fim'])) {
                    $endDate = new DateTime($currentUser['data_fim']);
                    $today = new DateTime();
                    if ($endDate > $today) {
                        $daysRemaining = $today->diff($endDate)->days;
                    }
                }
                $totalDays = $daysRemaining + $newPlanDays;
                
                $message = 'O Administrador atualizou o seu plano de "' . ($currentUser['tipoplano'] ?? 'Nenhum') . '" para "' . $newData['tipoplano'] . '". ';
                $message .= 'Dias restantes do plano anterior: ' . $daysRemaining . ' + Dias do novo plano: ' . $newPlanDays . ' = Total: ' . $totalDays . ' dias.';
                
                $notificationService->createNotification(
                    $userId,
                    'system',
                    'Plano Atualizado pelo Administrador',
                    $message,
                    null,
                    null,
                    'high'
                );
            }
            
            // Notificação de alteração de saldo da carteira
            if (isset($newData['saldo'])) {
                $oldSaldo = (float)($currentUser['saldo'] ?? 0);
                $newSaldo = (float)$newData['saldo'];
                $diff = $newSaldo - $oldSaldo;
                
                if ($diff != 0) {
                    $formattedDiff = 'R$ ' . number_format(abs($diff), 2, ',', '.');
                    if ($diff > 0) {
                        $notificationService->createNotification(
                            $userId,
                            'success',
                            'Créditos Recebidos na Carteira',
                            'Você recebeu créditos de ' . $formattedDiff . ' na sua carteira pelo Administrador. Saldo anterior: R$ ' . number_format($oldSaldo, 2, ',', '.') . ' → Novo saldo: R$ ' . number_format($newSaldo, 2, ',', '.'),
                            null,
                            null,
                            'high'
                        );
                    } else {
                        $notificationService->createNotification(
                            $userId,
                            'warning',
                            'Saldo da Carteira Ajustado',
                            'O Administrador ajustou o saldo da sua carteira. Foram removidos ' . $formattedDiff . '. Saldo anterior: R$ ' . number_format($oldSaldo, 2, ',', '.') . ' → Novo saldo: R$ ' . number_format($newSaldo, 2, ',', '.'),
                            null,
                            null,
                            'high'
                        );
                    }
                }
            }
            
            // Notificação de alteração de saldo do plano
            if (isset($newData['saldo_plano'])) {
                $oldSaldoPlano = (float)($currentUser['saldo_plano'] ?? 0);
                $newSaldoPlano = (float)$newData['saldo_plano'];
                $diffPlano = $newSaldoPlano - $oldSaldoPlano;
                
                if ($diffPlano != 0) {
                    $formattedDiff = 'R$ ' . number_format(abs($diffPlano), 2, ',', '.');
                    if ($diffPlano > 0) {
                        $notificationService->createNotification(
                            $userId,
                            'success',
                            'Créditos Recebidos no Plano',
                            'Você recebeu créditos de ' . $formattedDiff . ' no saldo do plano pelo Administrador. Saldo anterior: R$ ' . number_format($oldSaldoPlano, 2, ',', '.') . ' → Novo saldo: R$ ' . number_format($newSaldoPlano, 2, ',', '.'),
                            null,
                            null,
                            'high'
                        );
                    } else {
                        $notificationService->createNotification(
                            $userId,
                            'warning',
                            'Saldo do Plano Ajustado',
                            'O Administrador ajustou o saldo do seu plano. Foram removidos ' . $formattedDiff . '. Saldo anterior: R$ ' . number_format($oldSaldoPlano, 2, ',', '.') . ' → Novo saldo: R$ ' . number_format($newSaldoPlano, 2, ',', '.'),
                            null,
                            null,
                            'high'
                        );
                    }
                }
            }
            
            // Notificação de observações
            if (isset($newData['notes']) && !empty(trim($newData['notes']))) {
                $notificationService->createNotification(
                    $userId,
                    'info',
                    'Mensagem do Administrador',
                    $newData['notes'],
                    null,
                    null,
                    'medium'
                );
            }
            
            // Notificação de alteração de status
            if (isset($newData['status']) && $newData['status'] !== $currentUser['status']) {
                $statusText = $newData['status'] === 'ativo' ? 'ativada' : 'desativada';
                $notificationService->createNotification(
                    $userId,
                    'system',
                    'Conta ' . ucfirst($statusText),
                    'A sua conta foi ' . $statusText . ' pelo Administrador.',
                    null,
                    null,
                    'high'
                );
            }
            
            // Notificação de alteração de nome
            if (isset($newData['full_name']) && $newData['full_name'] !== $currentUser['full_name']) {
                $notificationService->createNotification(
                    $userId,
                    'info',
                    'Dados Atualizados',
                    'O Administrador atualizou o seu nome de "' . ($currentUser['full_name'] ?? '') . '" para "' . $newData['full_name'] . '".',
                    null,
                    null,
                    'low'
                );
            }
            
        } catch (Exception $e) {
            error_log("SEND_UPDATE_NOTIFICATIONS ERROR: " . $e->getMessage());
        }
    }
    
    public function toggleUserStatus($userId) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['status'])) {
                Response::error('Status é obrigatório', 400);
                return;
            }
            
            $query = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([$data['status'], $userId])) {
                Response::success(null, 'Status do usuário atualizado');
            } else {
                Response::error('Erro ao atualizar status', 400);
            }
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN TOGGLE_STATUS ERROR: " . $e->getMessage());
            Response::error('Erro ao atualizar status: ' . $e->getMessage(), 500);
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Verificar se userId é válido
            if (!$userId || !is_numeric($userId)) {
                Response::error('ID de usuário inválido', 400);
                return;
            }
            
            // Verificar se usuário existe
            $checkQuery = "SELECT id FROM users WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId]);
            
            if (!$checkStmt->fetch()) {
                Response::error('Usuário não encontrado', 404);
                return;
            }
            
            $this->db->beginTransaction();
            
            // Excluir dados relacionados em ordem para evitar problemas de chave estrangeira
            // Usando try-catch individual para cada tabela (algumas podem não existir)
            
            $tablesToClean = [
                ['table' => 'user_sessions', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'user_subscriptions', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'user_audit', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'user_wallets', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'wallet_transactions', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'user_settings', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'user_profiles', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'consultations', 'column' => 'user_id', 'action' => 'DELETE'],
                ['table' => 'notifications', 'column' => 'user_id', 'action' => 'DELETE'],
            ];
            
            foreach ($tablesToClean as $tableInfo) {
                try {
                    $query = "DELETE FROM {$tableInfo['table']} WHERE {$tableInfo['column']} = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$userId]);
                } catch (Exception $e) {
                    // Tabela pode não existir - continuar
                    error_log("DELETE_USER: Tabela {$tableInfo['table']} não encontrada ou erro: " . $e->getMessage());
                }
            }
            
            // Atualizar referências para NULL (não deletar)
            $tablesToNullify = [
                ['table' => 'central_cash', 'column' => 'user_id'],
                ['table' => 'system_logs', 'column' => 'user_id'],
            ];
            
            foreach ($tablesToNullify as $tableInfo) {
                try {
                    $query = "UPDATE {$tableInfo['table']} SET {$tableInfo['column']} = NULL WHERE {$tableInfo['column']} = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$userId]);
                } catch (Exception $e) {
                    error_log("DELETE_USER: Erro ao nullificar {$tableInfo['table']}: " . $e->getMessage());
                }
            }
            
            // Tratar indicações
            try {
                $indicationQuery = "DELETE FROM indicacoes WHERE indicado_id = ?";
                $indicationStmt = $this->db->prepare($indicationQuery);
                $indicationStmt->execute([$userId]);
            } catch (Exception $e) {
                error_log("DELETE_USER: Tabela indicacoes não encontrada: " . $e->getMessage());
            }
            
            // Atualizar usuários que este usuário indicou
            try {
                $updateIndicatorQuery = "UPDATE users SET indicador_id = NULL WHERE indicador_id = ?";
                $updateIndicatorStmt = $this->db->prepare($updateIndicatorQuery);
                $updateIndicatorStmt->execute([$userId]);
            } catch (Exception $e) {
                error_log("DELETE_USER: Erro ao atualizar indicador_id: " . $e->getMessage());
            }
            
            // Finalmente excluir o usuário
            $deleteUserQuery = "DELETE FROM users WHERE id = ?";
            $deleteUserStmt = $this->db->prepare($deleteUserQuery);
            $result = $deleteUserStmt->execute([$userId]);
            
            if (!$result) {
                throw new Exception('Falha ao excluir usuário da tabela principal');
            }
            
            $this->db->commit();
            
            Response::success(null, 'Usuário excluído permanentemente com sucesso');
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("DASHBOARD_ADMIN DELETE_USER ERROR: " . $e->getMessage());
            error_log("DASHBOARD_ADMIN DELETE_USER STACK: " . $e->getTraceAsString());
            Response::error('Erro ao excluir usuário: ' . $e->getMessage(), 500);
        }
    }
    
    public function resetUserPassword($userId) {
        try {
            // Verificar se userId é válido
            if (!$userId || !is_numeric($userId)) {
                Response::error('ID de usuário inválido', 400);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $newPassword = isset($data['new_password']) && !empty($data['new_password']) 
                ? $data['new_password'] 
                : '123456';
            
            // Validar senha mínima
            if (strlen($newPassword) < 6) {
                Response::error('A senha deve ter no mínimo 6 caracteres', 400);
                return;
            }
            
            // Verificar se usuário existe
            $checkQuery = "SELECT id FROM users WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId]);
            
            if (!$checkStmt->fetch()) {
                Response::error('Usuário não encontrado', 404);
                return;
            }
            
            $query = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId])) {
                Response::success(null, 'Senha resetada com sucesso');
            } else {
                Response::error('Erro ao resetar senha', 500);
            }
            
        } catch (Exception $e) {
            error_log("DASHBOARD_ADMIN RESET_PASSWORD ERROR: " . $e->getMessage());
            Response::error('Erro ao resetar senha: ' . $e->getMessage(), 500);
        }
    }
}
