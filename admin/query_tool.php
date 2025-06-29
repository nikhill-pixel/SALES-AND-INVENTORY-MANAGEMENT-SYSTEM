<?php
include '../includes/db.php';
include '../includes/auth.php';
checkAccess("Admin");

$output = null;
$error = null;
$query = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = trim($_POST['sql_query']);

    if (!empty($query)) {
        $result = mysqli_query($conn, $query);

        if ($result === true) {
            // For queries like INSERT, UPDATE, DELETE
            $output = "✅ Query executed successfully. Rows affected: " . mysqli_affected_rows($conn);
        } elseif ($result && is_object($result)) {
            // SELECT queries: show result set
            $output = '<table><tr>';
            while ($field = mysqli_fetch_field($result)) {
                $output .= '<th>' . htmlspecialchars($field->name) . '</th>';
            }
            $output .= '</tr>';

            while ($row = mysqli_fetch_assoc($result)) {
                $output .= '<tr>';
                foreach ($row as $cell) {
                    $output .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $output .= '</tr>';
            }
            $output .= '</table>';
        } else {
            $error = "❌ Error: " . mysqli_error($conn);
        }
    } else {
        $error = "⚠️ Please enter a query.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin SQL Query Tool</title>
    <link rel="stylesheet" href="../assets/forms.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        textarea { width: 100%; }
        .result, .error { margin-top: 1em; padding: 10px; border-radius: 5px; }
        .result { background: #e6ffe6; color: #006600; }
        .error { background: #ffe6e6; color: #990000; }
    </style>
</head>
<body>
<div class="container">
    <h2>🛠️ SQL Query Tool (Admin)</h2>

    <form method="POST">
        <label>Enter SQL Query:</label>
        <textarea name="sql_query" rows="6" required><?= htmlspecialchars($query) ?></textarea>
        <button type="submit">Run Query</button>
    </form>

    <?php if ($output): ?>
        <div class="result"><?= $output ?></div>
    <?php elseif ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <a href="../dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
