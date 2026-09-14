<?php
/**
 * track.php
 * -----------------------------------------------------------------
 * Returns a delivery timeline for a single order the buyer owns.
 * Expects an additional table:
 *
 *   order_tracking(id, order_id, status, note, event_time)
 *
 * One row per status the order has actually reached, e.g.:
 *   (order_id=12, status='pending',    note='Order placed',            event_time='2026-07-20 09:02:00')
 *   (order_id=12, status='processing', note='Seller preparing goods',  event_time='2026-07-20 14:10:00')
 *   (order_id=12, status='shipped',    note='Left AgriTech warehouse', event_time='2026-07-21 08:00:00')
 *
 * The fixed PIPELINE below defines the display order and labels;
 * order_tracking supplies the real timestamps/notes for whichever
 * steps have actually happened.
 * -----------------------------------------------------------------
 */


require_once "config.php";

function fail(string $message, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo (["error" => $message]);
    exit;
}

if (empty($_SESSION["user_id"])) {
    fail("Please log in to track this order.", 401);
}

$buyerId = $_SESSION["user_id"];
$orderId = $_GET["order_id"] ?? null;

if (!$orderId || !ctype_digit((string) $orderId)) {
    fail("A valid order_id is required.", 400);
}

// ---- make sure this order actually belongs to the logged-in buyer --
$orderStmt = $conn->prepare(
    "SELECT order_id, status, order_date FROM orders WHERE order_id = ? AND buyer_id = ?"
);
$orderStmt->bind_param("ii", $orderId, $buyerId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$order) {
    fail("Order not found.", 404);
}

// ---- pull any logged tracking events for this order -----------------
$eventStmt = $conn->prepare(
    "SELECT status, note, event_time FROM order_tracking WHERE order_id = ? ORDER BY event_time ASC"
);
$eventStmt->bind_param("i", $orderId);
$eventStmt->execute();
$eventsResult = $eventStmt->get_result();

$eventsByStatus = [];
while ($row = $eventsResult->fetch_assoc()) {
    $eventsByStatus[$row["status"]] = $row; // last event per status wins if duplicated
}
$eventStmt->close();

// ---- build the timeline -------------------------------------------
if ($order["status"] === "cancelled") {
    $pipeline = [
        "pending"   => "Order placed",
        "cancelled" => "Order cancelled",
    ];
} else {
    $pipeline = [
        "pending"           => "Order placed",
        "processing"        => "Processing",
        "shipped"           => "Shipped",
        "out_for_delivery"  => "Out for delivery",
        "delivered"         => "Delivered",
    ];
}

$statusKeys = array_keys($pipeline);
$currentIndex = array_search($order["status"], $statusKeys, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}

$steps = [];
$i = 0;
foreach ($pipeline as $statusKey => $label) {
    if ($i < $currentIndex) {
        $state = "done";
    } elseif ($i === $currentIndex) {
        $state = "current";
    } else {
        $state = "upcoming";
    }

    $event = $eventsByStatus[$statusKey] ?? null;

    $steps[] = [
        "status"    => $statusKey,
        "label"     => $label,
        "state"     => $state,
        "timestamp" => $event["event_time"] ?? ($statusKey === "pending" ? $order["order_date"] : null),
        "note"      => $event["note"] ?? null,
    ];

    $i++;
}

echo ([
    "order_id" => (int) $order["order_id"],
    "status"   => $order["status"],
    "steps"    => $steps,
]);

$conn->close();
