<?php
// admin/add_product.php - Ajouter un produit

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Inclure le fichier de connexion à la base de données
require_once '../db.php';

// Catégories par défaut à créer si elles n'existent pas
$default_categories = ['Charcuterie', 'Pack', 'Fruits', 'Légumes', 'Autres'];

// Vérifier et créer les catégories si nécessaire
try {
    foreach ($default_categories as $category_name) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
        $stmt->execute([$category_name]);
        $category = $stmt->fetch();
        
        if (!$category) {
            // La catégorie n'existe pas, la créer
            $insert_stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
            $insert_stmt->execute([$category_name]);
        }
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la vérification des catégories: " . $e->getMessage();
}

// Récupérer les catégories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des catégories: " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $categorie_id = !empty($_POST['categorie_id']) ? intval($_POST['categorie_id']) : null;
    
    // Gestion de l'upload d'image
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Vérifier l'extension du fichier
        if (in_array($file_extension, $allowed_extensions)) {
            // Créer le répertoire uploads s'il n'existe pas
            if (!file_exists('../uploads')) {
                mkdir('../uploads', 0777, true);
            }
            
            // Générer un nom unique pour l'image
            $image_name = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = '../uploads/' . $image_name;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Succès de l'upload
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error = "Format de fichier non autorisé. Utilisez JPG, JPEG, PNG, GIF ou WEBP.";
        }
    }
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom du produit est obligatoire";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire";
    }
    
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0";
    }
    
    if ($stock < 0) {
        $errors[] = "Le stock ne peut pas être négatif";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produits (nom, description, prix, stock, image_url, categorie_id) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $description, $prix, $stock, $image_name, $categorie_id]);
            
            $_SESSION['success'] = "Produit ajouté avec succès!";
            header("Location: products.php");
            exit();
            
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Produit - Nuur Market Admin</title>
    
    <!-- Favicon -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --vert-nuur: #28a745;
            --jaune-nuur: #FFC107;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7f9;
            overflow-x: hidden;
        }
        
        /* Logo container */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .basket-icon {
            color: var(--jaune-nuur);
            font-size: 1.8rem;
        }
        
        .logo-text {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--vert-nuur);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .sidebar ul.components {
            padding: 20px 0;
        }
        
        .sidebar ul li a {
            padding: 15px 20px;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar ul li a:hover {
            color: white;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .sidebar ul li a.active {
            color: white;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 500;
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--vert-nuur);
            border-color: var(--vert-nuur);
        }
        
        .btn-primary:hover {
            background-color: #218838;
            border-color: #218838;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--vert-nuur);
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        /* Image Upload */
        .image-upload-container {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .image-upload-container:hover {
            border-color: var(--vert-nuur);
            background-color: rgba(40, 167, 69, 0.05);
        }
        
        .image-upload-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .image-preview-container {
            text-align: center;
            margin-top: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -var(--sidebar-width);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Page header avec logo */
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .page-header-logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .page-header-icon {
            background-color: var(--jaune-nuur);
            color: var(--vert-nuur);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .page-header-title {
            color: var(--vert-nuur);
            margin: 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <span class="basket-icon"><i class="fas fa-shopping-basket"></i></span>
                <span class="logo-text">Nuur Market</span>
            </div>
            <p>Administration</p>
        </div>
        
        <ul class="components">
            <li>
                <a href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Commandes
                </a>
            </li>
            <li>
                <a href="products.php" class="active">
                    <i class="fas fa-box"></i> Produits
                </a>
            </li>
           
            <li>
                <a href="customers.php">
                    <i class="fas fa-users"></i> Clients
                </a>
            </li>
            <li>
                <a href="../index.html" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Voir le site
                </a>
            </li>
            <li>
                <a href="../index.html">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button id="sidebarCollapse" class="btn" style="background-color: var(--vert-nuur); color: white;">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="ms-auto">
                    <span class="navbar-text me-3">
                        Bienvenue, <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                    </span>
                    <a href="../index.html" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Ajouter un Produit</h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nom du produit *</label>
                                    <input type="text" name="nom" class="form-control" required 
                                           value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea name="description" class="form-control" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Prix (FCFA) *</label>
                                            <input type="number" name="prix" class="form-control" step="0.01" min="0" required 
                                                   value="<?php echo isset($_POST['prix']) ? $_POST['prix'] : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Stock *</label>
                                            <input type="number" name="stock" class="form-control" min="0" required 
                                                   value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '0'; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <select name="categorie_id" id="categorie" class="form-select">
                                        <option value="">Sélectionner une catégorie</option>
                                        <?php foreach ($categories as $categorie): ?>
                                            <option value="<?php echo $categorie['id']; ?>" 
                                                    <?php echo (isset($_POST['categorie_id']) && $_POST['categorie_id'] == $categorie['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categorie['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Image du produit</label>
                                    <div class="image-upload-container" id="image-upload-container">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-1">Cliquez pour sélectionner une image</p>
                                        <p class="text-muted small">Formats supportés: JPG, PNG, GIF, WEBP</p>
                                        <input type="file" name="image" id="image-input" class="d-none" accept="image/*">
                                    </div>
                                    <div class="image-preview-container">
                                        <img id="image-preview" class="image-upload-preview" src="" alt="Aperçu de l'image">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <a href="products.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gestion de l'upload d'image
        document.getElementById('image-upload-container').addEventListener('click', function() {
            document.getElementById('image-input').click();
        });
        
        document.getElementById('image-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('image-preview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                    
                    // Mettre à jour le texte du conteneur
                    document.querySelector('#image-upload-container p').textContent = 'Image sélectionnée';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Empêcher la propagation du clic sur le conteneur
        document.getElementById('image-input').addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        $('#sidebarCollapse').on('click', function () {
            $('.sidebar').toggleClass('active');
            $('.main-content').toggleClass('active');
        });
    </script>
</body>
</html>