<?php
require_once __DIR__ . "/../includes/database.php";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'chatbot_faq'");
    if ($stmt->rowCount() == 0) {
        // Create table if it doesn't exist
        $pdo->exec("CREATE TABLE chatbot_faq (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert some default FAQs
        $default_faqs = [
            ['What are your business hours?', 'We are open from 8:00 AM to 10:00 PM, seven days a week.'],
            ['Do you offer delivery?', 'Yes, we offer delivery within a 5-mile radius of our location.'],
            ['What payment methods do you accept?', 'We accept cash, credit cards, and digital payments.'],
            ['Do you have vegetarian options?', 'Yes, we have a variety of vegetarian and vegan options available.']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO chatbot_faq (question, answer) VALUES (?, ?)");
        foreach ($default_faqs as $faq) {
            $stmt->execute($faq);
        }
        
        echo "FAQ table created and populated with default questions.";
    } else {
        echo "FAQ table already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 