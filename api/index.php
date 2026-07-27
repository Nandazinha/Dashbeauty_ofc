<?php
// ============================================
// DASHBEAUTY API - COMPLETA
// ============================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONEXÃO COM O BANCO DE DADOS
// ============================================
$host = 'localhost';
$dbname = 'dashbeauty';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $e->getMessage()]));
}

// ============================================
// FUNÇÕES JWT
// ============================================
function generateToken($user_id, $email, $user_type) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'user_type' => $user_type,
        'exp' => time() + 86400
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", 'dashbeauty_secret_2024', true);
    $signature = base64_encode($signature);
    return "$header.$payload.$signature";
}

function validateToken() {
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    $token = str_replace('Bearer ', '', $auth);
    
    $parts = explode('.', $token);
    if (count($parts) != 3) return null;
    
    $signature = hash_hmac('sha256', "$parts[0].$parts[1]", 'dashbeauty_secret_2024', true);
    $signature = base64_encode($signature);
    
    if ($signature !== $parts[2]) return null;
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if ($payload['exp'] < time()) return null;
    
    return $payload;
}

// ============================================
// ROTEAMENTO
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/TCC/Dashbeauty/api', '', $path);
$path = str_replace('/api', '', $path);
$segments = explode('/', trim($path, '/'));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    // ============================================
    // ROTA DE TESTE
    // ============================================
    if ($resource === '' || $resource === 'test') {
        echo json_encode(['success' => true, 'message' => 'API DashBeauty funcionando!']);
        exit();
    }
    
    // ============================================
    // AUTENTICAÇÃO
    // ============================================
    if ($resource === 'auth') {
        // LOGIN
        if ($method === 'POST' && $id === 'login') {
            $stmt = $pdo->prepare("SELECT u.*, b.id as business_id, b.business_name, b.logo, b.description as business_description 
                                   FROM users u 
                                   LEFT JOIN businesses b ON u.id = b.user_id 
                                   WHERE u.email = ? AND u.password = MD5(?)");
            $stmt->execute([$input['email'], $input['password']]);
            $user = $stmt->fetch();
            
            if ($user) {
                $token = generateToken($user['id'], $user['email'], $user['user_type']);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'user_id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'photo' => $user['photo'],
                        'user_type' => $user['user_type'],
                        'business_id' => $user['business_id'],
                        'business_name' => $user['business_name'],
                        'logo' => $user['logo'],
                        'token' => $token
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email ou senha inválidos']);
            }
            exit();
        }
        
        // REGISTRO
        if ($method === 'POST' && $id === 'register') {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
                exit();
            }
            
            // Inserir usuário
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone, user_type) VALUES (?, MD5(?), ?, ?, ?)");
            $stmt->execute([$input['email'], $input['password'], $input['name'], $input['phone'], $input['user_type']]);
            $userId = $pdo->lastInsertId();
            
            // Se for empresa, criar registro na tabela businesses
            if ($input['user_type'] === 'business') {
                $businessName = $input['business_name'] ?? $input['name'];
                $stmt = $pdo->prepare("INSERT INTO businesses (user_id, business_name, description) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $businessName, $input['description'] ?? '']);
            }
            
            $token = generateToken($userId, $input['email'], $input['user_type']);
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso',
                'data' => [
                    'user_id' => $userId,
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'user_type' => $input['user_type'],
                    'token' => $token
                ]
            ]);
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Rota auth não encontrada']);
        exit();
    }
    
    // ============================================
    // EMPRESAS
    // ============================================
    if ($resource === 'businesses') {
        // LISTAR TODAS
        if ($method === 'GET' && !$id) {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $stmt = $pdo->prepare("
                SELECT b.*, u.name as owner_name, u.phone as owner_phone,
                (SELECT AVG(rating) FROM reviews r 
                 JOIN appointments a ON r.appointment_id = a.id 
                 JOIN services s ON a.service_id = s.id 
                 WHERE s.business_id = b.id) as avg_rating
                FROM businesses b
                JOIN users u ON b.user_id = u.id
                WHERE b.business_name LIKE ? OR b.description LIKE ?
                ORDER BY b.is_featured DESC
            ");
            $stmt->execute([$search, $search]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit();
        }
        
        // BUSCAR UMA EMPRESA
        if ($method === 'GET' && $id) {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name as owner_name, u.email, u.phone as owner_phone,
                (SELECT AVG(rating) FROM reviews r 
                 JOIN appointments a ON r.appointment_id = a.id 
                 JOIN services s ON a.service_id = s.id 
                 WHERE s.business_id = b.id) as avg_rating
                FROM businesses b
                JOIN users u ON b.user_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $business = $stmt->fetch();
            
            if ($business) {
                // Buscar serviços
                $stmt = $pdo->prepare("SELECT * FROM services WHERE business_id = ? AND is_active = 1");
                $stmt->execute([$id]);
                $business['services'] = $stmt->fetchAll();
                
                // Buscar horários
                $stmt = $pdo->prepare("SELECT * FROM business_hours WHERE business_id = ? ORDER BY day_of_week");
                $stmt->execute([$id]);
                $business['hours'] = $stmt->fetchAll();
                
                // Buscar avaliações
                $stmt = $pdo->prepare("
                    SELECT r.*, u.name as client_name 
                    FROM reviews r
                    JOIN appointments a ON r.appointment_id = a.id
                    JOIN users u ON a.client_id = u.id
                    WHERE a.service_id IN (SELECT id FROM services WHERE business_id = ?)
                    ORDER BY r.created_at DESC LIMIT 10
                ");
                $stmt->execute([$id]);
                $business['reviews'] = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'data' => $business]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);
            }
            exit();
        }
        
        // ATUALIZAR EMPRESA
        if ($method === 'PUT' && $id) {
            $userData = validateToken();
            if (!$userData) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autorizado']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                UPDATE businesses SET 
                    business_name = ?,
                    description = ?,
                    logo = ?,
                    address = ?,
                    whatsapp = ?,
                    instagram = ?,
                    facebook = ?,
                    website = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $input['business_name'],
                $input['description'],
                $input['logo'] ?? null,
                $input['address'] ?? null,
                $input['whatsapp'] ?? null,
                $input['instagram'] ?? null,
                $input['facebook'] ?? null,
                $input['website'] ?? null,
                $id,
                $userData['user_id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Empresa atualizada com sucesso']);
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Rota businesses não encontrada']);
        exit();
    }
    
    // ============================================
    // SERVIÇOS
    // ============================================
    if ($resource === 'services') {
        $userData = validateToken();
        
        // LISTAR SERVIÇOS
        if ($method === 'GET' && isset($_GET['business_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM services WHERE business_id = ? AND is_active = 1");
            $stmt->execute([$_GET['business_id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit();
        }
        
        // CRIAR SERVIÇO
        if ($method === 'POST') {
            if (!$userData) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autorizado']);
                exit();
            }
            
            // Buscar business_id do usuário
            $stmt = $pdo->prepare("SELECT id FROM businesses WHERE user_id = ?");
            $stmt->execute([$userData['user_id']]);
            $business = $stmt->fetch();
            
            if (!$business) {
                echo json_encode(['success' => false, 'message' => 'Empresa não encontrada']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO services (business_id, name, description, price, duration_minutes, category) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $business['id'],
                $input['name'],
                $input['description'],
                $input['price'],
                $input['duration'],
                $input['category']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Serviço criado com sucesso', 'id' => $pdo->lastInsertId()]);
            exit();
        }
        
        // DELETAR SERVIÇO
        if ($method === 'DELETE' && $id) {
            if (!$userData) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autorizado']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Serviço removido com sucesso']);
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Rota services não encontrada']);
        exit();
    }
    
    // ============================================
    // AGENDAMENTOS
    // ============================================
    if ($resource === 'appointments') {
        $userData = validateToken();
        if (!$userData) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit();
        }
        
        // LISTAR AGENDAMENTOS DO CLIENTE
        if ($method === 'GET' && $userData['user_type'] === 'client') {
            $stmt = $pdo->prepare("
                SELECT a.*, s.name as service_name, b.business_name, b.id as business_id
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN businesses b ON s.business_id = b.id
                WHERE a.client_id = ?
                ORDER BY a.appointment_date DESC
            ");
            $stmt->execute([$userData['user_id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit();
        }
        
        // LISTAR AGENDAMENTOS DA EMPRESA
        if ($method === 'GET' && $userData['user_type'] === 'business') {
            $stmt = $pdo->prepare("
                SELECT a.*, s.name as service_name, u.name as client_name
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN users u ON a.client_id = u.id
                WHERE s.business_id IN (SELECT id FROM businesses WHERE user_id = ?)
                ORDER BY a.appointment_date DESC
            ");
            $stmt->execute([$userData['user_id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit();
        }
        
        // CRIAR AGENDAMENTO
        if ($method === 'POST') {
            if ($userData['user_type'] !== 'client') {
                echo json_encode(['success' => false, 'message' => 'Apenas clientes podem agendar']);
                exit();
            }
            
            // Verificar disponibilidade (opcional)
            
            $stmt = $pdo->prepare("
                INSERT INTO appointments (service_id, client_id, appointment_date, appointment_time, price, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['service_id'],
                $userData['user_id'],
                $input['appointment_date'],
                $input['appointment_time'],
                $input['price'],
                $input['notes'] ?? ''
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Agendamento realizado com sucesso', 'id' => $pdo->lastInsertId()]);
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Rota appointments não encontrada']);
        exit();
    }
    
    // ============================================
    // FAVORITOS
    // ============================================
    if ($resource === 'favorites') {
        $userData = validateToken();
        if (!$userData) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit();
        }
        
        // LISTAR FAVORITOS
        if ($method === 'GET') {
            $stmt = $pdo->prepare("
                SELECT b.* FROM favorites f
                JOIN businesses b ON f.business_id = b.id
                WHERE f.user_id = ?
            ");
            $stmt->execute([$userData['user_id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit();
        }
        
        // ADICIONAR FAVORITO
        if ($method === 'POST') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, business_id) VALUES (?, ?)");
            $stmt->execute([$userData['user_id'], $input['business_id']]);
            echo json_encode(['success' => true, 'message' => 'Adicionado aos favoritos']);
            exit();
        }
        
        // REMOVER FAVORITO
        if ($method === 'DELETE' && $id) {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND business_id = ?");
            $stmt->execute([$userData['user_id'], $id]);
            echo json_encode(['success' => true, 'message' => 'Removido dos favoritos']);
            exit();
        }
        
        echo json_encode(['success' => false, 'message' => 'Rota favorites não encontrada']);
        exit();
    }
    
    // ============================================
    // ROTA PADRÃO
    // ============================================
    echo json_encode([
        'success' => true,
        'message' => 'API DashBeauty funcionando!',
        'version' => '2.0.0',
        'endpoints' => [
            'POST /auth/login' => 'Login',
            'POST /auth/register' => 'Registro',
            'GET /businesses' => 'Listar empresas',
            'GET /businesses/{id}' => 'Detalhes da empresa',
            'PUT /businesses/{id}' => 'Atualizar empresa',
            'GET /services?business_id={id}' => 'Serviços da empresa',
            'POST /services' => 'Criar serviço',
            'GET /appointments' => 'Meus agendamentos',
            'POST /appointments' => 'Criar agendamento',
            'GET /favorites' => 'Meus favoritos',
            'POST /favorites' => 'Adicionar favorito',
            'DELETE /favorites/{id}' => 'Remover favorito'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>