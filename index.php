<?php
// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturumu en başta başlat
session_start();

/* ===== DATABASE CONFIGURATION ===== */

//define('DB_HOST', '217.195.207.215');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'murat');
define('DB_PASS', '81644936.Ma');
define('DB_NAME', 'dunyani1_mcb');

//define('DB_HOST', 'localhost');
//define('DB_USER', 'root');
//define('DB_PASS', '');
//define('DB_NAME', 'web_projesi_db');

/* ===== SITE CONFIGURATION ===== */
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('SITE_TITLE', 'Murat Candaş Bozyiğit');
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
                file_path VARCHAR(255) NOT NULL,
                file_type ENUM('image', 'video', 'pdf') NOT NULL DEFAULT 'image',
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
            )",

            "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS site_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(50) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL
            )"
        ];

        foreach ($tables as $sql) {
            if (!$conn->query($sql)) {
                throw new Exception("Tablo oluşturulamadı: " . $conn->error);
            }
        }

        // Eski image_path sütununu file_path olarak değiştir (eski versiyonlar için)
        $result = $conn->query("SHOW COLUMNS FROM gallery_items LIKE 'image_path'");
        if ($result && $result->num_rows > 0) {
            $conn->query("ALTER TABLE gallery_items CHANGE image_path file_path VARCHAR(255) NOT NULL");
        }

        // Eğer file_type sütunu yoksa ekle (eski versiyonlar için)
        $result = $conn->query("SHOW COLUMNS FROM gallery_items LIKE 'file_type'");
        if (!$result || $result->num_rows === 0) {
            $conn->query("ALTER TABLE gallery_items ADD file_type ENUM('image', 'video', 'pdf') NOT NULL DEFAULT 'image' AFTER file_path");
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

        // Site ayarlarını oluştur (yoksa)
        $settings = [
            ['site_title', 'Murat Candaş Bozyiğit'],
            ['address', 'İstanbul, Türkiye'],
            ['email', 'info@muratcandas.com'],
            ['phone', '+90 555 123 4567'],
            ['facebook', 'https://facebook.com/muratcandas'],
            ['twitter', 'https://twitter.com/muratcandas'],
            ['instagram', 'https://instagram.com/muratcandas'],
            ['linkedin', 'https://linkedin.com/in/muratcandas'],
            ['github', 'https://github.com/muratcandas'],
            ['theme', 'dark'] // Varsayılan tema
        ];

        foreach ($settings as $setting) {
            $check = $conn->query("SELECT id FROM site_settings WHERE setting_key = '{$setting[0]}'");
            if ($check && $check->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->bind_param("ss", $setting[0], $setting[1]);
                $stmt->execute();
            }
        }

        $conn->close();

    } catch (Exception $e) {
        $db_connected = false;
        // Hata mesajını görmek için (geliştirme aşamasında)
        // echo "Hata: " . $e->getMessage();
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

function getSetting($key, $default = '') {
    $db = getDB();
    if (!$db) return $default;

    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }

    return $default;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function handleAdminActions() {
    $db = getDB();

    if (!$db) return false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'] ?? '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $question = $_POST['question'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $setting_key = $_POST['setting_key'] ?? '';
        $setting_value = $_POST['setting_value'] ?? '';

        // SweetAlert için session mesajları
        if (!isset($_SESSION['swal_messages'])) {
            $_SESSION['swal_messages'] = [];
        }

        switch ($action) {
            case 'login':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';

                $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['swal_messages'][] = [
                            'type' => 'success',
                            'title' => 'Başarılı',
                            'text' => 'Başarıyla giriş yaptınız'
                        ];
                        return true;
                    }
                }
                $_SESSION['swal_messages'][] = [
                    'type' => 'error',
                    'title' => 'Hata',
                    'text' => 'Geçersiz kullanıcı adı veya şifre'
                ];
                return false;

            case 'save_page':
                $id = intval($id);
                $content = $_POST['content'] ?? '';
                $title = $_POST['title'] ?? '';

                $stmt = $db->prepare("UPDATE pages SET title = ?, content = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $content, $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Sayfa başarıyla güncellendi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Sayfa güncellenirken hata oluştu'
                    ];
                }
                return $result;

            case 'save_blog':
                $id = intval($id);
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                $category = $_POST['category'] ?? '';

                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE blog_posts SET title = ?, content = ?, category = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $title, $content, $category, $id);
                } else {
                    $stmt = $db->prepare("INSERT INTO blog_posts (title, content, category) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $title, $content, $category);
                }
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => $id > 0 ? 'Blog yazısı güncellendi' : 'Yeni blog yazısı eklendi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Blog yazısı kaydedilirken hata oluştu'
                    ];
                }
                return $result;

            case 'delete_blog':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Blog yazısı başarıyla silindi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Blog yazısı silinirken hata oluştu'
                    ];
                }
                return $result;

            case 'save_gallery':
                header('Content-Type: application/json');
                $response = ['success' => false, 'message' => ''];

                try {
                    // ID ve form verilerini alma
                    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                    $title = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
                    $category = isset($_POST['category']) ? htmlspecialchars($_POST['category']) : '';
                    $description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '';
                    $current_file = isset($_POST['current_file']) ? htmlspecialchars($_POST['current_file']) : '';

                    // Zorunlu alan kontrolü
                    if (empty($title)) {
                        throw new Exception("Başlık alanı zorunludur!");
                    }

                    // Dosya yükleme işlemleri
                    $file_path = $current_file;
                    $file_type = 'image';
                    $newFileUploaded = false;

                    if (!empty($_FILES['file']['name'])) {
                        $target_dir = "uploads/";

                        // Klasör kontrolü ve oluşturma
                        if (!is_dir($target_dir)) {
                            if (!mkdir($target_dir, 0777, true)) {
                                throw new Exception("Klasör oluşturulamadı: $target_dir. Yazma izinlerini kontrol edin.");
                            }
                        }

                        // Dosya bilgileri
                        $original_name = basename($_FILES['file']['name']);
                        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                        // Tüm izin verilen dosya türleri (görsel, video, pdf)
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'avi', 'pdf'];

                        // Dosya türünü belirle
                        if (in_array($file_extension, ['mp4', 'mov', 'avi'])) {
                            $file_type = 'video';
                        } elseif ($file_extension === 'pdf') {
                            $file_type = 'pdf';
                        } else {
                            $file_type = 'image';
                        }

                        // Güvenlik kontrolleri
                        if (!in_array($file_extension, $allowed_types)) {
                            throw new Exception("Geçersiz dosya formatı! Desteklenen formatlar: JPG, JPEG, PNG, GIF, WEBP, MP4, MOV, AVI, PDF");
                        }

                        // Dosya boyutu kontrolü (max 200MB)
                        $maxFileSize = 200 * 1024 * 1024; // 200MB
                        if ($_FILES['file']['size'] > $maxFileSize) {
                            throw new Exception("Dosya boyutu çok büyük! Maksimum 200MB yükleyebilirsiniz.");
                        }

                        // MIME tipi kontrolü (sadece geçerli dosyalar için)
                        if (!empty($_FILES['file']['tmp_name']) && file_exists($_FILES['file']['tmp_name'])) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
                            finfo_close($finfo);

                            $allowed_mimes = [
                                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                                'video/mp4', 'video/quicktime', 'video/x-msvideo',
                                'application/pdf'
                            ];
                            if (!in_array($mime_type, $allowed_mimes)) {
                                throw new Exception("Geçersiz dosya türü! Sistem MIME tipi: $mime_type");
                            }
                        }

                        // Benzersiz dosya adı oluşturma
                        $unique_name = uniqid('file_', true) . '.' . $file_extension;
                        $target_path = $target_dir . $unique_name;

                        // Dosya taşıma
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                            $file_path = $target_path;
                            $newFileUploaded = true;
                        } else {
                            // Hata koduna göre özel mesaj
                            $error_code = $_FILES['file']['error'];
                            $error_messages = [
                                0 => 'Başarılı',
                                1 => 'Dosya boyutu php.ini limitini aşıyor',
                                2 => 'Dosya boyutu form limitini aşıyor',
                                3 => 'Dosya kısmen yüklendi',
                                4 => 'Dosya yüklenmedi',
                                6 => 'Geçici klasör bulunamadı',
                                7 => 'Dosya diske yazılamadı',
                                8 => 'Bir PHP eklentisi dosya yüklemeyi durdurdu'
                            ];

                            $error_msg = $error_messages[$error_code] ?? "Bilinmeyen hata kodu: $error_code";
                            throw new Exception("Dosya yükleme hatası: $error_msg");
                        }
                    }

                    // Veritabanı işlemleri
                    if ($id > 0) {
                        // GÜNCELLEME
                        $stmt = $db->prepare("UPDATE gallery_items SET 
                        title = ?,
                        file_path = ?,
                        file_type = ?,
                        category = ?,
                        description = ?
                        WHERE id = ?");
                        $stmt->bind_param("sssssi", $title, $file_path, $file_type, $category, $description, $id);
                    } else {
                        // EKLEME
                        $stmt = $db->prepare("INSERT INTO gallery_items 
                        (title, file_path, file_type, category, description) 
                        VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $title, $file_path, $file_type, $category, $description);
                    }

                    // Sorguyu çalıştır
                    if ($stmt->execute()) {
                        // Eski dosyayı silme (güncelleme ve yeni dosya yüklendiyse)
                        if ($id > 0 && $newFileUploaded && !empty($current_file) && file_exists($current_file)) {
                            @unlink($current_file);
                        }

                        $response['success'] = true;
                        $response['message'] = $id > 0
                            ? "Kayıt başarıyla güncellendi!"
                            : "Yeni kayıt eklendi!";
                    } else {
                        throw new Exception("Database hatası: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    // Hata durumunda yeni yüklenen dosyayı sil
                    if (isset($target_path) && file_exists($target_path)) {
                        @unlink($target_path);
                    }
                    $response['message'] = "Hata: " . $e->getMessage();
                }

                // JSON çıktısı yerine session'a mesaj ekle
                $_SESSION['swal_messages'][] = [
                    'type' => $response['success'] ? 'success' : 'error',
                    'title' => $response['success'] ? 'Başarılı' : 'Hata',
                    'text' => $response['message']
                ];

                // Yönlendirme yap
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;

            case 'delete_gallery':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM gallery_items WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Galeri öğesi başarıyla silindi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Galeri öğesi silinirken hata oluştu'
                    ];
                }
                return $result;

            case 'save_faq':
                $id = intval($id);
                $question = $_POST['question'] ?? '';
                $answer = $_POST['answer'] ?? '';

                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $question, $answer, $id);
                } else {
                    $stmt = $db->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
                    $stmt->bind_param("ss", $question, $answer);
                }
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => $id > 0 ? 'SSS güncellendi' : 'Yeni SSS eklendi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'SSS kaydedilirken hata oluştu'
                    ];
                }
                return $result;

            case 'delete_faq':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM faqs WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'SSS başarıyla silindi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'SSS silinirken hata oluştu'
                    ];
                }
                return $result;

            case 'save_about':
                $id = intval($id);
                $content = $_POST['content'] ?? '';
                $title = $_POST['title'] ?? '';

                $stmt = $db->prepare("UPDATE about_sections SET title = ?, content = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $content, $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Hakkımda bölümü güncellendi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Hakkımda bölümü güncellenirken hata oluştu'
                    ];
                }
                return $result;

            case 'save_settings':
                $settings = $_POST['settings'] ?? [];

                if (!empty($settings) && is_array($settings)) {
                    $success = true;

                    foreach ($settings as $key => $value) {
                        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                          VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->bind_param("sss", $key, $value, $value);
                        if (!$stmt->execute()) {
                            $success = false;
                        }
                    }

                    if ($success) {
                        $_SESSION['swal_messages'][] = [
                            'type' => 'success',
                            'title' => 'Başarılı',
                            'text' => 'Site ayarları başarıyla güncellendi'
                        ];
                    } else {
                        $_SESSION['swal_messages'][] = [
                            'type' => 'error',
                            'title' => 'Hata',
                            'text' => 'Bazı ayarlar güncellenemedi'
                        ];
                    }
                    return $success;
                }
                return false;

            case 'save_contact':
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $subject = $_POST['subject'] ?? '';
                $message = $_POST['message'] ?? '';

                $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $subject, $message);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Mesajınız başarıyla gönderildi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Mesaj gönderilirken hata oluştu'
                    ];
                }
                return $result;

            case 'delete_contact':
                $id = intval($id);
                $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();

                if ($result) {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'success',
                        'title' => 'Başarılı',
                        'text' => 'Mesaj başarıyla silindi'
                    ];
                } else {
                    $_SESSION['swal_messages'][] = [
                        'type' => 'error',
                        'title' => 'Hata',
                        'text' => 'Mesaj silinirken hata oluştu'
                    ];
                }
                return $result;
        }
    }
    return false;
}

