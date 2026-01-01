<?php
session_start();
include 'db.php';

// لو مفيش حد عامل دخول، رجعه لصفحة الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// هات بيانات الضرائب للشخص ده بس
$sql = "SELECT * FROM tax_returns WHERE citizen_id = $user_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html dir="rtl">
<head>
    <title>بياناتي الضريبية</title>
    <style>
        table { width: 80%; margin: 20px auto; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <center>
        <h1>أهلاً بك يا، <?php echo $user_name; ?></h1>
        <h3>إقراراتك الضريبية المسجلة:</h3>
        
        <table>
            <tr>
                <th>السنة المالية</th>
                <th>الدخل المصرح به</th>
                <th>الضريبة المستحقة</th>
                <th>الحالة</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['fiscal_year'] . "</td>";
                    echo "<td>" . $row['declared_income'] . " جنية</td>";
                    echo "<td>" . $row['tax_owed'] . " جنية</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>لا توجد إقرارات مسجلة</td></tr>";
            }
            ?>
        </table>
        
        <br>
        <a href="logout.php">تسجيل خروج</a>
    </center>
</body>
</html>

