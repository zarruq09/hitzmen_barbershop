<?php
require_once __DIR__ . '/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL UNIQUE,
        user_id INT NOT NULL,
        barber_id INT DEFAULT NULL,
        shop_rating INT NOT NULL CHECK (shop_rating BETWEEN 1 AND 5),
        service_rating INT NOT NULL CHECK (service_rating BETWEEN 1 AND 5),
        staff_rating INT NOT NULL CHECK (staff_rating BETWEEN 1 AND 5),
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);
    echo "Migration Successful: 'feedback' table created.";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage();
}
?>
