<?php
// Test script to check file permissions

echo "<h2>File Permission Test</h2>";

// Test data directories
$directories = [
    'data' => __DIR__ . '/data',
    'data/games' => __DIR__ . '/data/games',
    'data/images' => __DIR__ . '/data/images',
];

// Test each directory
foreach ($directories as $name => $path) {
    echo "<p><strong>Testing $name directory</strong>:</p>";
    echo "Path: $path<br>";
    
    if (file_exists($path)) {
        echo "✅ Directory exists<br>";
        
        if (is_dir($path)) {
            echo "✅ Is a directory<br>";
        } else {
            echo "❌ Not a directory!<br>";
        }
        
        if (is_readable($path)) {
            echo "✅ Directory is readable<br>";
        } else {
            echo "❌ Directory is not readable!<br>";
        }
        
        if (is_writable($path)) {
            echo "✅ Directory is writable<br>";
            
            // Try to create a test file
            $testFile = $path . '/test_' . time() . '.txt';
            $content = 'Test file created at ' . date('Y-m-d H:i:s');
            
            if (file_put_contents($testFile, $content)) {
                echo "✅ Successfully created test file<br>";
                
                // Try to read the test file
                $readContent = file_get_contents($testFile);
                if ($readContent === $content) {
                    echo "✅ Successfully read test file<br>";
                } else {
                    echo "❌ Failed to read test file content correctly!<br>";
                }
                
                // Try to delete the test file
                if (unlink($testFile)) {
                    echo "✅ Successfully deleted test file<br>";
                } else {
                    echo "❌ Failed to delete test file!<br>";
                }
            } else {
                echo "❌ Failed to create test file!<br>";
            }
        } else {
            echo "❌ Directory is not writable!<br>";
        }
    } else {
        echo "❌ Directory does not exist!<br>";
    }
    
    echo "<hr>";
}

// Test PHP upload configuration
echo "<p><strong>PHP File Upload Configuration</strong>:</p>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : "System default") . "<br>";
echo "file_uploads enabled: " . (ini_get('file_uploads') ? "Yes" : "No") . "<br>";

// Test if PHP can write to the temp directory
$tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "<p><strong>Testing temporary upload directory</strong>:</p>";
echo "Path: $tempDir<br>";

if (file_exists($tempDir)) {
    echo "✅ Directory exists<br>";
    
    if (is_dir($tempDir)) {
        echo "✅ Is a directory<br>";
    } else {
        echo "❌ Not a directory!<br>";
    }
    
    if (is_writable($tempDir)) {
        echo "✅ Directory is writable<br>";
    } else {
        echo "❌ Directory is not writable!<br>";
    }
} else {
    echo "❌ Directory does not exist!<br>";
}
?>