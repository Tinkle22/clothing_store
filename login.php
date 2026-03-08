<?php
// login.php
require_once __DIR__ . '/includes/header.php';

redirectIfLoggedIn();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            redirect(getRedirectPath());
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="container py-20 flex justify-center items-center min-h-[70vh]">
    <div class="bg-white p-12 md:p-16 rounded-xl shadow-md max-w-lg w-full border border-beige">
        <h2 class="text-center mb-4 text-3xl font-semibold">Welcome Back</h2>
        <p class="text-center text-soft-brown mb-12 text-sm leading-relaxed">Enter your credentials to access your account and manage your orders.</p>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 p-6 rounded-md mb-8 text-sm border border-red-100 flex items-center gap-3">
                <span class="font-bold text-lg">!</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST">
            <div class="form-group mb-8">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="example@mail.com" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            </div>
            
            <div class="form-group mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="#" class="text-xs text-soft-brown hover:text-dark transition">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary w-full py-4 mt-12 text-lg shadow-md">Sign In</button>
        </form>
        
        <div class="text-center mt-12 pt-8 border-t border-sand">
            <p class="text-sm text-soft-brown">
                Don't have an account? <a href="/register.php" class="text-terracotta hover:text-dark transition font-semibold">Create one</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
