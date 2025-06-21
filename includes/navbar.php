<style>
    :root {
        --brown-primary: #8B4513;
        --brown-secondary: #A0522D;
        --brown-light: #D2B48C;
        --brown-dark: #654321;
        --black-primary: #1a1a1a;
        --black-secondary: #2d2d2d;
        --black-light: #404040;
    }
    
    .navbar-brown {
        background: linear-gradient(135deg, var(--black-primary) 0%, var(--brown-dark) 100%);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        position: static; /* Ubah dari fixed ke static */
        z-index: 100;
    }
    
    .navbar-brown .navbar-brand {
        color: var(--brown-light) !important;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .navbar-brown .nav-link {
        color: var(--brown-light) !important;
        transition: all 0.3s ease;
    }
    
    .navbar-brown .nav-link:hover {
        color: white !important;
        transform: translateY(-1px);
    }
    
    .dropdown-menu {
        background: var(--black-secondary);
        border: 1px solid var(--brown-secondary);
    }
    
    .dropdown-item {
        color: var(--brown-light);
        transition: all 0.3s ease;
    }
    
    .dropdown-item:hover {
        background: var(--brown-primary);
        color: white;
    }
    
    .bg-brown {
        background-color: var(--brown-primary) !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-brown">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-boxes"></i> INVESTO
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border-color: var(--brown-light);">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> 
                        <?php echo $_SESSION['nama_lengkap']; ?>
                        <?php echo getRoleBadge($_SESSION['role']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                        <?php if (isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="log-aktivitas.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
