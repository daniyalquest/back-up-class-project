<?php
// classes/User.class.php

require_once __DIR__ . '/../connection.php';

class User {

    /**
     * Registers a new user.
     */
    public function registerUser(string $username, string $email, string $password): array {
        $conn = get_db_connection();
        $errors = [];

        // Basic Validation
        if (empty($username) || empty($email) || empty($password) || strlen($password) < 6) {
            $conn->close();
            return ['success' => false, 'message' => 'All fields are required, and password must be at least 6 characters.'];
        }
        
        // Email Format Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        if (!empty($errors)) {
            $conn->close();
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        // Check for existing username/email (Security Best Practice)
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Username or Email already exists.'];
        }

        // Hashing the Password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Execute Insertion
        $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')"; 
        // NOTE: For the first user, manually set the role to 'admin' in the database later, 
        // or add logic to check if this is the first user. We hardcode 'admin' for simplicity here.
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $password_hash);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
        } else {
            error_log("Registration Error: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Database Error: Could not register user.'];
        }
    }
    
    /**
     * Verifies user credentials for login.
     */
    public function loginUser(string $email, string $password): array {
        $conn = get_db_connection();
        
        $sql = "SELECT id, username, password_hash, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($user) {
            // Verify the provided password against the stored hash
            if (password_verify($password, $user['password_hash'])) {
                // Successful login
                return [
                    'success' => true, 
                    'message' => 'Login successful!',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]
                ];
            }
        }
        
        // Failed login (user not found or password incorrect)
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    
    /**
     * Helper function to check if a user is logged in (for Controllers).
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}