<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Fetch all products
$stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $_SESSION['success'] = "Product deleted successfully!";
    redirect('manage_products.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span>📦 Manage Products</span>
            <div class="buttons">
                <a href="index.php" class="button">Dashboard</a>
                <a href="login.php?logout=true" class="button danger">Logout</a>
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="index.php">📊 Dashboard</a>
            <a href="manage_users.php">👥 Users</a>
            <a href="manage_products.php">📦 Products</a>
            <a href="add_product.php">➕ Add Product</a>
            <a href="admin_orders.php">🛍️ Orders</a>
            <a href="manage_payments.php">💳 Payments</a>
            <a href="assign_download_link.php">🔗 Assign Links</a>
            <a href="manage_reviews.php">⭐ Reviews</a>
        </div>
        
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success">
                    <span>✓</span>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="font-size: 1.75rem;">📦 All Products</h2>
                <a href="add_product.php" class="button success">➕ Add New Product</a>
            </div>
            
            <?php if (empty($products)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 40px;">No products found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($product['id']); ?></strong></td>
                            <td><img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Product"></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <span style="background: rgba(99, 102, 241, 0.2); color: var(--primary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">
                                    <?php echo htmlspecialchars(ucfirst($product['type'])); ?>
                                </span>
                            </td>
                            <td><strong>$<?php echo number_format($product['price'], 2); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="button" style="font-size: 0.75rem; padding: 6px 12px; margin-right: 5px;">✏️ Edit</a>
                                <a href="manage_products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="button danger" style="font-size: 0.75rem; padding: 6px 12px;">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
</body>
</html>