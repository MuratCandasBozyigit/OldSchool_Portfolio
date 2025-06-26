<?php
session_start();

// --- DB Ayarları ---
$host = 'localhost';
$db = 'tekdosyaproje';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE $db");
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// --- Tabloları oluştur ---
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255)
    )",
    "blog" => "CREATE TABLE IF NOT EXISTS blog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        baslik VARCHAR(255),
        icerik TEXT,
        kategori VARCHAR(100),
        tarih DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "hakkimda" => "CREATE TABLE IF NOT EXISTS hakkimda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alan VARCHAR(50),
        icerik TEXT
    )",
    "sss" => "CREATE TABLE IF NOT EXISTS sss (
        id INT AUTO_INCREMENT PRIMARY KEY,
        soru VARCHAR(255),
        cevap TEXT
    )",
    "iletisim" => "CREATE TABLE IF NOT EXISTS iletisim (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adsoyad VARCHAR(100),
        email VARCHAR(100),
        mesaj TEXT,
        tarih DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
}

// --- Admin kullanıcıyı ekle (ilk sefer için) ---
$checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE username='admin'")->fetchColumn();
if (!$checkAdmin) {
    $hash = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', $hash]);
}

// --- Sayfa belirle ---
$page = isset($_GET['sayfa']) ? $_GET['sayfa'] : 'anasayfa';
$admin = isset($_GET['admin']);

// --- Giriş kontrolü ---
if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['admin'] = true;
    } else {
        echo "<script>alert('Giriş başarısız');</script>";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

function menu() {
    echo "<nav class='flex justify-center space-x-4 bg-gray-800 text-white py-4 shadow'>";
    $items = ['anasayfa'=>'Anasayfa','hakkimda'=>'Hakkımda','blog'=>'Blog','galeri'=>'Galeri','sss'=>'SSS','iletisim'=>'İletişim'];
    foreach ($items as $k => $v) {
        echo "<a class='hover:underline px-3' href='?sayfa=$k'>$v</a>";
    }
    if (isset($_SESSION['admin']) ? $_SESSION['admin'] : false) {
        echo "<a class='hover:underline px-3' href='?admin=1'>Admin</a><a class='hover:underline px-3' href='?logout=1'>Çıkış</a>";
    } else {
        echo "<a class='hover:underline px-3' href='?admin=1'>Giriş</a>";
    }
    echo "</nav>";
}

// --- Sayfa içeriği yükle ---
echo "<html><head><meta charset='utf-8'>
<title>Murat Candaş Bozyiğit - Web Proje</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<script src='https://cdn.tailwindcss.com'></script>
</head><body class='bg-gray-100 text-gray-900'>";
menu();
echo "<div class='container py-5'>";

if ($admin && !(isset($_SESSION['admin']) ? $_SESSION['admin'] : false)) {
    echo "<div class='max-w-md mx-auto bg-white p-5 rounded shadow'>
    <h2 class='text-xl font-bold mb-4'>Admin Giriş</h2>
    <form method='post' class='space-y-4'>
        <input name='username' class='form-control' placeholder='Kullanıcı Adı'>
        <input name='password' type='password' class='form-control' placeholder='Şifre'>
        <button name='login' class='btn btn-primary w-100'>Giriş Yap</button>
    </form></div>";
    exit;
}

switch ($page) {
    case 'anasayfa':
        echo "<div class='bg-white shadow p-4 rounded'><h2 class='text-2xl font-bold mb-4'>Hoş Geldiniz</h2>
        <p class='mb-3'>Ben Murat Candaş Bozyiğit. Siteme hoş geldiniz!</p>";
        $blogs = $pdo->query("SELECT * FROM blog ORDER BY RAND() LIMIT 3")->fetchAll();
        foreach ($blogs as $b) {
            echo "<div class='border-t pt-3'><h4 class='text-lg font-semibold'>{$b['baslik']}</h4><p>{$b['icerik']}</p></div>";
        }
        echo "</div>";
        break;
    case 'hakkimda':
        $veri = $pdo->query("SELECT * FROM hakkimda")->fetchAll();
        foreach ($veri as $v) {
            echo "<div class='bg-white p-4 shadow mb-3 rounded'><h3 class='text-xl font-bold'>{$v['alan']}</h3><p>{$v['icerik']}</p></div>";
        }
        break;
    case 'blog':
        $veri = $pdo->query("SELECT * FROM blog ORDER BY tarih DESC")->fetchAll();
        foreach ($veri as $v) {
            echo "<div class='bg-white p-4 shadow mb-3 rounded'><h3 class='text-xl font-bold'>{$v['baslik']}</h3><p>{$v['icerik']}</p><small class='text-muted'>{$v['kategori']} | {$v['tarih']}</small></div>";
        }
        break;
    case 'galeri':
        echo "<div class='bg-white p-4 shadow rounded'><h3 class='text-xl font-bold'>Galeri</h3><p>Görseller yakında eklenecek.</p></div>";
        break;
    case 'sss':
        $veri = $pdo->query("SELECT * FROM sss")->fetchAll();
        foreach ($veri as $v) {
            echo "<div class='bg-white p-4 shadow mb-3 rounded'><b>{$v['soru']}</b><br>{$v['cevap']}</div>";
        }
        break;
    case 'iletisim':
        if (isset($_POST['adsoyad']) ? $_POST['adsoyad'] : false) {
            $stmt = $pdo->prepare("INSERT INTO iletisim (adsoyad, email, mesaj) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['adsoyad'], $_POST['email'], $_POST['mesaj']
            ]);
            echo "<p class='alert alert-success'>Mesajınız alınmıştır, teşekkürler!</p>";
        }
        echo "<div class='bg-white p-4 shadow rounded'><form method='post' class='space-y-3'>
            <input name='adsoyad' class='form-control' placeholder='Ad Soyad'>
            <input name='email' class='form-control' placeholder='E-posta'>
            <textarea name='mesaj' class='form-control' placeholder='Mesajınız'></textarea>
            <button class='btn btn-success'>Gönder</button>
        </form></div>";
        break;
    default:
        echo "<div class='alert alert-danger'>Sayfa bulunamadı!</div>";
}

if ($admin && (isset($_SESSION['admin']) ? $_SESSION['admin'] : false)) {
    echo "<hr><div class='bg-white p-4 shadow rounded'><h2 class='text-xl font-bold mb-3'>Yönetim Paneli</h2>
    <form method='post' class='space-y-3'>
        <input name='blog_baslik' class='form-control' placeholder='Blog Başlığı'>
        <textarea name='blog_icerik' class='form-control' placeholder='İçerik'></textarea>
        <input name='blog_kategori' class='form-control' placeholder='Kategori'>
        <button name='ekle_blog' class='btn btn-primary'>Blog Ekle</button>
    </form></div>";

    if (isset($_POST['ekle_blog'])) {
        $stmt = $pdo->prepare("INSERT INTO blog (baslik, icerik, kategori) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['blog_baslik'], $_POST['blog_icerik'], $_POST['blog_kategori']
        ]);
        echo "<div class='alert alert-success mt-3'>Blog eklendi.</div>";
    }

    echo "<div class='bg-white p-4 mt-4 rounded shadow'><h3 class='text-lg font-bold'>Gelen Mesajlar</h3>";
    $msgs = $pdo->query("SELECT * FROM iletisim ORDER BY tarih DESC")->fetchAll();
    foreach ($msgs as $m) {
        echo "<div class='border-b py-2'><b>{$m['adsoyad']}</b> ({$m['email']})<br>{$m['mesaj']}</div>";
    }
    echo "</div>";
}



echo "</div></body></html>";
?>
