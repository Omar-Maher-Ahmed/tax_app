<?php
session_start();
require 'db.php';

// التحقق من الصلاحيات
if (!isset($_COOKIE['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_COOKIE['role'];

// --- معالجة الطلبات (Actions) ---
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. معالجة زر "تصفير الكل" (منطقة الخطر)
    if (isset($_POST['nuke_all'])) {
        if ($role === 'admin') {
            $stmt = $pdo->prepare("UPDATE tax_returns SET tax_amount = 0");
            $stmt->execute();
            $message = "⚠️ تم تنفيذ الهجوم الشامل! تم تصفير ضرائب جميع المواطنين.";
        } else {
            $message = "⛔ ليس لديك صلاحية!";
        }
    }

    // 2. معالجة زر "تعديل قيمة ضريبة" (تغيير المبلغ)
    if (isset($_POST['modify_tax'])) {
        if ($role === 'admin') {
            $target_id = $_POST['target_id'];
            $new_amount = $_POST['new_amount'];

            $stmt = $pdo->prepare("UPDATE tax_returns SET tax_amount = :amount WHERE citizen_id = :id");
            $stmt->execute(['amount' => $new_amount, 'id' => $target_id]);
            
            $message = "✅ تم تحديث ضريبة المواطن رقم ($target_id) لتصبح $new_amount";
        } else {
            $message = "⛔ ليس لديك صلاحية!";
        }
    }

    // 3. (الجديد) معالجة زر "إعفاء ضريبي" (تصفير لمواطن واحد)
    if (isset($_POST['waiver_tax'])) {
        if ($role === 'admin') {
            $target_id = $_POST['waiver_id'];

            // تصفير الضريبة لهذا المواطن فقط
            $stmt = $pdo->prepare("UPDATE tax_returns SET tax_amount = 0 WHERE citizen_id = :id");
            $stmt->execute(['id' => $target_id]);
            
            $message = "🎉 تم إلغاء الضريبة تماماً عن المواطن رقم ($target_id)";
        } else {
            $message = "⛔ ليس لديك صلاحية!";
        }
    }
}

// جلب البيانات للعرض
$stmtReturns = $pdo->query("SELECT tr.*, c.full_name FROM tax_returns tr JOIN citizens c ON tr.citizen_id = c.citizen_id");
$returns = $stmtReturns->fetchAll();

$stmtLogs = $pdo->query("SELECT * FROM audit_log ORDER BY log_id DESC LIMIT 50"); // نعرض آخر 50 عملية بس عشان السرعة
$logs = $stmtLogs->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم المتقدمة</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma; padding: 20px; background-color: #f4f4f9; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: white; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: right; }
        th { background-color: #2c3e50; color: white; }
        .role-badge { background: <?php echo $role == 'admin' ? 'red' : 'green'; ?>; color: white; padding: 5px 10px; border-radius: 15px; font-size: small;}
        
        /* تنسيق لوحة الأدمن */
        .admin-panel { background: #fff5f5; border: 2px solid #e74c3c; padding: 15px; margin-bottom: 20px; border-radius: 10px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .control-group { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; min-width: 250px; text-align: center; }
        
        input { padding: 8px; margin: 5px; width: 70%; border: 1px solid #ccc; border-radius: 4px; }
        button { cursor: pointer; padding: 8px 15px; border: none; border-radius: 5px; color: white; font-weight: bold; width: 80%; margin-top: 5px;}
        
        .btn-update { background-color: #2980b9; } /* أزرق */
        .btn-waiver { background-color: #27ae60; } /* أخضر */
        .btn-nuke { background-color: #c0392b; }   /* أحمر */
        
        .alert { padding: 10px; background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; margin-bottom: 15px; text-align: center; font-weight: bold;}
    </style>
</head>
<body>
    
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>نظام إدارة الضرائب (Admin C2 Panel)</h1>
        <div>
            مرحباً، <span class="role-badge"><?php echo $role; ?></span>
            <a href="logout.php" style="margin-right: 10px; color: red;">تسجيل خروج</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div class="admin-panel">
        
        <div class="control-group">
            <h3>🛠️ تعديل ضريبة</h3>
            <form method="POST">
                <input type="number" name="target_id" placeholder="ID المواطن" required>
                <input type="number" name="new_amount" placeholder="المبلغ الجديد" required>
                <button type="submit" name="modify_tax" class="btn-update">تحديث</button>
            </form>
        </div>

        <div class="control-group" style="border-top: 3px solid #27ae60;">
            <h3>✨ إعفاء ضريبي (إلغاء)</h3>
            <p style="font-size: 0.9em; color: #555;">تصفير الضريبة لمواطن محدد</p>
            <form method="POST">
                <input type="number" name="waiver_id" placeholder="ID المواطن" required>
                <button type="submit" name="waiver_tax" class="btn-waiver">✅ إلغاء الضريبة</button>
            </form>
        </div>

        <div class="control-group" style="border: 1px dashed red; background-color: #fff0f0;">
            <h3>☢️ منطقة الخطر</h3>
            <p style="font-size: 0.9em; color: red;">تصفير قاعدة البيانات بالكامل</p>
            <form method="POST">
                <button type="submit" name="nuke_all" class="btn-nuke">💣 تصفير الكل</button>
            </form>
        </div>

    </div>
    <?php endif; ?>

    <h2>1. سجلات المواطنين (Total: <?php echo count($returns); ?>)</h2>
    <div style="max-height: 400px; overflow-y: scroll; border: 1px solid #ddd;">
        <table>
            <thead>
                <tr><th>ID</th><th>الاسم</th><th>السنة</th><th>الدخل</th><th>الضريبة</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $row): ?>
                <tr>
                    <td><?php echo $row['citizen_id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['tax_year']; ?></td>
                    <td><?php echo number_format($row['declared_income']); ?></td>
                    <td style="font-weight:bold; color: <?php echo $row['tax_amount'] == 0 ? 'red' : 'green'; ?>">
                        <?php echo number_format($row['tax_amount']); ?>
                    </td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>2. سجل التدقيق (Live Logs)</h2>
    <table>
        <thead><tr><th>ID</th><th>العملية</th><th>المسؤول</th><th>قبل</th><th>بعد</th><th>الوقت</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo $log['log_id']; ?></td>
                <td><?php echo $log['operation']; ?></td>
                <td><?php echo $log['changed_by']; ?></td>
                <td><?php echo $log['old_data']; ?></td>
                <td><?php echo $log['new_data']; ?></td>
                <td><?php echo $log['change_time']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
