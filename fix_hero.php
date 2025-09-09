<?php

// Simple database update without Laravel
$host = 'localhost';
$dbname = 'examgenerator_com';
$username = 'examgenerator_com';
$password = 'ExamGenerator@123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Update hero data
    $sql = "UPDATE settings SET 
            hero_sub_title = 'Welcome to ExamGenerator AI',
            hero_title = 'Create Amazing Exams with AI',
            hero_description = 'Generate professional exams, quizzes, and assessments using advanced AI technology. Perfect for educators, trainers, and organizations.'
            WHERE id = 1";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Hero data updated successfully!\n";
        
        // Verify the update
        $sql = "SELECT hero_sub_title, hero_title, hero_description FROM settings WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verification:\n";
        echo "Hero Sub Title: " . $result['hero_sub_title'] . "\n";
        echo "Hero Title: " . $result['hero_title'] . "\n";
        echo "Hero Description: " . $result['hero_description'] . "\n";
    } else {
        echo "Failed to update hero data!\n";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
