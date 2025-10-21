<?php
session_start();

// เริ่มต้นค่าขั้นตอน
if (!isset($_SESSION['step'])) $_SESSION['step'] = 1;

// เมื่อกดปุ่ม “ถัดไป” หรือ “กลับ”
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        // เก็บข้อมูลตามขั้นตอน
        if ($_SESSION['step'] == 1) {
            $_SESSION['name'] = $_POST['name'];
            $_SESSION['phone'] = $_POST['phone'];
            $_SESSION['step'] = 2;
        } elseif ($_SESSION['step'] == 2) {
            $_SESSION['loan_amount'] = floatval($_POST['loan_amount']);
            $_SESSION['interest_rate'] = floatval($_POST['interest_rate']);
            $_SESSION['term_months'] = intval($_POST['term_months']);
            $_SESSION['step'] = 3;
        }
    } elseif (isset($_POST['back'])) {
        $_SESSION['step'] = max(1, $_SESSION['step'] - 1);
    } elseif (isset($_POST['reset'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ฟังก์ชันคำนวณค่างวด (สูตรสินเชื่อ)
function calculateMonthlyPayment($loan_amount, $interest_rate, $term_months) {
    $monthly_rate = $interest_rate / 100 / 12;
    if ($monthly_rate == 0) return $loan_amount / $term_months;
    $payment = $loan_amount * $monthly_rate / (1 - pow(1 + $monthly_rate, -$term_months));
    return $payment;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบฟอร์มสินเชื่อหลายขั้นตอน (PHP)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-lg rounded-4">
        <div class="card-body p-5">

            <h3 class="text-center mb-4">📋 แบบฟอร์มคำนวณสินเชื่อ</h3>

            <!-- Progress bar -->
            <div class="progress mb-4" style="height: 25px;">
                <div class="progress-bar progress-bar-striped bg-success"
                     role="progressbar"
                     style="width: <?= $_SESSION['step'] * 33 ?>%">
                    ขั้นตอน <?= $_SESSION['step'] ?> / 3
                </div>
            </div>

            <form method="POST" class="mt-4">

                <?php if ($_SESSION['step'] == 1): ?>
                    <h5>ขั้นตอนที่ 1: ข้อมูลลูกค้า</h5>
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= $_SESSION['name'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" class="form-control" required
                               value="<?= $_SESSION['phone'] ?? '' ?>">
                    </div>
                    <button type="submit" name="next" class="btn btn-primary">ถัดไป ➡️</button>

                <?php elseif ($_SESSION['step'] == 2): ?>
                    <h5>ขั้นตอนที่ 2: ข้อมูลสินเชื่อ</h5>
                    <div class="mb-3">
                        <label class="form-label">ยอดกู้ (บาท)</label>
                        <input type="number" step="0.01" name="loan_amount" class="form-control" required
                               value="<?= $_SESSION['loan_amount'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อัตราดอกเบี้ยต่อปี (%)</label>
                        <input type="number" step="0.01" name="interest_rate" class="form-control" required
                               value="<?= $_SESSION['interest_rate'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ระยะเวลาผ่อน (เดือน)</label>
                        <input type="number" name="term_months" class="form-control" required
                               value="<?= $_SESSION['term_months'] ?? '' ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="back" class="btn btn-secondary">⬅️ ย้อนกลับ</button>
                        <button type="submit" name="next" class="btn btn-primary">ถัดไป ➡️</button>
                    </div>

                <?php elseif ($_SESSION['step'] == 3): ?>
                    <h5>ขั้นตอนที่ 3: ผลการคำนวณ</h5>

                    <?php
                    $monthly_payment = calculateMonthlyPayment(
                        $_SESSION['loan_amount'],
                        $_SESSION['interest_rate'],
                        $_SESSION['term_months']
                    );
                    $total_payment = $monthly_payment * $_SESSION['term_months'];
                    $total_interest = $total_payment - $_SESSION['loan_amount'];
                    ?>

                    <div class="alert alert-success">
                        <strong>ชื่อ:</strong> <?= htmlspecialchars($_SESSION['name']) ?><br>
                        <strong>เบอร์โทร:</strong> <?= htmlspecialchars($_SESSION['phone']) ?><br><br>
                        💰 <strong>ยอดกู้:</strong> <?= number_format($_SESSION['loan_amount'], 2) ?> บาท<br>
                        💹 <strong>ดอกเบี้ยต่อปี:</strong> <?= number_format($_SESSION['interest_rate'], 2) ?> %<br>
                        ⏳ <strong>ระยะเวลาผ่อน:</strong> <?= $_SESSION['term_months'] ?> เดือน<br><br>
                        📆 <strong>ค่างวดต่อเดือน:</strong> <?= number_format($monthly_payment, 2) ?> บาท<br>
                        💸 <strong>ดอกเบี้ยรวม:</strong> <?= number_format($total_interest, 2) ?> บาท<br>
                        💰 <strong>ยอดชำระทั้งหมด:</strong> <?= number_format($total_payment, 2) ?> บาท
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" name="back" class="btn btn-secondary">⬅️ ย้อนกลับ</button>
                        <button type="submit" name="reset" class="btn btn-danger">เริ่มใหม่ 🔄</button>
                    </div>
                <?php endif; ?>

            </form>
        </div>
    </div>
</div>

</body>
</html>
