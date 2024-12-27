<?php

class AURA_WooCommerce {
    public function __construct() {
        // Hook registrieren für abgeschlossene Bestellungen
        add_action('woocommerce_order_status_completed', array($this, 'process_credit_purchase'));
        add_action('woocommerce_payment_complete', array($this, 'process_credit_purchase'));

        // Hooks für WooCommerce-Produktbearbeitung hinzufügen
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_credits_field_to_product'));
        add_action('woocommerce_process_product_meta', array($this, 'save_credits_field_to_product'));
    }

    /**
     * Verarbeitet den Kauf von Credits und weist sie dem Benutzer zu.
     *
     * @param int $order_id Die ID der abgeschlossenen Bestellung.
     */
    public function process_credit_purchase($order_id) {
        error_log('Credit Purchase - Hook triggered for order: ' . $order_id);

        // Bestellung abrufen
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        // Überprüfen, ob eine Benutzer-ID vorhanden ist
        if (!$user_id) {
            error_log('Credit Purchase - No user ID associated with order: ' . $order_id);
            return;
        }

        // Überprüfen, ob die Bestellung vollständig abgeschlossen ist
        if (!in_array($order->get_status(), ['completed', 'processing'])) {
            error_log('Credit Purchase - Order not completed or processing. Skipping credit allocation for order: ' . $order_id);
            return;
        }

        // Produkte in der Bestellung durchgehen
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id(); // Produkt-ID abrufen
            $credits = get_post_meta($product_id, '_aura_credits', true); // Credits aus Metadaten abrufen

            error_log('Credit Purchase - Product ID: ' . $product_id . ', Credits: ' . $credits);

            // Sicherstellen, dass Credits definiert sind
            if ($credits) {
                // Benutzer-Credits aktualisieren
                $current_credits = (int) get_user_meta($user_id, 'aura_credits', true);
                $new_total = $current_credits + (int) $credits;

                update_user_meta($user_id, 'aura_credits', $new_total);

                error_log('Credit Purchase - Updated credits for user ID: ' . $user_id . ' to: ' . $new_total);
            } else {
                error_log('Credit Purchase - No credits found for product ID: ' . $product_id);
                continue;
            }
        }
    }

    /**
     * Fügt ein Feld für Credits in der WooCommerce-Produktbearbeitungsseite hinzu.
     */
    public function add_credits_field_to_product() {
        woocommerce_wp_text_input(array(
            'id' => '_aura_credits',
            'label' => __('Credits', 'aura-photo-awards'),
            'desc_tip' => true,
            'description' => __('Number of credits this product grants.', 'aura-photo-awards'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min' => '0',
            ),
        ));
    }

    /**
     * Speichert den eingegebenen Credits-Wert in den Produkt-Metadaten.
     *
     * @param int $post_id Die ID des Produkts.
     */
    public function save_credits_field_to_product($post_id) {
        $credits = isset($_POST['_aura_credits']) ? absint($_POST['_aura_credits']) : 0;
        update_post_meta($post_id, '_aura_credits', $credits);
        error_log('Product Meta - Credits saved for product ID: ' . $post_id . ', Credits: ' . $credits);
    }
}
