<?php
// Test write permissions

// Define the directories to test
$directories = [
    'data' => __DIR__ . '/data',
    'data/games' => __DIR__ . '/data/games',
    'data/images' => __DIR__ . '/data/images',
];

try {
    // Test creating a unique test file in each directory
    foreach ($directories as $dirName => $dirPath) {
        echo "<h3>Testing write permissions for $dirName</h3>";
        
        // Check if directory exists
        if (!file_exists($dirPath)) {
            echo "<p style='color: red'>ERROR: Directory does not exist: $dirPath</p>";
            continue;
        }
        
        if (!is_dir($dirPath)) {
            echo "<p style='color: red'>ERROR: Path is not a directory: $dirPath</p>";
            continue;
        }
        
        // Check if directory is writable
        if (!is_writable($dirPath)) {
            echo "<p style='color: red'>ERROR: Directory is not writable: $dirPath</p>";
            continue;
        }
        
        // Try to create a file
        $testFile = $dirPath . '/test_write_' . time() . '.txt';
        $content = 'Test write at ' . date('Y-m-d H:i:s');
        
        if (file_put_contents($testFile, $content)) {
            echo "<p style='color: green'>SUCCESS: Created test file: $testFile</p>";
            
            // Try to read the file
            $readContent = @file_get_contents($testFile);
            if ($readContent === $content) {
                echo "<p style='color: green'>SUCCESS: Read test file content correctly</p>";
            } else {
                echo "<p style='color: red'>ERROR: Could not read file content correctly</p>";
            }
            
            // Try to delete the file
            if (@unlink($testFile)) {
                echo "<p style='color: green'>SUCCESS: Deleted test file</p>";
            } else {
                echo "<p style='color: red'>ERROR: Could not delete test file</p>";
            }
        } else {
            echo "<p style='color: red'>ERROR: Could not create test file in $dirPath</p>";
        }
    }
    
    // Test creating a game file directly
    $gameDir = __DIR__ . '/data/games';
    $testGameCode = 'TEST' . rand(1000, 9999);
    $testGameFile = $gameDir . '/' . $testGameCode . '.php';
    
    echo "<h3>Testing direct game file creation</h3>";
    
    $gameContent = "<?php\n";
    $gameContent .= "// Test game file\n";
    $gameContent .= "return array (\n";
    $gameContent .= "  'gameCode' => '$testGameCode',\n";
    $gameContent .= "  'hostPlayerId' => 'test_player_id',\n";
    $gameContent .= ");\n";
    $gameContent .= "?>";
    
    if (file_put_contents($testGameFile, $gameContent)) {
        echo "<p style='color: green'>SUCCESS: Created test game file: $testGameFile</p>";
        
        // Try to include the file
        $testData = @include($testGameFile);
        if (is_array($testData) && $testData['gameCode'] === $testGameCode) {
            echo "<p style='color: green'>SUCCESS: Read test game file content correctly</p>";
        } else {
            echo "<p style='color: red'>ERROR: Could not read game file content correctly</p>";
        }
        
        // Try to delete the file
        if (@unlink($testGameFile)) {
            echo "<p style='color: green'>SUCCESS: Deleted test game file</p>";
        } else {
            echo "<p style='color: red'>ERROR: Could not delete test game file</p>";
        }
    } else {
        echo "<p style='color: red'>ERROR: Could not create test game file in $gameDir</p>";
    }
    
    // Test PHP's upload configuration
    echo "<h3>PHP File Upload Configuration</h3>";
    echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
    echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
    echo "<p>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";
    echo "<p>file_uploads enabled: " . (ini_get('file_uploads') ? "Yes" : "No") . "</p>";
    
    // Test temporary directory 
    $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    echo "<h3>Testing temporary directory: $tmpDir</h3>";
    
    if (!is_dir($tmpDir)) {
        echo "<p style='color: red'>ERROR: Temporary directory does not exist or is not accessible</p>";
    } else if (!is_writable($tmpDir)) {
        echo "<p style='color: red'>ERROR: Temporary directory is not writable</p>";
    } else {
        echo "<p style='color: green'>SUCCESS: Temporary directory is writable</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red'>ERROR: " . $e->getMessage() . "</h3>";
}
?>