/* ===== ROUTING SYSTEM ===== */
$request = $_GET['page'] ?? 'home';
$adminMode = isset($_GET['admin']);
$action = $_GET['action'] ?? '';

// POST işlemlerini yönet
$actionResult = handleAdminActions();

// Çıkış işlemi
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header("Location: ?admin");
    exit;
}

// Tema değiştirme
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    if (in_array($theme, ['dark', 'light'])) {
        // Tema tercihini veritabanına kaydet
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                  VALUES ('theme', ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $theme, $theme);
            $stmt->execute();
        }

        // Geçerli sayfaya yönlendir (theme parametresini kaldırarak)
        $url = str_replace(['&theme=dark', '&theme=light'], '', $_SERVER['REQUEST_URI']);
        header("Location: $url");
        exit;
    }
}

// Geçerli tema
$currentTheme = getSetting('theme', 'dark');

/* ===== HTML OUTPUT ===== */
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= $currentTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSetting('site_title', SITE_TITLE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            /* Dark Theme Variables */
            --primary-dark: #4e73df;
            --secondary-dark: #6c757d;
            --success-dark: #1cc88a;
            --info-dark: #36b9cc;
            --warning-dark: #f6c23e;
            --danger-dark: #e74a3b;
            --dark-dark: #121212;
            --darker-dark: #0a0a0a;
            --dark-light-dark: #1e1e1e;
            --text-dark: #ffffff;
            --text-muted-dark: #adb5bd;

            /* Light Theme Variables */
            --primary-light: #4e73df;
            --secondary-light: #6c757d;
            --success-light: #1cc88a;
            --info-light: #36b9cc;
            --warning-light: #f6c23e;
            --danger-light: #e74a3b;
            --dark-light: #f8f9fa;
            --darker-light: #e9ecef;
            --dark-light-light: #ffffff;
            --text-light: #212529; /* Siyah metin */
            --text-muted-light: #6c757d; /* Gri metin */
        }

        /* Dark Theme */
        [data-theme="dark"] {
            --primary: var(--primary-dark);
            --secondary: var(--secondary-dark);
            --success: var(--success-dark);
            --info: var(--info-dark);
            --warning: var(--warning-dark);
            --danger: var(--danger-dark);
            --dark: var(--dark-dark);
            --darker: var(--darker-dark);
            --dark-light: var(--dark-light-dark);
            --text: var(--text-dark);
            --text-muted: var(--text-muted-dark);
        }

        /* Light Theme */
        [data-theme="light"] {
            --primary: var(--primary-light);
            --secondary: var(--secondary-light);
            --success: var(--success-light);
            --info: var(--info-light);
            --warning: var(--warning-light);
            --danger: var(--danger-light);
            --dark: var(--dark-light);
            --darker: var(--darker-light);
            --dark-light: var(--dark-light-light);
            --text: var(--text-light); /* Siyah metin */
            --text-muted: var(--text-muted-light); /* Gri metin */

            /* Özel ayarlar */
            .navbar, .card, .card-header, .card-body, .list-group-item,
            .accordion-button, .table, .alert, .form-label, .text-muted,
            .footer-links a, .contact-form .form-control, .btn-outline-light,
            .social-link, .stat-card, .admin-editable, .gallery-item, .blog-card,
            .empty-state, .section-header, .feature-icon, .btn-gradient, .admin-badge {
                color: var(--text) !important;
            }

            .card {
                border: 1px solid rgba(0,0,0,0.1) !important;
            }

            .table {
                color: var(--text) !important;
            }

            .contact-form .form-control {
                background: #fff !important;
                color: #000 !important;
                border: 1px solid #dee2e6 !important;
            }

            .empty-state {
                background-color: #f8f9fa !important;
            }
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--darker);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar, .card, .card-header, .card-body, .list-group-item,
        .accordion-button, .table, .alert, .form-label, .text-muted,
        .footer-links a, .contact-form .form-control, .btn-outline-light,
        .social-link, .stat-card, .admin-editable, .gallery-item, .blog-card,
        .empty-state, .section-header, .feature-icon, .btn-gradient, .admin-badge {
            color: var(--text) !important;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .navbar {
            background: var(--dark);
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.15rem 1.5rem 0 rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: var(--dark-light);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.4);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary), #4a6fc9);
            color: white;
            border-bottom: none;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--dark);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.3);
        }

        .admin-editable {
            border: 1px dashed transparent;
            padding: 5px;
            transition: all 0.3s;
        }

        .admin-editable:hover {
            border-color: var(--primary);
            background: rgba(78, 115, 223, 0.1);
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            height: 200px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .gallery-item img, .gallery-item video {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.5s;
        }

        .gallery-item:hover img, .gallery-item:hover video {
            transform: scale(1.1);
        }

        .blog-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.4);
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
            color: var(--text-muted);
            background-color: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.1);
        }

        .empty-state h3 {
            font-weight: 300;
            margin-bottom: 15px;
        }

        .section-header {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .section-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--dark-light);
            color: var(--text);
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-link:hover {
            transform: translateY(-3px);
            background: var(--primary);
            text-decoration: none;
        }

        .btn-gradient {
            background: linear-gradient(to right, var(--primary), #4a6fc9);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            transition: all 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.4);
            color: white;
        }

        .contact-form .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: var(--text);
            transition: all 0.3s;
        }

        .contact-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
            background: rgba(255,255,255,0.08);
        }

        footer {
            background: var(--dark);
            color: var(--text);
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            margin-bottom: 8px;
        }

        .footer-links a:hover {
            color: var(--text);
            padding-left: 5px;
        }

        .admin-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 10;
        }

        .admin-panel {
            background: var(--darker);
            min-height: 100vh;
        }

        .stat-card {
            border-left: 4px solid var(--primary);
            background: var(--dark-light);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.info { border-left-color: var(--info); }
        .stat-card.warning { border-left-color: var(--warning); }

        .list-group-item {
            background-color: var(--dark-light);
            color: var(--text);
            border-color: rgba(255,255,255,0.1);
        }

        .accordion-button {
            background-color: var(--dark-light);
            color: var(--text);
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--dark);
            color: var(--text);
        }

        .accordion-body {
            background-color: var(--darker);
            color: var(--text-muted);
        }

        .table {
            color: var(--text);
        }

        .table-bordered {
            border-color: rgba(255,255,255,0.1);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.05);
        }

        .alert {
            background-color: var(--dark-light);
            color: var(--text);
            border: none;
        }

        .form-label {
            color: var(--text);
        }

        .hero-bg {
            background: linear-gradient(135deg, var(--dark) 0%, var(--darker) 100%);
            padding: 80px 0;
            border-radius: 0 0 30px 30px;
        }

        .feature-card {
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s;
            border: 1px solid rgba(78, 115, 223, 0.2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            border-color: var(--primary);
        }

        .profile-img {
            border: 5px solid rgba(255,255,255,0.1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.4s;
        }

        .profile-img:hover {
            transform: scale(1.03);
            border-color: var(--primary);
        }

        .section-title {
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--primary), transparent);
        }

        .social-icon {
            font-size: 24px;
            margin: 0 10px;
            transition: all 0.3s;
        }

        .social-icon:hover {
            transform: translateY(-5px);
            color: var(--primary);
        }

        .admin-tool {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
        }

        .file-icon {
            font-size: 4rem;
            text-align: center;
            color: var(--primary);
        }

        .pdf-preview {
            width: 100%;
            height: 300px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: rgba(255,255,255,0.05);
        }

        .theme-switcher {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }

        .theme-switcher:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body class="<?= $adminMode ? 'admin-panel' : '' ?>">
