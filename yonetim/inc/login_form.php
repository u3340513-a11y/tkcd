<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; background: #fff; }
        .login-logo { max-width: 150px; height: auto; display: block; margin: 0 auto 20px auto; }
        .btn-trabzon { background-color: #610012; color: #fff; border: none; }
        .btn-trabzon:hover { background-color: #004d66; color: #fff; }
    </style>
</head>
<body>
<div class="card login-card p-4">
    <div class="card-body">
        <img src="assets/logo.png" alt="Dernek Logo" class="login-logo">
        <h4 class="text-center mb-4 fw-bold text-secondary">Yönetim Paneli</h4>
        <?php if (!empty($hata_mesaji)): ?>
            <div class="alert alert-danger text-center p-2" role="alert"><?= htmlspecialchars($hata_mesaji); ?></div>
        <?php endif; ?>
        <form action="/yonetim/" method="POST">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-trabzon btn-lg fw-bold">Giriş Yap</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>