<?php
// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturumu en başta başlat
session_start();

/* ===== DATABASE CONFIGURATION ===== */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_projesi_db');

/* ===== SITE CONFIGURATION ===== */
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('SITE_TITLE', 'Kişisel Web Sitem');
date_default_timezone_set('Europe/Istanbul');

/* ===== DATABASE INITIALIZATION ===== */
function initializeDatabase() {
    $db_connected = true;

    try {
        // Veritabanı bağlantısını dene
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            throw new Exception("Veritabanı sunucusuna bağlanılamadı");
        }

        // Veritabanı oluştur (yoksa)
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS ".DB_NAME." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            throw new Exception("Veritabanı oluşturulamadı");
        }

        $conn->select_db(DB_NAME);
        if ($conn->errno) {
            throw new Exception("Veritabanı seçilemedi");
        }

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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
                answer TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS about_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                section_type ENUM('bio', 'interests', 'education', 'certificates') NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL
            )"
        ];

        foreach ($tables as $sql) {
            if (!$conn->query($sql)) {
                throw new Exception("Tablo oluşturulamadı: " . $conn->error);
            }
        }

        // Admin kullanıcı oluştur (yoksa)
        $adminCheck = $conn->query("SELECT id FROM users WHERE username = '".ADMIN_USER."'");
        if ($adminCheck && $adminCheck->num_rows === 0) {
            $hashedPass = password_hash(ADMIN_PASS, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (username, password) VALUES ('".ADMIN_USER."', '$hashedPass')");
        }

        // Temel sayfaları oluştur (yoksa)
        $pages = [
            ['slug' => 'home', 'title' => 'Ana Sayfa', 'content' => '<h1>Hoş Geldiniz!</h1><p>Kişisel web siteme hoş geldiniz.</p>'],
            ['slug' => 'about_bio', 'title' => 'Biyografi', 'content' => '<h2>Benim Hakkımda</h2><p>Buraya biyografi içeriğinizi ekleyin.</p>'],
            ['slug' => 'about_interests', 'title' => 'İlgi Alanlarım', 'content' => '<h2>İlgi Alanlarım</h2><p>Buraya ilgi alanlarınızı ekleyin.</p>'],
            ['slug' => 'about_education', 'title' => 'Eğitim & Deneyim', 'content' => '<h2>Eğitim ve Deneyimlerim</h2><p>Buraya eğitim bilgilerinizi ekleyin.</p>'],
            ['slug' => 'about_certificates', 'title' => 'Sertifikalar', 'content' => '<h2>Sertifikalarım</h2><p>Buraya sertifikalarınızı ekleyin.</p>'],
            ['slug' => 'blog', 'title' => 'Blog', 'content' => '<h2>Blog Yazılarım</h2><p>En son blog yazılarım.</p>'],
            ['slug' => 'gallery', 'title' => 'Galeri', 'content' => '<h2>Galerim</h2><p>Fotoğraf ve videolarım.</p>'],
            ['slug' => 'faq', 'title' => 'Sıkça Sorulan Sorular', 'content' => '<h2>SSS</h2><p>Sıkça sorulan sorular.</p>'],
            ['slug' => 'contact', 'title' => 'İletişim', 'content' => '<h2>İletişim</h2><p>Bana ulaşın.</p>']
        ];

        foreach ($pages as $page) {
            $check = $conn->query("SELECT id FROM pages WHERE slug = '{$page['slug']}'");
            if ($check && $check->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $page['slug'], $page['title'], $page['content']);
                $stmt->execute();
            }
        }

        // Hakkımda bölümlerini oluştur (yoksa)
        $aboutSections = [
            ['section_type' => 'bio', 'title' => 'Biyografi', 'content' => '<p>Buraya biyografi içeriğinizi ekleyin.</p>'],
            ['section_type' => 'interests', 'title' => 'İlgi Alanlarım', 'content' => '<p>Buraya ilgi alanlarınızı ekleyin.</p>'],
            ['section_type' => 'education', 'title' => 'Eğitim & Deneyim', 'content' => '<p>Buraya eğitim bilgilerinizi ekleyin.</p>'],
            ['section_type' => 'certificates', 'title' => 'Sertifikalar', 'content' => '<p>Buraya sertifikalarınızı ekleyin.</p>']
        ];

        foreach ($aboutSections as $section) {
            $check = $conn->query("SELECT id FROM about_sections WHERE section_type = '{$section['section_type']}'");
            if ($check && $check->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO about_sections (section_type, title, content) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $section['section_type'], $section['title'], $section['content']);
                $stmt->execute();
            }
        }

        $conn->close();

    } catch (Exception $e) {
        $db_connected = false;
    }

    return $db_connected;
}