<?php
// SweetAlert mesajlarını göster
if (!empty($_SESSION['swal_messages'])) {
    foreach ($_SESSION['swal_messages'] as $message) {
        echo "<script>
            Swal.fire({
                icon: '{$message['type']}',
                title: '{$message['title']}',
                text: '{$message['text']}',
                confirmButtonColor: '#4e73df'
            });
        </script>";
    }
    $_SESSION['swal_messages'] = [];
}
?>

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
                <li class="nav-item mb-2">
                    <a href="?admin&page=contacts" class="nav-link text-white <?= $request === 'contacts' ? 'active' : '' ?>">
                        <i class="fas fa-envelope me-2"></i> İletişim Mesajları
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="?admin&page=settings" class="nav-link text-white <?= $request === 'settings' ? 'active' : '' ?>">
                        <i class="fas fa-cog me-2"></i> Site Ayarları
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
                <nav class="navbar navbar-light" style="background: var(--dark-light);">
                    <div class="container-fluid">
                        <span class="navbar-brand text-primary fw-bold">Yönetim Paneli</span>
                        <div>
                            <span class="me-3 text-white">Hoş geldin, <?= ADMIN_USER ?></span>
                            <a href="?admin&logout" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Çıkış Yap
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid py-4">
                    <?php if (!$db_initialized): ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Veritabanı Hatası!</h4>
                            <p>Veritabanı bağlantısı kurulamadı veya tablolar oluşturulamadı. Lütfen veritabanı ayarlarını kontrol edin.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($request === 'dashboard'): ?>
                        <!-- Dashboard Content -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Sistem İstatistikleri</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xl-3 col-md-6 mb-4">
                                                <div class="card stat-card h-100">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
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
                                                                <div class="h5 mb-0 font-weight-bold text-gray-300"><?= $blogCount ?></div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="fas fa-blog fa-2x text-gray-500"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-6 mb-4">
                                                <div class="card stat-card success h-100">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
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
                                                                <div class="h5 mb-0 font-weight-bold text-gray-300"><?= $galleryCount ?></div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="fas fa-images fa-2x text-gray-500"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-6 mb-4">
                                                <div class="card stat-card info h-100">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
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
                                                                <div class="h5 mb-0 font-weight-bold text-gray-300"><?= $faqCount ?></div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="fas fa-question-circle fa-2x text-gray-500"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-md-6 mb-4">
                                                <div class="card stat-card warning h-100">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                    İletişim Mesajları</div>
                                                                <?php
                                                                $contactCount = 0;
                                                                if ($db_initialized) {
                                                                    $db = getDB();
                                                                    if ($db) {
                                                                        $contactCount = $db->query("SELECT COUNT(*) as cnt FROM contacts")->fetch_assoc()['cnt'];
                                                                    }
                                                                }
                                                                ?>
                                                                <div class="h5 mb-0 font-weight-bold text-gray-300"><?= $contactCount ?></div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="fas fa-envelope fa-2x text-gray-500"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Son Blog Yazıları</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($db_initialized): ?>
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                $db = getDB();
                                                if ($db) {
                                                    $recentPosts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5");
                                                    if ($recentPosts && $recentPosts->num_rows > 0) {
                                                        while ($post = $recentPosts->fetch_assoc()):
                                                            ?>
                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 class="mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                                                                    <small class="text-muted"><?= date('d.m.Y', strtotime($post['created_at'])) ?></small>
                                                                </div>
                                                                <a href="?admin&page=edit_blog&id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            </li>
                                                        <?php endwhile;
                                                    } else {
                                                        echo '<li class="list-group-item text-center py-4 text-muted">Henüz blog yazısı eklenmemiş</li>';
                                                    }
                                                } else {
                                                    echo '<li class="list-group-item">Veritabanı bağlantı hatası</li>';
                                                }
                                                ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Son İletişim Mesajları</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($db_initialized): ?>
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                $db = getDB();
                                                if ($db) {
                                                    $recentContacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
                                                    if ($recentContacts && $recentContacts->num_rows > 0) {
                                                        while ($contact = $recentContacts->fetch_assoc()):
                                                            ?>
                                                            <li class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <h6 class="mb-1"><?= htmlspecialchars($contact['subject']) ?></h6>
                                                                    <small class="text-muted"><?= date('d.m.Y', strtotime($contact['created_at'])) ?></small>
                                                                </div>
                                                                <small class="text-muted"><?= htmlspecialchars($contact['name']) ?> &lt;<?= htmlspecialchars($contact['email']) ?>&gt;</small>
                                                                <p class="mb-0 mt-1"><?= mb_substr(strip_tags($contact['message']), 0, 60) ?>...</p>
                                                            </li>
                                                        <?php endwhile;
                                                    } else {
                                                        echo '<li class="list-group-item text-center py-4 text-muted">Henüz mesaj bulunmamaktadır</li>';
                                                    }
                                                } else {
                                                    echo '<li class="list-group-item">Veritabanı bağlantı hatası</li>';
                                                }
                                                ?>
                                            </ul>
                                        <?php endif; ?>
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
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-dark">
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
                                                                <i class="fas fa-edit me-1"></i> Düzenle
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
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
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
                                    <a href="?admin&page=edit_about&section=bio" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="fas fa-user me-3 fa-lg text-primary"></i>
                                        <div>
                                            <h6 class="mb-0">Biyografi</h6>
                                            <small class="text-muted">Kişisel hikayenizi ve geçmişinizi anlatın</small>
                                        </div>
                                    </a>
                                    <a href="?admin&page=edit_about&section=interests" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="fas fa-heart me-3 fa-lg text-danger"></i>
                                        <div>
                                            <h6 class="mb-0">İlgi Alanlarım</h6>
                                            <small class="text-muted">Hobileriniz ve ilgi alanlarınızı paylaşın</small>
                                        </div>
                                    </a>
                                    <a href="?admin&page=edit_about&section=education" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="fas fa-graduation-cap me-3 fa-lg text-info"></i>
                                        <div>
                                            <h6 class="mb-0">Eğitim & Deneyim</h6>
                                            <small class="text-muted">Eğitim ve iş deneyimlerinizi listeleyin</small>
                                        </div>
                                    </a>
                                    <a href="?admin&page=edit_about&section=certificates" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="fas fa-award me-3 fa-lg text-warning"></i>
                                        <div>
                                            <h6 class="mb-0">Başarılar & Sertifikalar</h6>
                                            <small class="text-muted">Kazanımlarınızı ve sertifikalarınızı sergileyin</small>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($request === 'edit_about'): ?>
                        <!-- Hakkımda Düzenleme -->
                        <?php
                        $section = $_GET['section'] ?? 'bio';
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
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
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
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-dark">
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
                                                        <td><span class="badge bg-primary"><?= $post['category'] ?></span></td>
                                                        <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
                                                        <td class="text-nowrap">
                                                            <a href="?admin&page=edit_blog&id=<?= $post['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display:inline-block;">
                                                                <input type="hidden" name="action" value="delete_blog">
                                                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu yazıyı silmek istediğinize emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
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
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
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
                                            if ($galleryItems && $galleryItems->num_rows > 0) {
                                                while ($item = $galleryItems->fetch_assoc()):
                                                    ?>
                                                    <div class="col-md-4 mb-4">
                                                        <div class="card h-100">
                                                            <?php if ($item['file_type'] === 'video'): ?>
                                                                <video class="card-img-top" style="height: 200px; object-fit: cover;" controls>
                                                                    <source src="<?= htmlspecialchars($item['file_path']) ?>" type="video/mp4">
                                                                </video>
                                                            <?php elseif ($item['file_type'] === 'pdf'): ?>
                                                                <div class="pdf-preview">
                                                                    <i class="fas fa-file-pdf file-icon"></i>
                                                                    <p class="mt-2">PDF Dosyası</p>
                                                                    <a href="<?= htmlspecialchars($item['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                                                                </div>
                                                            <?php else: ?>
                                                                <img src="<?= htmlspecialchars($item['file_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height: 200px; object-fit: cover;">
                                                            <?php endif; ?>
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
                                                                <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                                                                <div class="d-flex justify-content-between">
                                                                    <a href="?admin&page=edit_gallery&id=<?= $item['id'] ?>" class="btn btn-sm btn-primary me-2">
                                                                        <i class="fas fa-edit me-1"></i> Düzenle
                                                                    </a>
                                                                    <form method="POST">
                                                                        <input type="hidden" name="action" value="delete_gallery">
                                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu öğeyi silmek istediğinize emin misiniz?')">
                                                                            <i class="fas fa-trash me-1"></i> Sil
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile;
                                            } else {
                                                echo '<div class="col-12">
                                                        <div class="alert alert-info text-center py-5">
                                                            <i class="fas fa-images fa-3x mb-3"></i>
                                                            <h4>Galeri boş</h4>
                                                            <p class="text-muted">Yeni öğe eklemek için "Yeni Öğe" butonunu kullanın</p>
                                                        </div>
                                                    </div>';
                                            }
                                        } ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'add_gallery' || $request === 'edit_gallery'): ?>
                        <!-- Galeri Ekleme/Düzenleme -->
                        <?php
                        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                        $item = ['id' => 0, 'title' => '', 'file_path' => '', 'file_type' => 'image', 'category' => 'Fotoğraflar', 'description' => ''];

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
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="galleryForm">
                                    <input type="hidden" name="action" value="save_gallery">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="current_file" value="<?= $item['file_path'] ?>">

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
                                        <label class="form-label">Dosya (Resim, Video veya PDF)</label>
                                        <?php if ($item['file_path'] && file_exists($item['file_path'])): ?>
                                            <div class="mb-2">
                                                <?php if ($item['file_type'] === 'video'): ?>
                                                    <video class="img-thumbnail" style="max-height: 200px;" controls>
                                                        <source src="<?= $item['file_path'] ?>" type="video/mp4">
                                                    </video>
                                                <?php elseif ($item['file_type'] === 'pdf'): ?>
                                                    <div class="pdf-preview">
                                                        <i class="fas fa-file-pdf file-icon"></i>
                                                        <p class="mt-2">Mevcut PDF Dosyası</p>
                                                        <a href="<?= $item['file_path'] ?>" target="_blank" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?= $item['file_path'] ?>" class="img-thumbnail" style="max-height: 200px;">
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="file" class="form-control">
                                        <small class="text-muted">Desteklenen dosyalar: JPG, JPEG, PNG, GIF, WEBP, MP4, MOV, AVI, PDF (Max 200MB)</small>
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
                                            $faqs = $db->query("SELECT * FROM faqs ORDER BY id DESC");
                                            if ($faqs && $faqs->num_rows > 0) {
                                                while ($faq = $faqs->fetch_assoc()):
                                                    ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($faq['question']) ?></h6>
                                                                <p class="mb-0 text-muted"><?= mb_substr(strip_tags($faq['answer']), 0, 100) ?>...</p>
                                                            </div>
                                                            <div class="d-flex">
                                                                <a href="?admin&page=edit_faq&id=<?= $faq['id'] ?>" class="btn btn-sm btn-primary me-2">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST">
                                                                    <input type="hidden" name="action" value="delete_faq">
                                                                    <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu soruyu silmek istediğinize emin misiniz?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile;
                                            } else {
                                                echo '<div class="text-center py-5">
                                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                                        <h4>SSS Bulunamadı</h4>
                                                        <p class="text-muted">Yeni soru eklemek için "Yeni Soru" butonunu kullanın</p>
                                                    </div>';
                                            }
                                        } ?>
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
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
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

                    <?php elseif ($request === 'contacts'): ?>
                        <!-- İletişim Mesajları -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">İletişim Mesajları</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$db_initialized): ?>
                                    <div class="alert alert-warning">
                                        Veritabanı bağlantısı olmadığı için mesajlar görüntülenemiyor.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-dark">
                                            <tr>
                                                <th>Gönderen</th>
                                                <th>Konu</th>
                                                <th>Tarih</th>
                                                <th>İşlemler</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $db = getDB();
                                            if ($db) {
                                                $contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC");
                                                if ($contacts && $contacts->num_rows > 0) {
                                                    while ($contact = $contacts->fetch_assoc()):
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <div><?= htmlspecialchars($contact['name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($contact['email']) ?></small>
                                                            </td>
                                                            <td><?= htmlspecialchars($contact['subject']) ?></td>
                                                            <td><?= date('d.m.Y', strtotime($contact['created_at'])) ?></td>
                                                            <td>
                                                                <a href="?admin&page=view_contact&id=<?= $contact['id'] ?>" class="btn btn-sm btn-primary me-1">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <form method="POST" style="display:inline-block;">
                                                                    <input type="hidden" name="action" value="delete_contact">
                                                                    <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu mesajı silmek istediğinize emin misiniz?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile;
                                                } else {
                                                    echo '<tr>
                                                            <td colspan="4" class="text-center py-5">
                                                                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                                                <h4>Mesaj Bulunamadı</h4>
                                                                <p class="text-muted">Henüz iletişim mesajı alınmamış</p>
                                                            </td>
                                                        </tr>';
                                                }
                                            } ?>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($request === 'view_contact'): ?>
                        <!-- İletişim Mesajı Görüntüleme -->
                        <?php
                        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                        $contact = ['id' => 0, 'name' => '', 'email' => '', 'subject' => '', 'message' => '', 'created_at' => ''];

                        if ($id > 0 && $db_initialized) {
                            $db = getDB();
                            if ($db) {
                                $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
                                $stmt->bind_param("i", $id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $contact = $result->fetch_assoc();
                                }
                            }
                        }
                        ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">İletişim Mesajı</h5>
                                <a href="?admin&page=contacts" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6>Gönderen Bilgileri</h6>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Ad Soyad</label>
                                            <p><?= htmlspecialchars($contact['name']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">E-posta</label>
                                            <p><?= htmlspecialchars($contact['email']) ?></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Konu</label>
                                            <p><?= htmlspecialchars($contact['subject']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tarih</label>
                                            <p><?= date('d.m.Y H:i', strtotime($contact['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6>Mesaj İçeriği</h6>
                                    <div class="p-3 bg-dark rounded">
                                        <?= nl2br(htmlspecialchars($contact['message'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($request === 'settings'): ?>
                        <!-- Site Ayarları -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Site Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="save_settings">

                                    <div class="mb-3">
                                        <label class="form-label">Site Başlığı</label>
                                        <input type="text" name="settings[site_title]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('site_title', SITE_TITLE)) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Adres</label>
                                        <input type="text" name="settings[address]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('address', 'İstanbul, Türkiye')) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" name="settings[email]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('email', 'info@muratcandas.com')) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="settings[phone]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('phone', '+90 555 123 4567')) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Facebook</label>
                                        <input type="url" name="settings[facebook]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('facebook', 'https://facebook.com/muratcandas')) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Twitter</label>
                                        <input type="url" name="settings[twitter]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('twitter', 'https://twitter.com/muratcandas')) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Instagram</label>
                                        <input type="url" name="settings[instagram]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('instagram', 'https://instagram.com/muratcandas')) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">LinkedIn</label>
                                        <input type="url" name="settings[linkedin]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('linkedin', 'https://linkedin.com/in/muratcandas')) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">GitHub</label>
                                        <input type="url" name="settings[github]" class="form-control"
                                               value="<?= htmlspecialchars(getSetting('github', 'https://github.com/muratcandas')) ?>">
                                    </div>

<!--                                    <div class="mb-3">-->
<!--                                        <label class="form-label">Tema</label>-->
<!--                                        <select name="settings[theme]" class="form-select">-->
<!--                                            <option value="dark" --><?php //= getSetting('theme') === 'dark' ? 'selected' : '' ?><!-->Koyu Tema</option>-->
<!--                                            <option value="light" --><?php //= getSetting('theme') === 'light' ? 'selected' : '' ?><!-->Açık Tema</option>-->
<!--                                        </select>-->
<!--                                    </div>-->

                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Ayarları Kaydet
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
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="?"><?= getSetting('site_title', SITE_TITLE) ?></a>
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
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="?page=about_bio">Biyografi</a></li>
                            <li><a class="dropdown-item" href="?page=about_interests">İlgi Alanlarım</a></li>
                            <li><a class="dropdown-item" href="?page=about_education">Eğitim & Deneyim</a></li>
                            <li><a class="dropdown-item" href="?page=about_certificates">Sertifikalar</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $request === 'blog' ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                            Blog
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="?page=blog">Tüm Yazılar</a></li>
                            <li><a class="dropdown-item" href="?page=blog&category=Kişisel">Kişisel Yazılar</a></li>
                            <li><a class="dropdown-item" href="?page=blog&category=Seyahat">Seyahat Notları</a></li>
                            <li><a class="dropdown-item" href="?page=blog&category=Kitap-Film">Kitap & Film Önerileri</a></li>
                            <li><a class="dropdown-item" href="?page=blog&category=Teknoloji">Teknoloji & İlgi Alanları</a></li>
                        </ul>
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
                <a href="?admin" class="btn btn-outline-light me-2">
                    <i class="fas fa-lock me-2"></i>Yönetim
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        <?php if ($request === 'home'): ?>
            <!-- HOME PAGE CONTENT -->
            <section class="hero-bg">
                <div class="container py-5">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="display-4 fw-bold mb-4">Murat Candaş Bozyiğit</h1>
                            <p class="lead mb-4">2023'ten bu zamana kadar. Asp.Net Core -.Net ile web siteleri geliştiriyorum Python ile ise windows macos ve linux uyumlu uygulamalar geliştiriyorum trade botları vesaire.</p>
                            <div class="d-flex">
                                <a href="?page=about_bio" class="btn-gradient me-3">Beni Tanıyın</a>
                                <a href="?page=blog" class="btn btn-outline-primary">Blog Yazılarım</a>
                            </div>

                            <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                <p style="font-weight: 600; margin-bottom: 15px; font-size: 1.1rem;">Örnek Sitelerim...</p>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    <a href="https://www.botanikbambu.com" target="_blank" style="border: 2px solid #ffc107; color: #ffc107; background: transparent; padding: 10px 20px; border-radius: 30px; font-weight: 600; transition: all 0.3s ease; text-decoration: none;">Botanikbambu.com</a>
                                    <a href="https://www.bytewavehq.com" target="_blank" style="border: 2px solid #ffc107; color: #ffc107; background: transparent; padding: 10px 20px; border-radius: 30px; font-weight: 600; transition: all 0.3s ease; text-decoration: none;">BytewaveHQ.com</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 text-center">
                            <div class="position-relative">
                                <img src="
                                https://plus.unsplash.com/premium_photo-1673688152102-b24caa6e8725?q=80&w=3432&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                                     class="img-fluid rounded-circle shadow-lg profile-img" alt="Profil Resmi">
                                <span class="admin-badge"><?= date('Y') ?></span>
                            </div>
                        </div>



                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="row text-center mb-5">
                        <div class="col-12">
                            <h2 class="section-header">Neler Yapıyorum?</h2>
                            <p class="text-muted">İlgi alanlarım ve uzmanlık alanlarım hakkında bilgi edinin</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card h-100">
                                <div class="card-body p-4">
                                    <div class="feature-icon mx-auto">
                                        <i class="fas fa-laptop-code"></i>
                                    </div>
                                    <h5 class="card-title">Web Geliştirme</h5>
                                    <p class="card-text">Modern web teknolojileri kullanarak kullanıcı dostu ve etkileyici web siteleri geliştiriyorum.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card h-100">
                                <div class="card-body p-4">
                                    <div class="feature-icon mx-auto">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <h5 class="card-title">Mobil Uygulamalar</h5>
                                    <p class="card-text">iOS ve Android için performans odaklı ve kullanıcı deneyimi üst düzeyde mobil uygulamalar tasarlıyorum.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card feature-card h-100">
                                <div class="card-body p-4">
                                    <div class="feature-icon mx-auto">
                                        <i class="fas fa-paint-brush"></i>
                                    </div>
                                    <h5 class="card-title">UI/UX Tasarım</h5>
                                    <p class="card-text">Kullanıcı odaklı arayüzler tasarlayarak kullanıcı deneyimini en üst seviyeye çıkarıyorum.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5 bg-dark">
                <div class="container">
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
                                        <a href="?page=about_bio" class="btn btn-outline-primary">Devamını Oku</a>
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
                                                                <div class="d-flex justify-content-between">
                                                                    <h6 class="mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                                                                    <small class="text-muted"><?= date('d.m.Y', strtotime($post['created_at'])) ?></small>
                                                                </div>
                                                                <small class="text-muted"><?= $post['category'] ?></small>
                                                            </a>
                                                        </li>
                                                    <?php endwhile;
                                                } else {
                                                    echo '<li class="list-group-item text-center py-4 text-muted">Henüz blog yazısı eklenmemiş</li>';
                                                }
                                            } else {
                                                echo '<li class="list-group-item">Veritabanı bağlantı hatası</li>';
                                            }
                                            ?>
                                        </ul>
                                        <div class="mt-3 text-center">
                                            <a href="?page=blog" class="btn btn-outline-primary">Tüm Yazıları Gör</a>
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
                </div>
            </section>

        <?php elseif ($request === 'about_bio'): ?>
            <!-- BIOGRAPHY PAGE -->
            <div class="container py-5">
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
            </div>

        <?php elseif ($request === 'about_interests'): ?>
            <!-- INTERESTS PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">İlgi Alanlarım</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($db_initialized): ?>
                                    <?php
                                    $db = getDB();
                                    $about = $db ? $db->query("SELECT * FROM about_sections WHERE section_type = 'interests'") : false;
                                    if ($about && $about->num_rows > 0) {
                                        $interests = $about->fetch_assoc();
                                        echo $interests['content'];
                                    } else {
                                        echo '<div class="empty-state">
                                                <i class="fas fa-heart"></i>
                                                <h3>Henüz ilgi alanları eklenmemiş</h3>
                                                <p>Yönetici panelinden ilgi alanlarınızı ekleyebilirsiniz.</p>
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
            </div>

        <?php elseif ($request === 'about_education'): ?>
            <!-- EDUCATION PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">Eğitim & Deneyim</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($db_initialized): ?>
                                    <?php
                                    $db = getDB();
                                    $about = $db ? $db->query("SELECT * FROM about_sections WHERE section_type = 'education'") : false;
                                    if ($about && $about->num_rows > 0) {
                                        $education = $about->fetch_assoc();
                                        echo $education['content'];
                                    } else {
                                        echo '<div class="empty-state">
                                                <i class="fas fa-graduation-cap"></i>
                                                <h3>Henüz eğitim bilgileri eklenmemiş</h3>
                                                <p>Yönetici panelinden eğitim bilgilerinizi ekleyebilirsiniz.</p>
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
            </div>

        <?php elseif ($request === 'about_certificates'): ?>
            <!-- CERTIFICATES PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">Sertifikalar</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($db_initialized): ?>
                                    <?php
                                    $db = getDB();
                                    $about = $db ? $db->query("SELECT * FROM about_sections WHERE section_type = 'certificates'") : false;
                                    if ($about && $about->num_rows > 0) {
                                        $certificates = $about->fetch_assoc();
                                        echo $certificates['content'];
                                    } else {
                                        echo '<div class="empty-state">
                                                <i class="fas fa-award"></i>
                                                <h3>Henüz sertifika eklenmemiş</h3>
                                                <p>Yönetici panelinden sertifikalarınızı ekleyebilirsiniz.</p>
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
            </div>

        <?php elseif ($request === 'blog'): ?>
            <!-- BLOG PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">Blog Yazılarım</h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <?php if ($db_initialized):
                            $category = $_GET['category'] ?? '';
                            $categoryTitle = $category ? htmlspecialchars($category) : 'Tüm Yazılar';
                            ?>
                            <div class="mb-4">
                                <h4>Kategori: <?= $categoryTitle ?></h4>
                            </div>
                            <div class="row">
                                <?php
                                $db = getDB();
                                if ($db) {
                                    $sql = "SELECT * FROM blog_posts";
                                    if ($category) {
                                        $sql .= " WHERE category = '".$db->real_escape_string($category)."'";
                                    }
                                    $sql .= " ORDER BY created_at DESC";

                                    $posts = $db->query($sql);
                                    if ($posts && $posts->num_rows > 0) {
                                        while ($post = $posts->fetch_assoc()):
                                            ?>
                                            <div class="col-md-4 mb-4">
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

        <?php elseif ($request === 'blog_post'): ?>
            <!-- BLOG POST DETAIL -->
            <?php
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $post = ['id' => 0, 'title' => '', 'content' => '', 'category' => '', 'created_at' => ''];

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

            <div class="container py-5">
                <?php if ($post['id']): ?>
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="mb-0"><?= htmlspecialchars($post['title']) ?></h2>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <span class="badge bg-primary"><?= $post['category'] ?></span>
                                        <small class="text-muted"><?= date('d.m.Y', strtotime($post['created_at'])) ?></small>
                                    </div>

                                    <div class="blog-content">
                                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                    <h3>Yazı Bulunamadı</h3>
                                    <p class="text-muted">İstediğiniz blog yazısı bulunamadı veya silinmiş olabilir.</p>
                                    <a href="?page=blog" class="btn btn-primary">Blog Sayfasına Dön</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($request === 'gallery'): ?>
            <!-- GALLERY PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">Galeri</h2>
                            </div>
                        </div>
                    </div>

                    <?php if ($db_initialized): ?>
                        <?php
                        $db = getDB();
                        if ($db) {
                            $galleryItems = $db->query("SELECT * FROM gallery_items ORDER BY id DESC");
                            if ($galleryItems && $galleryItems->num_rows > 0) {
                                echo '<div class="row">';
                                while ($item = $galleryItems->fetch_assoc()):
                                    ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <?php if ($item['file_type'] === 'video'): ?>
                                                <video class="card-img-top" style="height: 250px; object-fit: cover;" controls>
                                                    <source src="<?= htmlspecialchars($item['file_path']) ?>" type="video/mp4">
                                                </video>
                                            <?php elseif ($item['file_type'] === 'pdf'): ?>
                                                <div class="pdf-preview">
                                                    <i class="fas fa-file-pdf file-icon"></i>
                                                    <p class="mt-2">PDF Dosyası</p>
                                                    <a href="<?= htmlspecialchars($item['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                                                </div>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($item['file_path']) ?>" data-lightbox="gallery" data-title="<?= htmlspecialchars($item['title']) ?>">
                                                    <img src="<?= htmlspecialchars($item['file_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height: 250px; object-fit: cover;">
                                                </a>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
                                                <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                                echo '</div>';
                            } else {
                                echo '<div class="col-12">
                                        <div class="card">
                                            <div class="card-body text-center py-5">
                                                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                                <h4>Galeri Boş</h4>
                                                <p class="text-muted">Henüz galeriye öğe eklenmemiş</p>
                                            </div>
                                        </div>
                                    </div>';
                            }
                        } ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-database"></i>
                                <h3>Veriye ulaşılamıyor</h3>
                                <p>Veritabanı bağlantısı olmadığı için içerik gösterilemiyor.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($request === 'faq'): ?>
            <!-- FAQ PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">Sıkça Sorulan Sorular</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($db_initialized): ?>
                                    <div class="accordion" id="faqAccordion">
                                        <?php
                                        $db = getDB();
                                        if ($db) {
                                            $faqs = $db->query("SELECT * FROM faqs ORDER BY id DESC");
                                            if ($faqs && $faqs->num_rows > 0) {
                                                $count = 0;
                                                while ($faq = $faqs->fetch_assoc()):
                                                    $count++;
                                                    ?>
                                                    <div class="accordion-item">
                                                        <h3 class="accordion-header" id="heading<?= $count ?>">
                                                            <button class="accordion-button <?= $count > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $count ?>" aria-expanded="<?= $count === 1 ? 'true' : 'false' ?>" aria-controls="collapse<?= $count ?>">
                                                                <?= htmlspecialchars($faq['question']) ?>
                                                            </button>
                                                        </h3>
                                                        <div id="collapse<?= $count ?>" class="accordion-collapse collapse <?= $count === 1 ? 'show' : '' ?>" aria-labelledby="heading<?= $count ?>" data-bs-parent="#faqAccordion">
                                                            <div class="accordion-body">
                                                                <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile;
                                            } else {
                                                echo '<div class="text-center py-5">
                                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                                        <h3>SSS Bulunamadı</h3>
                                                        <p class="text-muted">Henüz sıkça sorulan soru eklenmemiş</p>
                                                    </div>';
                                            }
                                        } ?>
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
            </div>

        <?php elseif ($request === 'contact'): ?>
            <!-- CONTACT PAGE -->
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="mb-0">İletişim</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($actionResult): ?>
                                    <div class="alert alert-success">
                                        Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağım.
                                    </div>
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h5>İletişim Bilgilerim</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-3">
                                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                <?= htmlspecialchars(getSetting('address', 'İstanbul, Türkiye')) ?>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-envelope text-primary me-2"></i>
                                                <?= htmlspecialchars(getSetting('email', 'info@muratcandas.com')) ?>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-phone text-primary me-2"></i>
                                                <?= htmlspecialchars(getSetting('phone', '+90 555 123 4567')) ?>
                                            </li>
                                        </ul>

                                        <div class="mt-4">
                                            <h5>Sosyal Medya</h5>
                                            <div class="d-flex mt-3">
                                                <?php if (getSetting('facebook')): ?>
                                                    <a href="<?= htmlspecialchars(getSetting('facebook')) ?>" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                                <?php endif; ?>
                                                <?php if (getSetting('twitter')): ?>
                                                    <a href="<?= htmlspecialchars(getSetting('twitter')) ?>" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                                                <?php endif; ?>
                                                <?php if (getSetting('instagram')): ?>
                                                    <a href="<?= htmlspecialchars(getSetting('instagram')) ?>" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                                                <?php endif; ?>
                                                <?php if (getSetting('linkedin')): ?>
                                                    <a href="<?= htmlspecialchars(getSetting('linkedin')) ?>" class="social-link" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                                                <?php endif; ?>
                                                <?php if (getSetting('github')): ?>
                                                    <a href="<?= htmlspecialchars(getSetting('github')) ?>" class="social-link" target="_blank"><i class="fab fa-github"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Mesaj Gönder</h5>
                                        <form method="POST" class="contact-form">
                                            <input type="hidden" name="action" value="save_contact">
                                            <div class="mb-3">
                                                <label class="form-label">Adınız Soyadınız</label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">E-posta Adresiniz</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Konu</label>
                                                <input type="text" name="subject" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Mesajınız</label>
                                                <textarea name="message" class="form-control" rows="4" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-gradient w-100">Gönder</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- DYNAMIC PAGE CONTENT -->
            <div class="container py-5">
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
            </div>
        <?php endif; ?>
    </main>

    <footer class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="text-white mb-4"><?= getSetting('site_title', SITE_TITLE) ?></h5>
                    <p class="text-muted">2023’ten bu yana, ASP.NET Core ve .NET teknolojilerini kullanarak web siteleri geliştiriyorum. Ayrıca Python ile Windows, macOS ve Linux uyumlu uygulamalar, trade botları ve çeşitli otomasyon çözümleri geliştiriyorum.</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-white mb-4">Hızlı Linkler</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="?page=home">Ana Sayfa</a></li>
                        <li><a href="?page=about_bio">Hakkımda</a></li>
                        <li><a href="?page=blog">Blog</a></li>
                        <li><a href="?page=gallery">Galeri</a></li>
                        <li><a href="?page=contact">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="text-white mb-4">Sosyal Medya</h5>
                    <div class="d-flex">
                        <?php if (getSetting('facebook')): ?>
                            <a href="<?= htmlspecialchars(getSetting('facebook')) ?>" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('twitter')): ?>
                            <a href="<?= htmlspecialchars(getSetting('twitter')) ?>" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('instagram')): ?>
                            <a href="<?= htmlspecialchars(getSetting('instagram')) ?>" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('linkedin')): ?>
                            <a href="<?= htmlspecialchars(getSetting('linkedin')) ?>" class="social-link" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('github')): ?>
                            <a href="<?= htmlspecialchars(getSetting('github')) ?>" class="social-link" target="_blank"><i class="fab fa-github"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5 class="text-white mb-4">İletişim</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-muted">
                            <i class="fas fa-envelope me-2"></i> <?= htmlspecialchars(getSetting('email', 'info@muratcandas.com')) ?>
                        </li>
                        <li class="mb-2 text-muted">
                            <i class="fas fa-phone me-2"></i> <?= htmlspecialchars(getSetting('phone', '+90 555 123 4567')) ?>
                        </li>
                        <li class="mb-2 text-muted">
                            <i class="fas fa-map-marker-alt me-2"></i> <?= htmlspecialchars(getSetting('address', 'İstanbul, Türkiye')) ?>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="bg-secondary mt-0 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?= date('Y') ?> <?= getSetting('site_title', SITE_TITLE) ?>. Tüm hakları saklıdır.</p>
                </div>

            </div>
        </div>
    </footer>

    <!-- Tema değiştirici -->
<!--    <div class="theme-switcher" onclick="toggleTheme()">-->
<!--        <i class="fas fa---><?php //= $currentTheme === 'dark' ? 'sun' : 'moon' ?><!--"></i>-->
<!--    </div>-->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script>
    // Lightbox ayarları
    //lightbox.option({
    //'resizeDuration': 200,
    //  'wrapAround': true,
    //  'showImageNumberLabel': true
    //});

    // Tema değiştirme
    //function toggleTheme() {
    //const currentTheme = document.documentElement.getAttribute('data-theme');
    //  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    //    document.documentElement.setAttribute('data-theme', newTheme);
    //      window.location.href = '?theme=' + newTheme;
    //}


    // Galeri formu AJAX ile gönderim
    const galleryForm = document.getElementById('galleryForm');
    if (galleryForm) {
        galleryForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: data.message
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Bir hata oluştu: ' + error
                    });
                });
        });
    }

    // Genel hata mesajları için SweetAlert
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!valid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Lütfen tüm zorunlu alanları doldurun.',
                    confirmButtonColor: '#4e73df'
                });
            }
        });
    });
</script>
</body>
</html>