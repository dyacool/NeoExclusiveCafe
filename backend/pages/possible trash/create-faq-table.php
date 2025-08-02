<?php
require_once __DIR__ . "/../includes/database.php";

try {
    // Create the chatbot_faq table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS chatbot_faq (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    
    // Insert some sample FAQs if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM chatbot_faq");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sampleFaqs = [
            [
                'question' => 'What are your business hours?',
                'answer' => 'We are open Monday to Sunday from 8:00 AM to 10:00 PM.'
            ],
            [
                'question' => 'Do you offer delivery services?',
                'answer' => 'Yes, we offer delivery services within a 5km radius of our location.'
            ],
            [
                'question' => 'How can I place an order?',
                'answer' => 'You can place an order through our website, by phone, or by visiting our cafe.'
            ],
            [
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept cash, credit cards, and mobile payments.'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO chatbot_faq (question, answer) VALUES (?, ?)");
        
        foreach ($sampleFaqs as $faq) {
            $stmt->execute([$faq['question'], $faq['answer']]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'FAQ table created and sample data inserted successfully'
    ]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create FAQ table'
    ]);
} 