<?php
// Connection l database
$conn = mysqli_connect("localhost", "root", "", "exam_system");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $role = $_POST['role'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $firstName . ' ' . $lastName;

    // Insert into database
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $role);

    if (mysqli_stmt_execute($stmt)) {
        // Get updated count based on role
        $count_sql = "SELECT COUNT(*) as count FROM users WHERE role = ?";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        mysqli_stmt_bind_param($count_stmt, "s", $role);
        mysqli_stmt_execute($count_stmt);
        $result = mysqli_stmt_get_result($count_stmt);
        $count = mysqli_fetch_assoc($result)['count'];

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'User added successfully',
            'count' => $count,
            'role' => $role
        ]);
    } else {
        // Return error response
        echo json_encode([
            'status' => 'error',
            'message' => 'Error adding user: ' . mysqli_error($conn)
        ]);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>