// Veritabanı başlatmayı dene
$db_initialized = @initializeDatabase();

/* ===== CORE FUNCTIONS ===== */
function getDB() {
    static $db = null;

    if ($db === null) {
        try {
            $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($db->connect_error) {
                $db = false;
            } else {
                $db->set_charset("utf8mb4");
            }
        } catch (Exception $e) {
            $db = false;
        }
    }

    return $db;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function handleAdminActions() {
    $db = getDB();

    if (!$db) return false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Null coalesce düzeltmeleri
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $question = isset($_POST['question']) ? $_POST['question'] : '';
        $answer = isset($_POST['answer']) ? $_POST['answer'] : '';

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
                $id = intval($id);
                $content = isset($_POST['content']) ? $_POST['content'] : '';
                $title = isset($_POST['title']) ? $_POST['title'] : '';

                $stmt = $db->prepare("UPDATE pages SET title = ?, content = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $content, $id);
                return $stmt->execute();

            case 'save_blog':
                $id = intval($id);
                $title = isset($_POST['title']) ? $_POST['title'] : '';
                $content = isset($_POST['content']) ? $_POST['content'] : '';
                $category = isset($_POST['category']) ? $_POST['category'] : '';

                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE blog_posts SET title = ?, content = ?, category = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $title, $content, $category, $id);
                } else {
                    $stmt = $db->prepare("INSERT INTO blog_posts (title, content, category) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $title, $content, $category);
                }
                return $stmt->execute();

            case 'delete_blog':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
                $stmt->bind_param("i", $id);
                return $stmt->execute();

            case 'save_gallery':
                $id = intval($id);
                $title = isset($_POST['title']) ? $_POST['title'] : '';
                $category = isset($_POST['category']) ? $_POST['category'] : '';
                $description = isset($_POST['description']) ? $_POST['description'] : '';

                // Basit dosya yükleme
                $image_path = isset($_POST['current_image']) ? $_POST['current_image'] : '';
                if (!empty($_FILES['image']['name'])) {
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) {
                        @mkdir($target_dir, 0777, true);
                    }
                    $target_file = $target_dir . basename($_FILES["image"]["name"]);
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    }
                }

                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE gallery_items SET title = ?, image_path = ?, category = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $title, $image_path, $category, $description, $id);
                } else {
                    $stmt = $db->prepare("INSERT INTO gallery_items (title, image_path, category, description) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $title, $image_path, $category, $description);
                }
                return $stmt->execute();

            case 'delete_gallery':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM gallery_items WHERE id = ?");
                $stmt->bind_param("i", $id);
                return $stmt->execute();

            case 'save_faq':
                $id = intval($id);
                $question = isset($_POST['question']) ? $_POST['question'] : '';
                $answer = isset($_POST['answer']) ? $_POST['answer'] : '';

                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $question, $answer, $id);
                } else {
                    $stmt = $db->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
                    $stmt->bind_param("ss", $question, $answer);
                }
                return $stmt->execute();

            case 'delete_faq':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM faqs WHERE id = ?");
                $stmt->bind_param("i", $id);
                return $stmt->execute();

            case 'save_about':
                $id = intval($id);
                $content = isset($_POST['content']) ? $_POST['content'] : '';
                $title = isset($_POST['title']) ? $_POST['title'] : '';

                $stmt = $db->prepare("UPDATE about_sections SET title = ?, content = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $content, $id);
                return $stmt->execute();
        }
    }
    return false;
}

/* ===== ROUTING SYSTEM ===== */
// Null coalesce düzeltmeleri
$request = isset($_GET['page']) ? $_GET['page'] : 'home';
$adminMode = isset($_GET['admin']);
$action = isset($_GET['action']) ? $_GET['action'] : '';

// POST işlemlerini yönet
$actionResult = handleAdminActions();

