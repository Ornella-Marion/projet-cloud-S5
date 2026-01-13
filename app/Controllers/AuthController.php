<?php
class AuthController {
    public function signup() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'email and password required']);
            return;
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'email already exists']);
            return;
        }
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $name = $input['name'] ?? null;
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?,?,?)');
        $stmt->execute([$name, $input['email'], $hash]);
        http_response_code(201);
        echo json_encode(['status' => 'created']);
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'email and password required']);
            return;
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
        $stmt->execute([$input['email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($input['password'], $row['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid credentials']);
            return;
        }
        // create simple token and set expiry
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $pdo->prepare('UPDATE users SET session_token = ?, session_expires = ? WHERE id = ?');
        $stmt->execute([$token, $expires, $row['id']]);
        echo json_encode(['token' => $token, 'expires' => $expires]);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'not found']);
            return;
        }
        $name = $input['name'] ?? null;
        $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
        echo json_encode(['status' => 'updated']);
    }
}
