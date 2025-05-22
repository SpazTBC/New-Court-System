<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("../include/database.php");

echo "<h1>Case System Debug Page</h1>";

// 1. Check if user is logged in
echo "<h2>1. Session Information</h2>";
echo "Username in session: " . (isset($_SESSION['username']) ? $_SESSION['username'] : "Not set") . "<br>";

// 2. Check user information
echo "<h2>2. User Information</h2>";
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found in database:<br>";
        echo "Username: " . htmlspecialchars($user['username']) . "<br>";
        echo "Job: " . htmlspecialchars($user['job']) . "<br>";
    } else {
        echo "No user found with username: " . htmlspecialchars($_SESSION['username']) . "<br>";
    }
}

// 3. Check if cases table exists and its structure
echo "<h2>3. Cases Table Structure</h2>";
try {
    $stmt = $conn->query("DESCRIBE cases");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage() . "<br>";
}

// 4. Check total number of cases
echo "<h2>4. Total Cases in Database</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM cases");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total cases: " . $total . "<br>";
    
    if ($total > 0) {
        // Show first 5 cases
        $stmt = $conn->query("SELECT * FROM cases LIMIT 5");
        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Cases (First 5):</h3>";
        echo "<table border='1'><tr>";
        // Headers
        foreach (array_keys($cases[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($cases as $case) {
            echo "<tr>";
            foreach ($case as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "Error checking cases: " . $e->getMessage() . "<br>";
}

// 5. Check cases for current user
echo "<h2>5. Cases for Current User</h2>";
if (isset($_SESSION['username'])) {
    try {
        $username = $_SESSION['username'];
        
        // Try different queries to find cases for this user
        $queries = [
            "Simple query" => "SELECT COUNT(*) as count FROM cases WHERE assigneduser = :username",
            "With shared fields" => "SELECT COUNT(*) as count FROM cases WHERE assigneduser = :username OR shared01 = :username OR shared02 = :username OR shared03 = :username OR shared04 = :username",
            "With defendant" => "SELECT COUNT(*) as count FROM cases WHERE assigneduser = :username OR defendent = :username",
            "Case insensitive" => "SELECT COUNT(*) as count FROM cases WHERE LOWER(assigneduser) = LOWER(:username)",
            "All fields" => "SELECT COUNT(*) as count FROM cases WHERE assigneduser = :username OR shared01 = :username OR shared02 = :username OR shared03 = :username OR shared04 = :username OR defendent = :username"
        ];
        
        foreach ($queries as $description => $query) {
            $stmt = $conn->prepare($query);
            $stmt->execute(['username' => $username]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "$description: $count cases found<br>";
        }
        
        // If any cases found, show them
        $stmt = $conn->prepare("SELECT * FROM cases WHERE assigneduser = :username OR shared01 = :username OR shared02 = :username OR shared03 = :username OR shared04 = :username OR defendent = :username LIMIT 10");
        $stmt->execute(['username' => $username]);
        $userCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($userCases) > 0) {
            echo "<h3>User's Cases (First 10):</h3>";
            echo "<table border='1'><tr>";
            // Headers
            foreach (array_keys($userCases[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // Data
            foreach ($userCases as $case) {
                echo "<tr>";
                foreach ($case as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No cases found for this user with any query.<br>";
        }
    } catch (PDOException $e) {
        echo "Error checking user cases: " . $e->getMessage() . "<br>";
    }
}

// 6. Test a very simple case listing
echo "<h2>6. Simple Case Listing Test</h2>";
try {
    // This is the simplest version of the query we'd use in index.php
    $query = "SELECT * FROM cases";
    if (isset($_SESSION['username'])) {
        $query .= " WHERE assigneduser = :username";
        $stmt = $conn->prepare($query);
        $stmt->execute(['username' => $_SESSION['username']]);
    } else {
        $stmt = $conn->query($query);
    }
    
    $testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($testCases) . " cases with simple query<br>";
} catch (PDOException $e) {
    echo "Error with simple query: " . $e->getMessage() . "<br>";
}
?>