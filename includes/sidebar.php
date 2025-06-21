<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <!-- Menu untuk semua user -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'active' : ''; ?>" href="barang.php">
                    <i class="fas fa-boxes"></i> Data Barang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>" href="transaksi.php">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </li>
            
            <!-- Menu khusus Admin -->
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <hr class="sidebar-divider">
            </li>
            <li class="nav-item">
                <small class="sidebar-heading">ADMIN ONLY</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active' : ''; ?>" href="kategori.php">
                    <i class="fas fa-tags"></i> Kategori
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'supplier.php' ? 'active' : ''; ?>" href="supplier.php">
                    <i class="fas fa-truck"></i> Supplier
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Kelola User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'log-aktivitas.php' ? 'active' : ''; ?>" href="log-aktivitas.php">
                    <i class="fas fa-history"></i> Log Aktivitas
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<style>
    .sidebar {
        position: fixed;
        top: 0; /* Ubah dari 56px ke 0 agar full sampai atas */
        bottom: 0;
        left: 0;
        z-index: 99; /* Turunkan z-index agar di bawah navbar */
        padding: 76px 0 0; /* Tambah padding top untuk memberi ruang navbar */
        background: linear-gradient(180deg, var(--black-secondary) 0%, var(--brown-dark) 100%);
        box-shadow: inset -1px 0 0 rgba(139, 69, 19, 0.3);
    }

    .sidebar .nav-link {
        font-weight: 500;
        color: var(--brown-light);
        padding: 12px 20px;
        border-radius: 0 25px 25px 0;
        margin: 2px 0;
        margin-right: 10px;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link.active {
        color: white;
        background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
        box-shadow: 0 3px 10px rgba(139, 69, 19, 0.4);
    }

    .sidebar .nav-link:hover {
        color: white;
        background: rgba(139, 69, 19, 0.3);
        transform: translateX(5px);
    }

    .sidebar .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .sidebar-divider {
        border-color: var(--brown-secondary);
        margin: 15px 20px;
    }

    .sidebar-heading {
        color: var(--brown-secondary);
        font-weight: bold;
        padding: 0 20px;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    main {
        margin-top: 56px; /* Tetap beri margin top untuk navbar */
        background: linear-gradient(135deg, #f8f9fa 0%, var(--brown-light) 100%);
        min-height: calc(100vh - 56px);
    }

    /* Pastikan navbar tetap di atas sidebar */
    .navbar-brown {
        z-index: 100;
        position: relative;
    }

    @media (max-width: 767.98px) {
        .sidebar {
            top: 56px; /* Pada mobile, tetap di bawah navbar */
            padding: 20px 0 0;
        }
    }
</style>
