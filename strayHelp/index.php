<?php
// Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $host = "localhost";
    $user = "root";   // default for XAMPP
    $pass = "";       // default no password
    $db   = "animal_rescue";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    $timestamp = date("Y-m-d H:i:s");
    $latitude  = $_POST["latitude"] ?? null;
    $longitude = $_POST["longitude"] ?? null;
    $details   = $_POST["details"] ?? "";
    $photoPath = null;

    // Handle photo upload
    if (!empty($_FILES["photo"]["name"])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = $uploadDir . "report_" . time() . ".png";
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $fileName)) {
            $photoPath = $fileName;
        } else {
            $message = "<p class='error'>⚠ Photo upload failed!</p>";
        }
    }

    $sql = "INSERT INTO reports (timestamp, latitude, longitude, photo_path, details, status) 
            VALUES (?, ?, ?, ?, ?, 'submitted')";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sddss", $timestamp, $latitude, $longitude, $photoPath, $details);

        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Report submitted successfully!</p>";
        } else {
            $message = "<p class='error'>❌ Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } else {
        $message = "<p class='error'>❌ Prepare failed: " . $conn->error . "</p>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Animal Rescue Reporter</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f9fc;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 400px;
      margin: 40px auto;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 10px;
      color: #444;
    }
    input, textarea, button {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    button {
      background: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
      margin-top: 15px;
    }
    button:hover {
      background: #45a049;
    }
    .loc-btn {
      background: #2196F3;
      margin-top: 8px;
    }
    .loc-btn:hover {
      background: #1976D2;
    }
    .success {
      background: #e6ffed;
      color: #2e7d32;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    .error {
      background: #ffebee;
      color: #c62828;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🐾 Report a Stray Animal</h2>
    <?= $message ?>

    <form action="index.php" method="POST" enctype="multipart/form-data">
      <label>Latitude:</label>
      <input type="text" id="latitude" name="latitude" required>

      <label>Longitude:</label>
      <input type="text" id="longitude" name="longitude" required>

      <button type="button" class="loc-btn" onclick="getLocation()">📍 Detect My Location</button>

      <label>Details:</label>
      <textarea name="details" rows="3"></textarea>

      <label>Upload / Take Photo:</label>
      <!-- Camera or Gallery -->
      <input type="file" name="photo" accept="image/*" capture="environment">

      <button type="submit">📩 Submit Report</button>
    </form>
  </div>

  <script>
    function getLocation() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
          document.getElementById("latitude").value = position.coords.latitude;
          document.getElementById("longitude").value = position.coords.longitude;
        }, function(error) {
          alert("⚠ Unable to fetch location. Please allow location access.");
        });
      } else {
        alert("❌ Geolocation is not supported by this browser.");
      }
    }
  </script>
</body>
</html>