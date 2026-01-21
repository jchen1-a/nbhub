<?php
// 在 login.php 和 register.php 的最开头添加
require_once __DIR__ . '/config.php';

// 如果用户已登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Naraka Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .login-header .logo {
            font-size: 40px;
            color: #4a6fa5;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4a6fa5;
            box-shadow: 0 0 0 2px rgba(74, 111, 165, 0.2);
        }
        
        .btn-login-submit {
            width: 100%;
            padding: 14px;
            background-color: #4a6fa5;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login-submit:hover {
            background-color: #3a5a85;
        }
        
        .login-options {
            margin-top: 20px;
            text-align: center;
        }
        
        .login-options a {
            color: #4a6fa5;
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-options a:hover {
            text-decoration: underline;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .divider span {
            padding: 0 15px;
            color: #666;
            font-size: 14px;
        }
        
        .guest-option {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.html" class="logo">
                <i class="fas fa-gamepad"></i>
                <span>Naraka Hub</span>
            </a>
            
            <div class="nav-main-links">
                <a href="index.html">Inicio</a>
                <a href="wiki.html">Wiki</a>
                <a href="guides.html">Guías</a>
                <a href="forum.html">Foro</a>
            </div>
            
            <div class="nav-auth">
                <a href="register.html" class="btn-register">Registrarse</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="login-container">
                <div class="login-header">
                    <div class="logo">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h1>Iniciar Sesión</h1>
                    <p>Ingresa a tu cuenta de Naraka Hub</p>
                </div>
                
                <div id="errorMessage" class="error-message"></div>
                <div id="successMessage" class="success-message"></div>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" class="form-control" placeholder="tu@email.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" class="form-control" placeholder="Tu contraseña" required>
                    </div>
                    
                    <button type="submit" class="btn-login-submit">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>
                
                <div class="login-options">
                    <a href="forgot-password.html">¿Olvidaste tu contraseña?</a>
                </div>
                
                <div class="divider">
                    <span>O</span>
                </div>
                
                <div class="guest-option">
                    <p>¿No tienes una cuenta? <a href="register.html">Regístrate aquí</a></p>
                    <p>¿Prefieres explorar sin cuenta? <a href="index.html">Continuar como invitado</a></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Reset messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            // Simple validation
            if (!email || !password) {
                errorMessage.textContent = 'Por favor, completa todos los campos.';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Simulate login process (in a real app, this would be an API call)
            // For demo purposes, we'll use mock credentials
            const mockUsers = [
                { email: 'usuario@ejemplo.com', password: '123456', name: 'Usuario Demo' },
                { email: 'jugador@naraka.com', password: 'naraka123', name: 'Jugador' }
            ];
            
            const user = mockUsers.find(u => u.email === email && u.password === password);
            
            if (user) {
                // Store user info in localStorage
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userEmail', user.email);
                localStorage.setItem('userName', user.name);
                
                successMessage.textContent = '¡Inicio de sesión exitoso! Redirigiendo...';
                successMessage.style.display = 'block';
                
                // Redirect to home page after 1 second
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1000);
            } else {
                errorMessage.textContent = 'Correo electrónico o contraseña incorrectos.';
                errorMessage.style.display = 'block';
            }
        });
        
        // Check if user is already logged in
        if (localStorage.getItem('isLoggedIn') === 'true') {
            window.location.href = 'index.html';
        }
    </script>
</body>
</html>