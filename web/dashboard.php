<?php
session_start();
include("connection.php"); // เชื่อมต่อฐานข้อมูล
include("functions.php"); // เชื่อมต่อฐานข้อมูล

$users = check_login($con);
$user_id = $_SESSION['user_id'];  // ดึง user_id จาก session

// ฟังก์ชันสำหรับคำนวณยอดรวม
function get_total_amount($type, $user_id)
{
    global $con;
    $query = "SELECT SUM(amount) AS total FROM transactions WHERE type = ? AND user_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("si", $type, $user_id);  // binding user_id เพื่อให้แน่ใจว่าเลือกเฉพาะข้อมูลของ user นี้
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ? $row['total'] : 0;
}

// ฟังก์ชันดึงยอดรวม
if (isset($_GET['action']) && $_GET['action'] == "get_totals") {
    $income_total = get_total_amount('income', $user_id);
    $expense_total = get_total_amount('expense', $user_id);
    $net_total = $income_total - $expense_total;
    $response = [
        'income_total' => $income_total,
        'expense_total' => $expense_total,
        'net_total' => $net_total
    ];
    echo json_encode($response);
    exit;
}

// ฟังก์ชันดึงข้อมูลรายรับและรายจ่ายรวมตามวัน
if (isset($_GET['action']) && $_GET['action'] == "fetch_daily_totals") {
    // ดึงข้อมูลรายการทั้งหมด
    $query = "SELECT DATE(date) as transaction_date, type, SUM(amount) as total_amount 
              FROM transactions 
              WHERE user_id = ? 
              GROUP BY DATE(date), type 
              ORDER BY transaction_date ASC";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $user_id); // ใช้ user_id สำหรับจำกัดข้อมูลของผู้ใช้
    $stmt->execute();
    $result = $stmt->get_result();

    $daily_data = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['transaction_date'];
        $type = $row['type'];
        $total_amount = $row['total_amount'];

        if (!isset($daily_data[$date])) {
            $daily_data[$date] = ['income' => 0, 'expense' => 0];
        }

        if ($type == 'income') {
            $daily_data[$date]['income'] = $total_amount;
        } else {
            $daily_data[$date]['expense'] = $total_amount;
        }
    }

    // ส่งข้อมูลกลับไปยังหน้าเว็บ
    echo json_encode($daily_data);
    exit;
}



// คำนวณยอดรวมรายรับและรายจ่าย
$income_total = get_total_amount('income', $user_id);
$expense_total = get_total_amount('expense', $user_id);
$net_total = $income_total - $expense_total;

// คำสั่งสำหรับการเพิ่มรายการ
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    if (!empty($type) && !empty($amount) && !empty($description) && !empty($date)) {
        $query = "INSERT INTO transactions (type, amount, description, date, user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sdssi", $type, $amount, $description, $date, $user_id);  // ใช้ user_id ในการเก็บข้อมูล
        $stmt->execute();
    }
    exit;
}

// ฟังก์ชันดึงข้อมูลรายการ
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC";  // กรองข้อมูลตาม user_id
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $user_id);  // ใช้ user_id ในการค้นหาข้อมูล
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($transactions);
    exit;
}

// ฟังก์ชันลบข้อมูลรายการ
if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $id = $_DELETE['id'];
    $query = "DELETE FROM transactions WHERE id = ? AND user_id = ?";  // ตรวจสอบ user_id ก่อนลบ
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);  // ใช้ user_id ในการลบข้อมูล
    $stmt->execute();
    exit;
}

// ฟังก์ชันอัพเดตข้อมูลรายการ
if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    parse_str(file_get_contents("php://input"), $_PUT);
    $id = $_PUT['id'];
    $type = $_PUT['type'];
    $amount = $_PUT['amount'];
    $description = $_PUT['description'];
    $date = $_PUT['date'];

    $query = "UPDATE transactions SET type=?, amount=?, description=?, date=? WHERE id=? AND user_id=?";  // ตรวจสอบ user_id ก่อนอัพเดต
    $stmt = $con->prepare($query);
    $stmt->bind_param("sdssii", $type, $amount, $description, $date, $id, $user_id);  // ใช้ user_id ในการอัพเดตข้อมูล
    $stmt->execute();
    exit;
}
?>