// Çıkış işlemi
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header("Location: ?admin");
    exit;
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
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
            margin-bottom: 20px;
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

        .blog-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1);
        }

        .faq-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .faq-question {
            font-weight: 600;
            cursor: pointer;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            opacity: 1 !important;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }

        .empty-state h3 {
            font-weight: 300;
            margin-bottom: 15px;
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
                    <a href="?admin&page=about" class="nav-link text-white <?= $request === 'about' ? 'active' : '' ?>">
                        <i class="fas fa-user me-2"></i> Hakkımda
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
                    <?php if ($actionResult): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            İşlem başarıyla tamamlandı!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$db_initialized): ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Veritabanı Hatası!</h4>
                            <p>Veritabanı bağlantısı kurulamadı veya tablolar oluşturulamadı. Lütfen veritabanı ayarlarını kontrol edin.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($request === 'dashboard'): ?>
                        <!-- Dashboard Content -->
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary mb-1">
                                                    Blog Yazıları</div>
                                                <?php
                                                $blogCount = 0;
                                                if ($db_initialized) {
                                                    $db = getDB();
                                                    if ($db) {
                                                        $blogCount = $db->query("SELECT COUNT(*) as cnt FROM blog_posts")->fetch_assoc()['cnt'];
                                                    }
                                                }
                                                ?>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $blogCount ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-blog fa-2x text-gray-300"></i>
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
                                                    Galeri Öğeleri</div>
                                                <?php
                                                $galleryCount = 0;
                                                if ($db_initialized) {
                                                    $db = getDB();
                                                    if ($db) {
                                                        $galleryCount = $db->query("SELECT COUNT(*) as cnt FROM gallery_items")->fetch_assoc()['cnt'];
                                                    }
                                                }
                                                ?>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $galleryCount ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-images fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info mb-1">
                                                    SSS</div>
                                                <?php
                                                $faqCount = 0;
                                                if ($db_initialized) {
                                                    $db = getDB();
                                                    if ($db) {
                                                        $faqCount = $db->query("SELECT COUNT(*) as cnt FROM faqs")->fetch_assoc()['cnt'];
                                                    }
                                                }
                                                ?>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $faqCount ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($request === 'pages'): ?>
                        <!-- Sayfa Yönetimi -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Sayfa İçerik Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$db_initialized): ?>
                                    <div class="alert alert-warning">
                                        Veritabanı bağlantısı olmadığı için sayfalar görüntülenemiyor.
                                    </div>
                                <?php else: ?>
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
                                            <?php
                                            $db = getDB();
                                            if ($db) {
                                                $pages = $db->query("SELECT * FROM pages");
                                                while ($page = $pages->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($page['title']) ?></td>
                                                        <td>?page=<?= $page['slug'] ?></td>
                                                        <td>
                                                            <a href="?admin&page=edit_page&id=<?= $page['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i> Düzenle
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'edit_page'): ?>
                        <!-- Sayfa Düzenleme -->
                        <?php
                        $id = intval($_GET['id']);
                        $pageContent = ['id' => 0, 'title' => '', 'content' => ''];

                        if ($db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
                                $stmt->bind_param("i", $id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $pageContent = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sayfa Düzenleme: <?= $pageContent['title'] ?></h5>
                                <a href="?admin&page=pages" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_page">
                                    <input type="hidden" name="id" value="<?= $pageContent['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Sayfa Başlığı</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($pageContent['title']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="content" class="form-control" rows="12" required><?= htmlspecialchars($pageContent['content']) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Değişiklikleri Kaydet
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($request === 'about'): ?>
                        <!-- Hakkımda Yönetimi -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Hakkımda Sayfası Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="?admin&page=edit_about&section=bio" class="list-group-item list-group-item-action">
                                        <i class="fas fa-user me-2"></i> Biyografi
                                    </a>
                                    <a href="?admin&page=edit_about&section=interests" class="list-group-item list-group-item-action">
                                        <i class="fas fa-heart me-2"></i> İlgi Alanlarım
                                    </a>
                                    <a href="?admin&page=edit_about&section=education" class="list-group-item list-group-item-action">
                                        <i class="fas fa-graduation-cap me-2"></i> Eğitim & Deneyim
                                    </a>
                                    <a href="?admin&page=edit_about&section=certificates" class="list-group-item list-group-item-action">
                                        <i class="fas fa-award me-2"></i> Başarılar & Sertifikalar
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($request === 'edit_about'): ?>
                        <!-- Hakkımda Düzenleme -->
                        <?php
                        $section = isset($_GET['section']) ? $_GET['section'] : 'bio';
                        $about = ['id' => 0, 'title' => 'Bölüm Başlığı', 'content' => 'Bölüm içeriği'];

                        if ($db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM about_sections WHERE section_type = ?");
                                $stmt->bind_param("s", $section);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $about = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Düzenle: <?= $about['title'] ?></h5>
                                <a href="?admin&page=about" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_about">
                                    <input type="hidden" name="id" value="<?= $about['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Başlık</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($about['title']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="content" class="form-control" rows="12" required><?= htmlspecialchars($about['content']) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Kaydet
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($request === 'blog'): ?>
                        <!-- Blog Yönetimi -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Blog Yazıları Yönetimi</h5>
                                <a href="?admin&page=add_blog" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i> Yeni Yazı
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (!$db_initialized): ?>
                                    <div class="alert alert-warning">
                                        Veritabanı bağlantısı olmadığı için blog yazıları görüntülenemiyor.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                            <tr>
                                                <th>Başlık</th>
                                                <th>Kategori</th>
                                                <th>Tarih</th>
                                                <th>İşlemler</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $db = getDB();
                                            if ($db) {
                                                $posts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
                                                while ($post = $posts->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                                        <td><?= $post['category'] ?></td>
                                                        <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
                                                        <td>
                                                            <a href="?admin&page=edit_blog&id=<?= $post['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i> Düzenle
                                                            </a>
                                                            <form method="POST" style="display:inline-block;">
                                                                <input type="hidden" name="action" value="delete_blog">
                                                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu yazıyı silmek istediğinize emin misiniz?')">
                                                                    <i class="fas fa-trash"></i> Sil
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'add_blog' || $request === 'edit_blog'): ?>
                        <!-- Blog Ekleme/Düzenleme -->
                        <?php
                        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                        $post = ['id' => 0, 'title' => '', 'content' => '', 'category' => 'Kişisel'];

                        if ($id > 0 && $db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
                                $stmt->bind_param("i", $id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $post = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= $id ? 'Blog Yazısını Düzenle' : 'Yeni Blog Yazısı Ekle' ?></h5>
                                <a href="?admin&page=blog" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_blog">
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Başlık</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="category" class="form-select" required>
                                            <option value="Kişisel" <?= $post['category'] === 'Kişisel' ? 'selected' : '' ?>>Kişisel</option>
                                            <option value="Seyahat" <?= $post['category'] === 'Seyahat' ? 'selected' : '' ?>>Seyahat</option>
                                            <option value="Kitap-Film" <?= $post['category'] === 'Kitap-Film' ? 'selected' : '' ?>>Kitap & Film</option>
                                            <option value="Teknoloji" <?= $post['category'] === 'Teknoloji' ? 'selected' : '' ?>>Teknoloji</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="content" class="form-control" rows="12" required><?= htmlspecialchars($post['content']) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> <?= $id ? 'Güncelle' : 'Oluştur' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($request === 'gallery'): ?>
                        <!-- Galeri Yönetimi -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Galeri Yönetimi</h5>
                                <a href="?admin&page=add_gallery" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i> Yeni Öğe
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (!$db_initialized): ?>
                                    <div class="alert alert-warning">
                                        Veritabanı bağlantısı olmadığı için galeri öğeleri görüntülenemiyor.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php
                                        $db = getDB();
                                        if ($db) {
                                            $galleryItems = $db->query("SELECT * FROM gallery_items ORDER BY id DESC");
                                            while ($item = $galleryItems->fetch_assoc()):
                                                ?>
                                                <div class="col-md-4 mb-4">
                                                    <div class="card">
                                                        <img src="<?= htmlspecialchars($item['image_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height: 200px; object-fit: cover;">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
                                                            <p class="card-text text-muted"><?= $item['category'] ?></p>
                                                            <div class="d-flex justify-content-between">
                                                                <a href="?admin&page=edit_gallery&id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-edit"></i> Düzenle
                                                                </a>
                                                                <form method="POST">
                                                                    <input type="hidden" name="action" value="delete_gallery">
                                                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu öğeyi silmek istediğinize emin misiniz?')">
                                                                        <i class="fas fa-trash"></i> Sil
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; } ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'add_gallery' || $request === 'edit_gallery'): ?>
                        <!-- Galeri Ekleme/Düzenleme -->
                        <?php
                        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                        $item = ['id' => 0, 'title' => '', 'image_path' => '', 'category' => 'Fotoğraflar', 'description' => ''];

                        if ($id > 0 && $db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM gallery_items WHERE id = ?");
                                $stmt->bind_param("i", $id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $item = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= $id ? 'Galeri Öğesini Düzenle' : 'Yeni Galeri Öğesi Ekle' ?></h5>
                                <a href="?admin&page=gallery" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save_gallery">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="current_image" value="<?= $item['image_path'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Başlık</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="category" class="form-select" required>
                                            <option value="Fotoğraflar" <?= $item['category'] === 'Fotoğraflar' ? 'selected' : '' ?>>Fotoğraflar</option>
                                            <option value="Hobiler" <?= $item['category'] === 'Hobiler' ? 'selected' : '' ?>>Hobiler</option>
                                            <option value="Videolar" <?= $item['category'] === 'Videolar' ? 'selected' : '' ?>>Videolar</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Açıklama</label>
                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Resim</label>
                                        <?php if ($item['image_path']): ?>
                                            <div class="mb-2">
                                                <img src="<?= $item['image_path'] ?>" class="img-thumbnail" style="max-height: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="image" class="form-control">
                                    </div>

                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> <?= $id ? 'Güncelle' : 'Oluştur' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($request === 'faq'): ?>
                        <!-- SSS Yönetimi -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">SSS (Sıkça Sorulan Sorular) Yönetimi</h5>
                                <a href="?admin&page=add_faq" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i> Yeni Soru
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (!$db_initialized): ?>
                                    <div class="alert alert-warning">
                                        Veritabanı bağlantısı olmadığı için SSS görüntülenemiyor.
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php
                                        $db = getDB();
                                        if ($db) {
                                            $faqs = $db->query("SELECT * FROM faqs ORDER BY created_at DESC");
                                            while ($faq = $faqs->fetch_assoc()):
                                                ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($faq['question']) ?></h6>
                                                            <p class="mb-0 text-muted"><?= mb_substr(strip_tags($faq['answer']), 0, 100) ?>...</p>
                                                        </div>
                                                        <div>
                                                            <a href="?admin&page=edit_faq&id=<?= $faq['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i> Düzenle
                                                            </a>
                                                            <form method="POST" style="display:inline-block;">
                                                                <input type="hidden" name="action" value="delete_faq">
                                                                <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu soruyu silmek istediğinize emin misiniz?')">
                                                                    <i class="fas fa-trash"></i> Sil
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; } ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'add_faq' || $request === 'edit_faq'): ?>
                        <!-- SSS Ekleme/Düzenleme -->
                        <?php
                        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                        $faq = ['id' => 0, 'question' => '', 'answer' => ''];

                        if ($id > 0 && $db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM faqs WHERE id = ?");
                                $stmt->bind_param("i", $id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $faq = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= $id ? 'SSS Düzenle' : 'Yeni SSS Ekle' ?></h5>
                                <a href="?admin&page=faq" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_faq">
                                    <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Soru</label>
                                        <input type="text" name="question" class="form-control" value="<?= htmlspecialchars($faq['question']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Cevap</label>
                                        <textarea name="answer" class="form-control" rows="5" required><?= htmlspecialchars($faq['answer']) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> <?= $id ? 'Güncelle' : 'Oluştur' ?>
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
        <?php if (!$db_initialized): ?>
            <div class="alert alert-danger">
                <h4 class="alert-heading">Veritabanı Bağlantı Hatası!</h4>
                <p>Veritabanı bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin veya site yöneticisi ile iletişime geçin.</p>
            </div>
        <?php endif; ?>

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
                            <?php if ($db_initialized): ?>
                                <?php
                                $db = getDB();
                                $about = $db ? $db->query("SELECT * FROM about_sections WHERE section_type = 'bio'") : false;
                                if ($about && $about->num_rows > 0) {
                                    $bio = $about->fetch_assoc();
                                    echo '<p>' . mb_substr(strip_tags($bio['content']), 0, 200) . '...</p>';
                                } else {
                                    echo '<div class="empty-state">
                                            <i class="fas fa-user-circle"></i>
                                            <h3>Henüz biyografi eklenmemiş</h3>
                                            <p>Yönetici panelinden biyografi bilgilerinizi ekleyebilirsiniz.</p>
                                        </div>';
                                }
                                ?>
                                <a href="?page=about_bio" class="btn btn-primary">Devamını Oku</a>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>Veriye ulaşılamıyor</h3>
                                    <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Son Blog Yazılarım</h3>
                            <?php if ($db_initialized): ?>
                                <ul class="list-group list-group-flush">
                                    <?php
                                    $db = getDB();
                                    if ($db) {
                                        $recentPosts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 3");
                                        if ($recentPosts && $recentPosts->num_rows > 0) {
                                            while ($post = $recentPosts->fetch_assoc()):
                                                ?>
                                                <li class="list-group-item">
                                                    <a href="?page=blog_post&id=<?= $post['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($post['title']) ?>
                                                    </a>
                                                    <div class="text-muted small mt-1">
                                                        <?= date('d.m.Y', strtotime($post['created_at'])) ?> | <?= $post['category'] ?>
                                                    </div>
                                                </li>
                                            <?php endwhile;
                                        } else {
                                            echo '<li class="list-group-item">Henüz blog yazısı eklenmemiş</li>';
                                        }
                                    } else {
                                        echo '<li class="list-group-item">Veritabanı bağlantı hatası</li>';
                                    }
                                    ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="?page=blog" class="btn btn-outline-primary">Tüm Yazılar</a>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>Veriye ulaşılamıyor</h3>
                                    <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                                </div>
                            <?php endif; ?>
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
                            <?php if ($db_initialized): ?>
                                <?php
                                $db = getDB();
                                $about = $db ? $db->query("SELECT * FROM about_sections WHERE section_type = 'bio'") : false;
                                if ($about && $about->num_rows > 0) {
                                    $bio = $about->fetch_assoc();
                                    echo $bio['content'];
                                } else {
                                    echo '<div class="empty-state">
                                            <i class="fas fa-user-circle"></i>
                                            <h3>Henüz biyografi eklenmemiş</h3>
                                            <p>Yönetici panelinden biyografi bilgilerinizi ekleyebilirsiniz.</p>
                                        </div>';
                                }
                                ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>Veriye ulaşılamıyor</h3>
                                    <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($request === 'blog'): ?>
            <!-- BLOG PAGE -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0">Blog Yazılarım</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($db_initialized): ?>
                                <div class="row">
                                    <?php
                                    $db = getDB();
                                    if ($db) {
                                        $posts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
                                        if ($posts && $posts->num_rows > 0) {
                                            while ($post = $posts->fetch_assoc()):
                                                ?>
                                                <div class="col-md-6 mb-4">
                                                    <div class="card blog-card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                                            <p class="card-text text-muted">
                                                                <small>
                                                                    <?= date('d.m.Y', strtotime($post['created_at'])) ?> |
                                                                    <span class="badge bg-primary"><?= $post['category'] ?></span>
                                                                </small>
                                                            </p>
                                                            <p class="card-text"><?= mb_substr(strip_tags($post['content']), 0, 150) ?>...</p>
                                                            <a href="?page=blog_post&id=<?= $post['id'] ?>" class="btn btn-outline-primary">Devamını Oku</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile;
                                        } else {
                                            echo '<div class="col-12">
                                                    <div class="empty-state">
                                                        <i class="fas fa-newspaper"></i>
                                                        <h3>Henüz blog yazısı eklenmemiş</h3>
                                                        <p>Yönetici panelinden blog yazıları ekleyebilirsiniz.</p>
                                                    </div>
                                                </div>';
                                        }
                                    } else {
                                        echo '<div class="col-12">
                                                <div class="alert alert-danger">Veritabanı bağlantı hatası</div>
                                            </div>';
                                    }
                                    ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>Veriye ulaşılamıyor</h3>
                                    <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diğer sayfalar aynı mantıkla düzenlendi -->

        <?php else: ?>
            <!-- DYNAMIC PAGE CONTENT -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0"><?= isset($pageContent['title']) ? htmlspecialchars($pageContent['title']) : 'Sayfa Başlığı' ?></h2>
                        </div>
                        <div class="card-body">
                            <?php if ($db_initialized): ?>
                                <?php
                                $db = getDB();
                                if ($db) {
                                    $currentPage = $db->prepare("SELECT * FROM pages WHERE slug = ?");
                                    $currentPage->bind_param("s", $request);
                                    $currentPage->execute();
                                    $pageContent = $currentPage->get_result()->fetch_assoc();

                                    if ($pageContent) {
                                        echo $pageContent['content'];
                                    } else {
                                        echo '<div class="empty-state">
                                                <i class="fas fa-file-alt"></i>
                                                <h3>Sayfa içeriği henüz eklenmemiş</h3>
                                                <p>Yönetici panelinden bu sayfanın içeriğini düzenleyebilirsiniz.</p>
                                            </div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger">Veritabanı bağlantı hatası</div>';
                                }
                                ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>Veriye ulaşılamıyor</h3>
                                    <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script>
    // Lightbox ayarları
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'showImageNumberLabel': true
    });
</script>
</body>
</html>