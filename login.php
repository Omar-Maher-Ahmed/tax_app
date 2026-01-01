<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- ثغرة خطيرة هنا: استقبال البيانات مباشرة بدون فلترة ---
    // ده بيسمح بـ SQL Injection
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    
    // تنفيذ الاستعلام
    try {
        $stmt = $pdo->query($sql);
        $user = $stmt->fetch();

        if ($user) {
            // تسجيل الدخول ناجح
            $_SESSION['username'] = $user['username'];
            
            // --- ثغرة رفع الصلاحيات (Privilege Escalation) ---
            // تخزين الصلاحية في الكوكيز بشكل غير آمن (سهل التعديل)
            setcookie("role", $user['role'], time() + 3600, "/"); 
            
            header("Location: index.php");
            exit();
        } else {
            $error = "بيانات الدخول غير صحيحة!";
        }
    } catch (PDOException $e) {
        // طباعة الخطأ بتفاصيله بتساعد الهاكر يفهم الداتابيز (Information Disclosure)
        $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html dir="rtl">
<head>
    <title>تسجيل الدخول</title>
    <style>
        body { font-family: Tahoma; background-color: #f4f4f9; text-align: center; padding-top: 50px; }
        .login-box { background: white; width: 300px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input { width: 90%; margin-bottom: 10px; padding: 10px; }
        button { background: #2c3e50; color: white; padding: 10px; width: 100%; border: none; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>نظام الضرائب - تسجيل الدخول</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">دخول</button>
        </form>
    </div>
</body>
</html>

