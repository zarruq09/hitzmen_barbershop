<?php
// Shared logic to fetch data required for the booking form
// Requires $pdo to be available

$barbers = [];
$services = [];
$haircuts = [];
$haircutServiceIds = [];

try {
    // Fetch Barbers
    $barbersStmt = $pdo->query("SELECT id, name FROM barbers WHERE status != 'Deleted' ORDER BY name ASC");
    $barbers = $barbersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Services
    $servicesStmt = $pdo->query("SELECT id, service_name, price FROM services ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Haircuts
    $haircutsStmt = $pdo->query("SELECT id, style_name FROM haircuts ORDER BY style_name ASC");
    $haircuts = $haircutsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Identify Haircut Services for logic
    foreach ($services as $service) {
        if (stripos($service['service_name'], 'haircut') !== false || stripos($service['service_name'], 'cut') !== false || stripos($service['service_name'], 'shampoo') !== false) {
            $haircutServiceIds[] = $service['id'];
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching booking data: " . $e->getMessage());
}
?>
