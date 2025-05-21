<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Example</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            text-align: center;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1 class="text-center">Course Details Table</h1>
    <table>
        <thead>
            <tr>
                <th>ที่</th>
                <th>รหัสวิชา</th>
                <th>ชื่อรายวิชา</th>
                <th colspan="4">จำนวน</th>
                <th>ชื่อครูประจำรายวิชา<br>(ให้เขียนตัวบรรจง)</th>
                <th>ลงชื่อครูประจำรายวิชา</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th>ทฤษฎี</th>
                <th>ปฏิบัติ</th>
                <th>หน่วยกิต</th>
                <th>ชั่วโมง</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <!-- Repeat this row for each subject -->
            <tr>
                <td>1</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>2</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <!-- Add more rows as needed -->
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">รวม</td>
                <td>........วิชา</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
