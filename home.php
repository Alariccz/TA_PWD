<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoEnzyme - Solusi Alami untuk Bumi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2d6a4f; 
        }
        .navbar-brand, .nav-link {
            color: #ffffff !important;
        }
        .nav-link:hover {
            color: #d8f3dc !important;
        }
        /* Hero Section Styling */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .content-section {
            padding: 80px 0;
        }
        footer {
            background-color: #1b4332;
            color: #d8f3dc;
            padding: 50px 0 20px;
        }
        .footer-title {
            color: #ffffff;
            font-weight: bold;
            margin-bottom: 20px;
            border-left: 4px solid #74c69d;
            padding-left: 10px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">EcoEnzyme.id</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#produk">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <h1 class="display-3 fw-bold">EcoEnzyme Indonesia</h1>
            <p class="lead mb-4">Solusi pembersih alami dari fermentasi limbah organik untuk masa depan yang lebih hijau.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="login.php" class="btn btn-success btn-lg px-4 gap-3">Login</a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">Daftar Akun</a>
            </div>
        </div>
    </header>

    <main class="container content-section" id="tentang">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Apa itu EcoEnzyme?</h2>
                <p class="text-secondary">
                    Eco-enzyme adalah cairan serbaguna yang dihasilkan dari fermentasi sampah organik (seperti kulit buah dan sayuran), gula merah/molase, dan air. Cairan ini merupakan hasil penemuan Dr. Rosukon Poompanvong, seorang peneliti dari Thailand.
                </p>
                <p class="text-secondary">
                    Selain membantu mengurangi beban sampah di TPA, EcoEnzyme dapat digunakan sebagai pupuk alami, pengusir hama, hingga cairan pembersih lantai yang aman tanpa bahan kimia berbahaya.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #40916c;">
                    <h3>Manfaat Utama</h3>
                    <ul class="mt-3">
                        <li>Memurnikan udara dari polutan.</li>
                        <li>Sebagai antiseptik alami.</li>
                        <li>Meningkatkan kualitas air tanah.</li>
                        <li>Pembersih rumah tangga serbaguna.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer id="kontak">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">Visi Kami</h5>
                    <p>Menjadi pelopor pengelolaan limbah organik rumah tangga di Indonesia untuk mendukung keberlanjutan lingkungan hidup.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">Kontak & Alamat</h5>
                    <p>
                        <strong>Alamat:</strong> Jl. Hijau Lestari No. 12, Jakarta Selatan, Indonesia<br>
                        <strong>Email:</strong> halo@ecoenzyme.id<br>
                        <strong>WhatsApp:</strong> +62 812-3456-7890
                    </p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title">Biodata Perusahaan</h5>
                    <p>
                        <strong>Nama:</strong> PT EcoEnzyme Lestari Nusantara<br>
                        <strong>Berdiri:</strong> Tahun 2021<br>
                        <strong>NIB:</strong> 1234567890123<br>
                        <strong>Sektor:</strong> Teknologi Ramah Lingkungan
                    </p>
                </div>
            </div>
            <hr class="mt-4" style="background-color: #74c69d;">
            <div class="text-center small">
                &copy; <?php echo date("Y"); ?> EcoEnzyme Indonesia. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>