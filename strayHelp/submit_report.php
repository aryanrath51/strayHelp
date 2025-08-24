<?php
// Database connection
$host = "localhost"; 
$user = "root";      // default XAMPP user
$pass = "";          // default password is blank
$db   = "animal_rescue";   // ✅ use your DB

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

$timestamp = $data["timestamp"];
$latitude  = $data["location"]["lat"];
$longitude = $data["location"]["lng"];
$details   = $conn->real_escape_string($data["details"]);
$status    = "submitted";

// Handle photo
$photoPath = null;
if (!empty($data["photo"])) {
    $imgData = $data["photo"];
    if (strpos($imgData, "base64,") !== false) {
        $imgData = explode("base64,", $imgData)[1];
    }
    $imgData = base64_decode($imgData);
    $fileName = "uploads/report_" . time() . ".png";

    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    if (file_put_contents($fileName, $imgData)) {
        $photoPath = $conn->real_escape_string($fileName);
    }
}

$sql = "INSERT INTO reports (timestamp, latitude, longitude, photo_path, details, status) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sddsss", $timestamp, $latitude, $longitude, $photoPath, $details, $status);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Report submitted successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>