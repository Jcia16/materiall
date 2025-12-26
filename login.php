<?php
// Mulai session (hanya di sini, jangan di function.php)
session_start();
require 'function.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['log']) && $_SESSION['log'] === true) {
    header('location:index.php');
    exit;
}


// Cek jika tombol login ditekan
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Jalankan query ke database
    $result = mysqli_query($conn, "SELECT * FROM login WHERE username='$username' AND password='$password'");

    // Jika query error, tampilkan pesan agar mudah debug
    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }

    // Hitung jumlah baris hasil query
    $cek = mysqli_num_rows($result);

    if ($cek > 0) {
        // Login berhasil
        $_SESSION['log'] = true;
        $_SESSION['username'] = $username;
        header('location:index.php');
        exit;
    } else {
        // Login gagal
        echo "<script>alert('Username atau Password salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CV. Dwiasta Konstruksi</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #3a49d2ff, #8b8a8aff);
            min-height: 100vh;
        }

        .login-card {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            animation: fadeIn 0.6s ease-in-out;
        }

        .login-card h3 {
            font-weight: 600;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
        }

        .btn-login {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, #4464e6ff, #c6c1cbff);
            border: none;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center">

    <div class="col-md-4 col-sm-10">
        <div class="card login-card border-0">
            <div class="card-body p-4">

                <h3 class="text-center mb-1">Welcome Back</h3>
                <p class="text-center text-muted mb-4">Silakan login ke sistem</p>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-login text-white w-100 mt-2">
                        Login
                    </button>
                </form>

            </div>
        </div>

        <p class="text-center text-white mt-3 small">
            © <?= date('Y'); ?> CV. Dwiasta Konstruksi
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
