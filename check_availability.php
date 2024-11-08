<?php
require("includes/db.inc.php");
require("includes/config.inc.php");

$conn = dbConnect();


if (isset($_GET['date']) && isset($_GET['time'])) {
    $res_date = $_GET['date'];
    $res_time = $_GET['time'];

    $sql = "SELECT table_number 
        FROM tbl_reservations 
        WHERE res_date = ? 
        AND res_time = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $res_date, $res_time);
    $stmt->execute();
    $result = $stmt->get_result();

    $reserved_tables = [];
    while ($row = $result->fetch_assoc()) {
        $reserved_tables[] = $row['table_number'];
    }

    echo json_encode($reserved_tables);
}
$stmt->close();
$conn->close();
?>