<?php
/* ===== DATABASE CONFIGURATION ===== */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_projesi_db');

/* ===== SITE CONFIGURATION ===== */
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('SITE_TITLE', 'Kişisel Web Sitem');

/* ===== DATABASE INITIALIZATION ===== */
function initializeDatabase() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    if ($conn->connect_error) {
        die("Veritabanı bağlantı hatası: " . $conn->connect_error);
    }

    // Veritabanı oluştur (yoksa)
    $conn->query("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db(DB_NAME);

    // Tabloları oluştur (yoksa)
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            category ENUM('Kişisel', 'Seyahat', 'Kitap-Film', 'Teknoloji') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            category ENUM('Fotoğraflar', 'Hobiler', 'Videolar') NOT NULL,
            description TEXT
        )",

        "CREATE TABLE IF NOT EXISTS faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL
        )"
    ];

    foreach ($tables as $sql) {
        $conn->query($sql);
    }

    // Admin kullanıcı oluştur (yoksa)
    $adminCheck = $conn->query("SELECT id FROM users WHERE username = '".ADMIN_USER."'");
    if ($adminCheck->num_rows === 0) {
        $hashedPass = password_hash(ADMIN_PASS, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, password) VALUES ('".ADMIN_USER."', '$hashedPass')");
    }

    $conn->close();
}

initializeDatabase();

/* ===== CORE FUNCTIONS ===== */
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $db->set_charset("utf8mb4");
    }
    return $db;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function handleAdminActions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $db = getDB();
        $action = $_POST['action'];

        switch ($action) {
            case 'login':
                $username = isset($_POST['username']) ? $_POST['username'] : '';
                $password = isset($_POST['password']) ? $_POST['password'] : '';

                $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['admin_logged_in'] = true;
                        return true;
                    }
                }
                return false;

            case 'save_page':
                $id = isset($_POST['id']) ? $_POST['id'] : 0;
                $content = isset($_POST['content']) ? $_POST['content'] : '';

                $stmt = $db->prepare("UPDATE pages SET content = ? WHERE id = ?");
                $stmt->bind_param("si", $content, $id);
                return $stmt->execute();

            case 'save_blog':
                // Blog kaydetme işlemleri
                break;

            // Diğer CRUD işlemleri buraya eklenebilir
        }
    }
    return false;
}

/* ===== ROUTING SYSTEM ===== */
session_start();
$request = isset($_GET['page']) ? $_GET['page'] : 'home';
$adminMode = isset($_GET['admin']);

// POST işlemlerini yönet
handleAdminActions();

