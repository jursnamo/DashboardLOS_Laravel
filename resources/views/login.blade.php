<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard LOS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-wrapper {
            display: flex;
            gap: 20px;
            width: 100%;
            max-width: 1000px;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            flex: 1;
            min-width: 350px;
        }

        .demo-users {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            flex: 1;
            min-width: 350px;
        }

        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .demo-users h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .error {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }

        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .users-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .user-item {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }

        .user-item strong {
            color: #333;
            display: block;
        }

        .user-item .email {
            color: #666;
            font-size: 13px;
            margin: 4px 0;
        }

        .user-item .role {
            color: #667eea;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }

        .default-password {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-top: 15px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-container,
            .demo-users {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Login Form -->
        <div class="login-container">
            <h1>Login</h1>

            @if ($errors->any())
                <div class="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember" style="margin: 0;">Remember me</label>
                </div>

                <button type="submit">Login</button>
            </form>

            <div class="register-link">
                Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>
            </div>
        </div>

        <!-- Demo Users -->
        <div class="demo-users">
            <h2>📋 Demo Users</h2>
            <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                Test accounts dengan berbagai roles untuk development:
            </p>

            <div class="users-list">
                <div class="user-item">
                    <strong>Test RM</strong>
                    <div class="email">test1@example.com</div>
                    <div class="role">RM</div>
                </div>

                <div class="user-item">
                    <strong>Test BM</strong>
                    <div class="email">test2@example.com</div>
                    <div class="role">BM</div>
                </div>

                <div class="user-item">
                    <strong>Test BSM</strong>
                    <div class="email">test3@example.com</div>
                    <div class="role">BSM</div>
                </div>

                <div class="user-item">
                    <strong>Test AM</strong>
                    <div class="email">test4@example.com</div>
                    <div class="role">AM</div>
                </div>

                <div class="user-item">
                    <strong>Test BAM</strong>
                    <div class="email">test5@example.com</div>
                    <div class="role">BAM</div>
                </div>

                <div class="user-item">
                    <strong>Test CIV_Maker</strong>
                    <div class="email">test6@example.com</div>
                    <div class="role">CIV Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test CIV_Checker</strong>
                    <div class="email">test7@example.com</div>
                    <div class="role">CIV Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test CS_Maker</strong>
                    <div class="email">test8@example.com</div>
                    <div class="role">CS Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test CS_Checker</strong>
                    <div class="email">test9@example.com</div>
                    <div class="role">CS Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test DV_Maker</strong>
                    <div class="email">test10@example.com</div>
                    <div class="role">DV Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test DV_Checker</strong>
                    <div class="email">test11@example.com</div>
                    <div class="role">DV Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test Legal_Maker</strong>
                    <div class="email">test12@example.com</div>
                    <div class="role">Legal Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test Legal_Checker</strong>
                    <div class="email">test13@example.com</div>
                    <div class="role">Legal Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test OCR_Maker</strong>
                    <div class="email">test14@example.com</div>
                    <div class="role">OCR Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test OCR_Checker</strong>
                    <div class="email">test15@example.com</div>
                    <div class="role">OCR Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test Underwriter_Maker</strong>
                    <div class="email">test16@example.com</div>
                    <div class="role">Underwriter Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test Underwriter_Checker</strong>
                    <div class="email">test17@example.com</div>
                    <div class="role">Underwriter Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test PLI</strong>
                    <div class="email">test18@example.com</div>
                    <div class="role">PLI</div>
                </div>

                <div class="user-item">
                    <strong>Test PO_Trade_Maker</strong>
                    <div class="email">test19@example.com</div>
                    <div class="role">PO Trade Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test PO_Trade_Checker</strong>
                    <div class="email">test20@example.com</div>
                    <div class="role">PO Trade Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test PO_Value_Chain_Maker</strong>
                    <div class="email">test21@example.com</div>
                    <div class="role">PO Value Chain Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test PO_Value_Chain_Checker</strong>
                    <div class="email">test22@example.com</div>
                    <div class="role">PO Value Chain Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test Treasury_Maker</strong>
                    <div class="email">test23@example.com</div>
                    <div class="role">Treasury Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test Treasury_Checker</strong>
                    <div class="email">test24@example.com</div>
                    <div class="role">Treasury Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test Unit_Syariah_Primover_Maker</strong>
                    <div class="email">test25@example.com</div>
                    <div class="role">Unit Syariah Primover Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test Unit_Syariah_Primover_Checker</strong>
                    <div class="email">test26@example.com</div>
                    <div class="role">Unit Syariah Primover Checker</div>
                </div>

                <div class="user-item">
                    <strong>Test Valuer_Internal</strong>
                    <div class="email">test27@example.com</div>
                    <div class="role">Valuer Internal</div>
                </div>

                <div class="user-item">
                    <strong>Test Valuer_External</strong>
                    <div class="email">test28@example.com</div>
                    <div class="role">Valuer External</div>
                </div>

                <div class="user-item">
                    <strong>Test Credam_Maker</strong>
                    <div class="email">test29@example.com</div>
                    <div class="role">Credam Maker</div>
                </div>

                <div class="user-item">
                    <strong>Test Credam_Checker</strong>
                    <div class="email">test30@example.com</div>
                    <div class="role">Credam Checker</div>
                </div>
            </div>

            <div class="default-password">
                🔐 Default Password: <strong>password</strong>
            </div>
        </div>
    </div>
</body>
</html>
