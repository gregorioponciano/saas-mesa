<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #0a0a0a; color: #e5e5e5; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; padding: 32px; background: #1a1a1a; border-radius: 16px; border: 1px solid #262626; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { color: #fbbf24; font-size: 20px; margin: 0; }
        h2 { color: #fff; font-size: 18px; margin: 0 0 8px; }
        p { color: #a3a3a3; font-size: 14px; line-height: 1.6; margin: 0 0 24px; }
        .btn { display: inline-block; padding: 12px 32px; background: #f59e0b; color: #0a0a0a; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; }
        .btn:hover { background: #fbbf24; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #262626; font-size: 12px; color: #525252; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>{{ $tenantName }}</h1>
        </div>
        <h2>Redefinição de Senha</h2>
        <p>Recebemos uma solicitação de redefinição de senha para o email <strong>{{ $email }}</strong>.</p>
        <p style="text-align: center;">
            <a href="{{ $url }}" class="btn">Redefinir Senha</a>
        </p>
        <p>Se você não solicitou esta alteração, ignore este email. O link expira em 60 minutos.</p>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $tenantName }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