<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard รายรับ-รายจ่าย</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        * {
            font-family: "Instrument Sans", serif;
            font-optical-sizing: auto;
            font-weight: weight;
            font-style: normal;
            font-variation-settings: "wdth" 100;
        }

        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }

        .p-in {
            font-size: 30px;
            color: rgb(3, 179, 44);
        }

        .p-out {
            font-size: 30px;
            color: rgb(226, 3, 25);
        }

        h3 {
            font-size: 40px;
        }

        .container {
            width: 50%;
            margin: auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f4a261;
            color: white;
        }

        form select,
        form input[type="number"],
        form input[type="text"],
        form input[type="date"],
        form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 20px;
        }

        /* เพิ่มสีตรงช่องเลือกรายรับรายจ่าย */
        form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #f1f1f1;
            font-size: 20px;
        }

        form select option[value="income"] {
            background-color: #d4edda;
            /* สีพื้นหลังสำหรับรายรับ */
            color: #155724;
            /* สีตัวอักษรสำหรับรายรับ */
        }

        form select option[value="expense"] {
            background-color: #f8d7da;
            /* สีพื้นหลังสำหรับรายจ่าย */
            color: #721c24;
            /* สีตัวอักษรสำหรับรายจ่าย */
        }

        form select:focus {
            outline: none;
            border-color: #3498db;
            /* สีขอบเมื่อมีการโฟกัส */
            background-color: #ffffff;
            /* เปลี่ยนสีพื้นหลังเมื่อโฟกัส */
        }

        .income {
            color: green;
            font-weight: bold;
        }

        .expense {
            color: red;
            font-weight: bold;
        }

        .btn {
            padding: 5px 10px;
            margin: 2px;
            cursor: pointer;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        .text-p {
            font-size: 20px;
        }

        /* ปรับตำแหน่งปุ่มให้อยู่ที่มุมขวาล่างและให้ลอย */
        .logout-btn {
            position: fixed;
            bottom: 20px;
            /* ระยะห่างจากด้านล่าง */
            right: 20px;
            /* ระยะห่างจากด้านขวา */
            padding: 12px 20px;
            background-color: rgb(247, 133, 41);
            color: white;
            font-size: 24px;
            border: 5px;
            border-radius: 60px;
            /* ทำให้ปุ่มเป็นวงกลม */
            cursor: pointer;
        }

        .transactions-table {
            border-radius: 30px;
        }

        th,
        td {
            border: 2px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 1px;
            /* เพิ่ม border-radius ให้กับเซลล์ */
            border-color: black;
            /* เพิ่มสีขอบเป็นสีขาว */
        }

        table {
            width: 100%;
            border-collapse: separate;
            /* ใช้ separate เพื่อให้ border-radius ทำงาน */
            margin-top: 20px;
            border-radius: 10px;
            /* เพิ่ม border-radius ที่ตัวตาราง */
            overflow: hidden;
            /* ป้องกันไม่ให้มุมเหลี่ยมถูกตัด */
            border-color: black;
            /* เพิ่มสีขอบเป็นสีขาว */
        }

        .show-graph-btn {
            border-radius: 10px;
            background-color: #f4a261;
            border-color: #f4a261;
            color: white; /* Set color of font */
            padding: 5px;;
            font-size: 30px;
            display: block;
            margin: 20px auto; /* Center the button */
        }
    </style>
</head>

<body>

    <img src="image/LogoTextWeb.png" alt="Main Logo"
        style="width: 700px; height: auto; display: block; margin:  auto auto -50px 630px">

    <!-- ปุ่มสำหรับ Logout -->
    <div>
        <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
    </div>


    <div class="container">



        <!-- เพิ่มฟิลด์สำหรับยอดรวม -->
        <div class="total">
            <p class="p-in">รายรับรวม: <?= number_format($income_total, 2) ?> บาท</p>
            <p class="p-out">รายจ่ายรวม: <?= number_format($expense_total, 2) ?> บาท</p>
            <h3>ยอดสุทธิ: <?= number_format($income_total - $expense_total, 2) ?> บาท</h3>
        </div>


       
        <!-- ปุ่มแสดงกราฟ -->
        <div>
            <button id="show-graph-btn" class="show-graph-btn">แสดงกราฟ</button>
        </div>
 <!-- พื้นที่สำหรับแสดงกราฟ -->
        <div>
            <canvas id="transaction-chart" width="30px" height="10px"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



        <form id="transaction-form">
            <select id="type">
                <option value="income">รายรับ</option>
                <option value="expense">รายจ่าย</option>
            </select>
            <input type="number" id="amount" placeholder="จำนวนเงิน" required>
            <input type="text" id="description" placeholder="รายละเอียด" required>
            <input type="date" id="date" required>
            <button type="submit">เพิ่มรายการ</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ประเภท</th>
                    <th>จำนวน</th>
                    <th>รายละเอียด</th>
                    <th>วันที่</th>
                    <th>แก้ไข</th>
                    <th>ลบ</th>
                </tr>
            </thead>
            <tbody id="transactions-list"></tbody>
        </table>
    </div>



    <script>
        // ฟังก์ชันเพื่อดึงข้อมูลรายการและยอดรวม
        function fetchTransactions() {
            $.get("dashboard.php?action=fetch", function (data) {
                let transactions = JSON.parse(data);
                let html = "";
                let incomeTotal = 0;
                let expenseTotal = 0;

                transactions.forEach(t => {
                    html += `<tr data-id="${t.id}">
                <td class="${t.type}">${t.type == "income" ? "📈 รายรับ" : "📉 รายจ่าย"}</td>
                <td>${t.amount}</td>
                <td>${t.description}</td>
                <td>${t.date}</td>
                <td><button class="btn edit">✏️</button></td>
                <td><button class="btn delete">❌</button></td>
             </tr>`;

                    if (t.type === "income") {
                        incomeTotal += parseFloat(t.amount);
                    } else {
                        expenseTotal += parseFloat(t.amount);
                    }
                });

                $("#transactions-list").html(html);

                // อัพเดตยอดรวม
                updateTotals(incomeTotal, expenseTotal);
            });
        }

        // ฟังก์ชันเพื่อดึงยอดรวมและอัพเดตในหน้าจอ
        function updateTotals(incomeTotal, expenseTotal) {
            $("#total-income").text(incomeTotal.toFixed(2) + " บาท");
            $("#total-expense").text(expenseTotal.toFixed(2) + " บาท");
            $("#net-total").text((incomeTotal - expenseTotal).toFixed(2) + " บาท");
        }

        $("#transaction-form").submit(function (e) {
            e.preventDefault();
            let data = {
                type: $("#type").val(),
                amount: $("#amount").val(),
                description: $("#description").val(),
                date: $("#date").val()
            };
            $.post("dashboard.php", data, function () {
                fetchTransactions(); // เรียกใช้ฟังก์ชันเพื่อดึงข้อมูลใหม่และอัพเดตยอดรวม
            });
            this.reset();
        });

        $(document).on("click", ".delete", function () {
            let id = $(this).closest("tr").data("id");
            $.ajax({
                url: "dashboard.php",
                type: "DELETE",
                data: { id: id },
                success: fetchTransactions
            });
        });

        $(document).on("click", ".edit", function () {
            let row = $(this).closest("tr");
            let id = row.data("id");
            let type = row.find("td:eq(0)").hasClass("income") ? "income" : "expense";
            let amount = row.find("td:eq(1)").text();
            let description = row.find("td:eq(2)").text();
            let date = row.find("td:eq(3)").text();

            let newType = prompt("เลือกประเภท (income/expense):", type);
            let newAmount = prompt("แก้ไขจำนวนเงิน:", amount);
            let newDescription = prompt("แก้ไขรายละเอียด:", description);
            let newDate = prompt("แก้ไขวันที่:", date);

            if (newType && newAmount && newDescription && newDate) {
                $.ajax({
                    url: "dashboard.php",
                    type: "PUT",
                    data: { id, type: newType, amount: newAmount, description: newDescription, date: newDate },
                    success: fetchTransactions
                });
            }
        });

        // ฟังก์ชันเพื่อดึงยอดรวมทันที
        function fetchTotals() {
            $.get("dashboard.php?action=get_totals", function (data) {
                let totals = JSON.parse(data);
                updateTotals(totals.income_total, totals.expense_total);
            });
        }

        // ฟังก์ชันเพื่อดึงข้อมูลและแสดงกราฟ
        $("#show-graph-btn").click(function () {
            $.get("dashboard.php?action=fetch_daily_totals", function (data) {
                let dailyData = JSON.parse(data);

                let incomeData = [];
                let expenseData = [];
                let totalData = [];
                let dates = [];

                // ดึงข้อมูลวันและยอดรวม
                for (let date in dailyData) {
                    dates.push(date);
                    incomeData.push(dailyData[date].income || 0); // กรณีไม่มีรายรับให้ใช้ค่า 0
                    expenseData.push(dailyData[date].expense || 0); // กรณีไม่มีรายจ่ายให้ใช้ค่า 0
                    totalData.push((dailyData[date].income || 0) - (dailyData[date].expense || 0)); // คำนวณยอดรวม
                }

                // สร้างกราฟ
                var ctx = document.getElementById("transaction-chart").getContext("2d");
                var chart = new Chart(ctx, {
                    type: 'line', // ชนิดของกราฟ
                    data: {
                        labels: dates, // ใช้วันที่เป็น label
                        datasets: [{
                            label: "รายรับ",
                            data: incomeData, // ข้อมูลรายรับ
                            borderColor: "rgb(0, 204, 85)",
                            borderDash: [5, 5], // เส้นประ
                            fill: false
                        },
                        {
                            label: "รายจ่าย",
                            data: expenseData, // ข้อมูลรายจ่าย
                            borderColor: "rgb(255, 0, 0)",
                            borderDash: [5, 5], // เส้นประ
                            fill: false
                        },
                        {
                            label: "ยอดรวม",
                            data: totalData, // ข้อมูลยอดรวม
                            borderColor: "rgb(136, 76, 255)",
                            fill: false
                        }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                type: 'category',
                                labels: dates, // ใช้วันที่เป็น label
                                title: {
                                    display: true,
                                    text: 'วันที่',
                                    font: {
                                        size: 20 // กำหนดขนาดตัวอักษร
                                    }
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'จำนวนเงิน (บาท)',
                                    font: {
                                        size: 20 // กำหนดขนาดตัวอักษร
                                    }
                                }
                            }
                        }
                    }
                });
            });
        });


        // เรียกใช้ฟังก์ชันเพื่อแสดงยอดรวมเมื่อโหลดหน้า
        fetchTransactions();
        fetchTotals();

    </script>



</body>

</html>