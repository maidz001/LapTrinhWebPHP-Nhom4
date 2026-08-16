<?php
session_start();

// Tạo dữ liệu mẫu
if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [
        [
            'id' => 1,
            'name' => 'Nguyễn Văn An',
            'room' => 'A101',
            'time' => '08:00 - 10:00',
            'status' => 'Chờ duyệt'
        ],
        [
            'id' => 2,
            'name' => 'Trần Thị Bình',
            'room' => 'A102',
            'time' => '13:00 - 15:00',
            'status' => 'Chờ duyệt'
        ],
        [
            'id' => 3,
            'name' => 'Lê Minh Cường',
            'room' => 'B201',
            'time' => '09:00 - 11:00',
            'status' => 'Đã duyệt'
        ]
    ];
}

// Xử lý khi bấm nút
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    foreach ($_SESSION['requests'] as &$request) {
        if (
            $request['id'] === $id
            && $request['status'] === 'Chờ duyệt'
        ) {
            if ($action === 'approve') {
                $request['status'] = 'Đã duyệt';
            }

            if ($action === 'reject') {
                $request['status'] = 'Từ chối';
            }
        }
    }

    unset($request);

    header('Location: manage.php');
    exit;
}

$requests = $_SESSION['requests'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý booking</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        h1 {
            margin: 0;
            padding: 20px;
            color: white;
            background: #1769aa;
            text-align: center;
        }

        table {
            width: 85%;
            margin: 30px auto;
            background: white;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #cccccc;
            text-align: center;
        }

        th {
            background: #dce8f2;
        }

        button {
            padding: 7px 10px;
            border: none;
            color: white;
            cursor: pointer;
        }

        .approve {
            background: green;
        }

        .reject {
            background: #c0392b;
        }

        .done {
            color: #555555;
        }
    </style>
</head>

<body>

<h1>QUẢN LÝ YÊU CẦU ĐẶT PHÒNG</h1>

<table>
    <tr>
        <th>Mã</th>
        <th>Người gửi</th>
        <th>Phòng</th>
        <th>Thời gian</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
    </tr>

    <?php foreach ($requests as $request) { ?>

        <tr>
            <td><?= $request['id'] ?></td>
            <td><?= $request['name'] ?></td>
            <td><?= $request['room'] ?></td>
            <td><?= $request['time'] ?></td>
            <td><?= $request['status'] ?></td>
            <td>
                            <?php if ($request['status'] === 'Chờ duyệt') { ?>

                                <form method="post" style="display: inline;">
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $request['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >

                                    <button class="approve">
                                        Duyệt
                                    </button>
                                </form>

                                <form method="post" style="display: inline;">
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $request['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >

                                    <button class="reject">
                                        Từ chối
                                    </button>
                                </form>

                            <?php } ?>

                            <?php if ($request['status'] !== 'Chờ duyệt') { ?>

                                <span class="done">
                                    Đã xử lý
                                </span>

                            <?php } ?>
                        </td>
                    </tr>

                <?php } ?>

            </table>

            </body>
            </html>