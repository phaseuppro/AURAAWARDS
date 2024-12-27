<?php
$user_id = get_current_user_id();
$available_credits = get_user_meta($user_id, 'aura_credits', true) ?: 0;
$total_purchased = get_user_meta($user_id, 'aura_total_purchased', true) ?: 0;

// Get credit purchase history
$orders = wc_get_orders(array(
    'customer_id' => $user_id,
    'status' => 'completed'
));
?>
<div class="aura-credits-dashboard">
    <div class="credits-summary">
        <p>Available Credits: <?php echo esc_html($available_credits); ?></p>
        <p>Total Purchased: <?php echo esc_html($total_purchased); ?></p>
    </div>
    <div class="purchase-credits">
        <p>
            Need more credits? 
        </p>
        <a href="https://aura-awards.com/product-category/submission-credits/" class="button">
            Purchase Credits for your next Submissions
        </a>
    </div>
</div>

<style>
/* General Dashboard Styles */
.aura-credits-dashboard {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9; /* Neutral background */
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #ddd; /* Subtle border */
}

/* Credits Summary */
.credits-summary p {
    font-size: 14px;
    line-height: 1.5;
    color: #495057; /* Neutral text color */
    margin-bottom: 10px;
}

/* Purchase Credits Section */
.purchase-credits p {
    margin-top: 20px;
    font-size: 14px; /* Einheitliche Schriftgröße */
    line-height: 1.5; /* Lesbare Zeilenhöhe */
    color: #495057; /* Dezente Textfarbe */
}

/* Purchase Credits Button */
.purchase-credits .button {
    display: block; /* Button nimmt die gesamte Breite ein */
    width: 100%; /* Feste Breite auf 100% */
    margin-top: 10px; /* Abstand zum Text */
    padding: 10px 15px;
    background-color: #007bff; /* Primäre Button-Farbe */
    color: #fff; /* Weiße Schrift */
    text-decoration: none; /* Kein Unterstrich */
    border-radius: 5px; /* Abgerundete Ecken */
    font-size: 14px; /* Einheitliche Schriftgröße */
    text-align: center; /* Horizontale Zentrierung */
    line-height: 1.5; /* Zeilenhöhe für vertikale Zentrierung */
    display: flex; /* Flexbox für präzise Zentrierung */
    justify-content: center; /* Horizontale Zentrierung */
    align-items: center; /* Vertikale Zentrierung */
    transition: background-color 0.3s ease, color 0.3s ease; /* Weiche Übergänge */
}

/* Button Hover-Effekt */
.purchase-credits .button:hover {
    background-color: #0056b3; /* Dunklere Hover-Farbe */
    color: #fff; /* Weiße Schrift */
}
</style>
