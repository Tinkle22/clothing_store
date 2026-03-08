<?php
// register.php
require_once __DIR__ . '/includes/header.php';

redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "Please fill in all required fields.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already registered.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        if ($insertStmt->execute([$name, $email, $hashedPassword])) {
            $userId = $pdo->lastInsertId();
            
            // Auto-login
            loginUser(['id' => $userId, 'name' => $name, 'role' => 'customer']);
            
            setFlashMessage('success', 'Registration successful! Welcome to Aura.');
            redirect(getRedirectPath());
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}
?>

<div class="container py-16 flex justify-center items-center min-h-[70vh]">
    <div class="bg-white p-8 md:p-12 rounded-lg shadow-soft max-w-md w-full border border-beige">
        <h2 class="text-center mb-2">Create Account</h2>
        <p class="text-center text-soft-brown mb-8 text-sm">Join Aura to manage your orders and wishlist.</p>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded mb-6 text-sm border border-red-100">
                <ul class="list-disc pl-5">
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/register.php" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 mt-4 text-lg">Create Account</button>
        </form>
        
        <p class="text-center mt-6 text-sm text-soft-brown">
            Already have an account? <a href="/login.php" class="text-terracotta hover:underline font-medium">Log In</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
