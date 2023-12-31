<?php
session_start();
include 'conn.php';

//import script autoload agar bisa menggunakan library
require_once('./vendor/autoload.php');
use Firebase\JWT\JWT;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set Content-Type header agar respons yang dikirim ke klien dibaca sebagai JSON
header('Content-Type: application/json');

// Validasi method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Metode tidak diizinkan']);
    exit();
}

// Ambil data dari input JSON
$input = json_decode(file_get_contents('php://input'));

// Periksa apakah input sesuai dengan format yang diharapkan
if (!isset($input->email) || !isset($input->password)) {
    http_response_code(400);
    echo json_encode(['message' => 'Format input tidak sesuai']);
    exit();
}

$email = $input->email;
$password = $input->password;

// Ambil data pengguna dari database berdasarkan email
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if ($row) {
    // Periksa apakah pengguna telah dikunci
    if ($row['locked'] == 1) {
        http_response_code(401);
        echo json_encode(['message' => 'Akun Anda telah dikunci. Silakan hubungi administrator.']);
    } else {
        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Reset jumlah percobaan login yang gagal
            $sql_reset = "UPDATE users SET login_attempts = 0 WHERE email = '$email'";
            mysqli_query($conn, $sql_reset);

            // Buat payload token
            $payload = [
                'email' => $email,
                'exp' => time() + (15 * 60) // Token kadaluarsa dalam 15 menit
            ];

            // Generate token menggunakan library Firebase JWT
            $access_token = JWT::encode($payload, $_ENV['ACCESS_TOKEN_SECRET'], 'HS256');

            // Kirim token sebagai respons JSON
            echo json_encode([
                'message' => 'Login berhasil',
                'accessToken' => $access_token,
            ]);
        } else {
            // Login gagal
            $sql = "UPDATE users SET login_attempts = login_attempts + 1 WHERE email = '$email'";
            mysqli_query($conn, $sql);

            if ($row['login_attempts'] >= 3) {
                http_response_code(401);
                // Jika sudah mencapai 3 kali percobaan, kunci email
                $sql = "UPDATE users SET locked = 1 WHERE email = '$email'";
                mysqli_query($conn, $sql);
                echo json_encode(['message' => 'Akun Anda telah dikunci. Silakan hubungi administrator.']);
            } else {
                http_response_code(401);
                echo json_encode(['message' => 'Password salah. Sisa percobaan: ' . (3 - $row['login_attempts'])]);
            }
        }
    }
} else {
    echo json_encode(['message' => 'Username tidak ditemukan']);
}

mysqli_close($conn);
?>