/* ===== HTML OUTPUT ===== */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_TITLE ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --dark: #5a5c69;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f8f9fc;
        }

        .navbar {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.2);
        }

        .admin-editable {
            border: 1px dashed transparent;
            padding: 5px;
            transition: all 0.3s;
        }

        .admin-editable:hover {
            border-color: var(--primary);
            background: rgba(78, 115, 223, 0.05);
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            height: 200px;
        }

        .gallery-item img {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.5s;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
<?php if ($adminMode): ?>
    <!-- ADMIN PANEL LAYOUT -->
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column p-3 text-white" style="width: 250px;">
            <h4 class="text-center mb-4">Yönetim Paneli</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-2">
                    <a href="?admin&page=dashboard" class="nav-link text-white <?= $request === 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> Gösterge Paneli
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="?admin&page=pages" class="nav-link text-white <?= $request === 'pages' ? 'active' : '' ?>">
                        <i class="fas fa-file-alt me-2"></i> Sayfalar
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="?admin&page=blog" class="nav-link text-white <?= $request === 'blog' ? 'active' : '' ?>">
                        <i class="fas fa-blog me-2"></i> Blog Yönetimi
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="?admin&page=gallery" class="nav-link text-white <?= $request === 'gallery' ? 'active' : '' ?>">
                        <i class="fas fa-images me-2"></i> Galeri Yönetimi
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="?admin&page=faq" class="nav-link text-white <?= $request === 'faq' ? 'active' : '' ?>">
                        <i class="fas fa-question-circle me-2"></i> SSS Yönetimi
                    </a>
                </li>
            </ul>
            <div class="mt-auto">
                <a href="?" class="btn btn-light w-100">
                    <i class="fas fa-sign-out-alt me-2"></i> Siteye Dön
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <?php if (!isAdminLoggedIn()): ?>
                <!-- LOGIN FORM -->
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Yönetici Girişi</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="login">
                                        <div class="mb-3">
                                            <label class="form-label">Kullanıcı Adı</label>
                                            <input type="text" name="username" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Şifre</label>
                                            <input type="password" name="password" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- ADMIN DASHBOARD -->
                <nav class="navbar navbar-light bg-light">
                    <div class="container-fluid">
                        <span class="navbar-brand">Yönetim Paneli</span>
                        <div>
                            <span class="me-3">Hoş geldin, <?= ADMIN_USER ?></span>
                            <a href="?admin&logout" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid py-4">
                    <?php if ($request === 'dashboard'): ?>
                        <!-- Dashboard Content -->
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary mb-1">
                                                    Sayfa Sayısı</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">12</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success mb-1">
                                                    Blog Yazıları</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">24</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-blog fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Diğer istatistik kartları... -->
                        </div>
                    <?php elseif ($request === 'pages'): ?>
                        <!-- Sayfa Yönetimi -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Sayfa İçerik Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Sayfa Adı</th>
                                            <th>URL</th>
                                            <th>İşlemler</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>Ana Sayfa</td>
                                            <td>/</td>
                                            <td>
                                                <a href="?admin&page=edit_page&id=1" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Hakkımda</td>
                                            <td>/hakkimda</td>
                                            <td>
                                                <a href="?admin&page=edit_page&id=2" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                            </td>
                                        </tr>
                                        <!-- Diğer sayfalar... -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($request === 'edit_page'): ?>
                        <!-- Sayfa Düzenleme -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sayfa Düzenleme</h5>
                                <a href="?admin&page=pages" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_page">
                                    <input type="hidden" name="id" value="1">
                                    <div class="mb-3">
                                        <label class="form-label">Sayfa Başlığı</label>
                                        <input type="text" name="title" class="form-control" value="Ana Sayfa">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="content" class="form-control" rows="12" id="editor">
                                                <h3>Hoş Geldiniz!</h3>
                                                <p>Bu benim kişisel web siteme hoş geldiniz...</p>
                                            </textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Değişiklikleri Kaydet
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- PUBLIC WEBSITE LAYOUT -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="?"><?= SITE_TITLE ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $request === 'home' ? 'active' : '' ?>" href="?">Ana Sayfa</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($request, 'about') === 0 ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                            Hakkımda
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=about_bio">Biyografi</a></li>
                            <li><a class="dropdown-item" href="?page=about_interests">İlgi Alanlarım</a></li>
                            <li><a class="dropdown-item" href="?page=about_education">Eğitim & Deneyim</a></li>
                            <li><a class="dropdown-item" href="?page=about_certificates">Sertifikalar</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request === 'blog' ? 'active' : '' ?>" href="?page=blog">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request === 'gallery' ? 'active' : '' ?>" href="?page=gallery">Galeri</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request === 'faq' ? 'active' : '' ?>" href="?page=faq">SSS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request === 'contact' ? 'active' : '' ?>" href="?page=contact">İletişim</a>
                    </li>
                </ul>
                <a href="?admin" class="btn btn-outline-light">
                    <i class="fas fa-lock me-2"></i>Yönetim
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php if ($request === 'home'): ?>
            <!-- HOME PAGE CONTENT -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 mb-4">Hoş Geldiniz!</h1>
                    <p class="lead">Benim kişisel dünyama adım attınız. Burada benimle ilgili her şeyi bulabilirsiniz.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Ben Kimim?</h3>
                            <p class="card-text">Merhaba, ben [Adınız]. [Mesleğiniz] olarak çalışıyorum ve [ilgi alanlarınız] ile ilgileniyorum...</p>
                            <a href="?page=about_bio" class="btn btn-primary">Devamını Oku</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Son Blog Yazılarım</h3>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <a href="#" class="text-decoration-none">Yazılım Geliştirme Süreçleri</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="#" class="text-decoration-none">Yeni Teknoloji Trendleri</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="#" class="text-decoration-none">Web Tasarım İpuçları</a>
                                </li>
                            </ul>
                            <div class="mt-3">
                                <a href="?page=blog" class="btn btn-outline-primary">Tüm Yazılar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($request === 'about_bio'): ?>
            <!-- BIOGRAPHY PAGE -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0">Biyografi</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://via.placeholder.com/200" class="rounded-circle mb-3" alt="Profil Fotoğrafı">
                                <h3>[Adınız Soyadınız]</h3>
                                <p class="text-muted">[Mesleğiniz] | [Şehir]</p>
                            </div>

                            <div class="admin-editable">
                                <p>Buraya biyografi içeriğinizi yazabilirsiniz. Yönetim panelinden bu içeriği düzenleyebilirsiniz.</p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl.</p>
                                <p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($request === 'contact'): ?>
            <!-- CONTACT PAGE -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">İletişim Formu</h3>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Adınız Soyadınız</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">E-posta Adresiniz</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mesajınız</label>
                                    <textarea class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Gönder</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0">İletişim Bilgileri</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-envelope fa-lg text-primary me-3"></i>
                                    <span>email@ornek.com</span>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-phone fa-lg text-primary me-3"></i>
                                    <span>+90 555 123 4567</span>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt fa-lg text-primary me-3"></i>
                                    <span>İstanbul, Türkiye</span>
                                </li>
                            </ul>

                            <div class="mt-4">
                                <h5>Sosyal Medya</h5>
                                <div class="d-flex mt-3">
                                    <a href="#" class="btn btn-outline-dark me-2">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-dark me-2">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-dark me-2">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-dark">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- DEFAULT CONTENT -->
            <div class="text-center py-5">
                <h1 class="display-4">Sayfa Bulunamadı</h1>
                <p class="lead">Aradığınız sayfa mevcut değil.</p>
                <a href="?" class="btn btn-primary">Ana Sayfaya Dön</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><?= SITE_TITLE ?></h5>
                    <p class="text-muted">Kişisel web siteme hoş geldiniz. Benimle ilgili her şeyi burada bulabilirsiniz.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Hızlı Linkler</h5>
                    <ul class="list-unstyled">
                        <li><a href="?page=home" class="text-white text-decoration-none">Ana Sayfa</a></li>
                        <li><a href="?page=about_bio" class="text-white text-decoration-none">Hakkımda</a></li>
                        <li><a href="?page=blog" class="text-white text-decoration-none">Blog</a></li>
                        <li><a href="?page=contact" class="text-white text-decoration-none">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>İletişim</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i> email@ornek.com
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i> +90 555 123 4567
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_TITLE ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Admin düzenleme için basit editör
    document.querySelectorAll('.admin-editable').forEach(editable => {
        editable.addEventListener('dblclick', function() {
            if (confirm('Bu içeriği düzenlemek ister misiniz?')) {
                const content = this.innerHTML;
                this.innerHTML = `<textarea class="form-control mb-2">${content}</textarea>
                                     <button class="btn btn-sm btn-success me-2">Kaydet</button>
                                     <button class="btn btn-sm btn-secondary">İptal</button>`;
            }
        });
    });
</script>
</body>
</html>