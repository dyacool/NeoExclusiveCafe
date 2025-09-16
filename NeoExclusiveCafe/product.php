<?php
header('Location: ../../pages/auth/login-signup.php');
?>
<link rel="stylesheet" href="../../css/users/product.css" />
<img src="<?= htmlspecialchars($product['image_url'] ?: '../assets/images/default-product.png') ?>" alt="Product Image" width="50" /> 