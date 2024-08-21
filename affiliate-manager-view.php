<style>
    .affiliate-summary {
        display: flex;
        justify-content: space-around;
        margin-bottom: 20px;
    }

    .affiliate-summary .circle-container {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .affiliate-summary .circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 24px;
        font-weight: bold;
        color: white;
        margin-bottom: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .affiliate-summary .total-orders {
        background-color: #0073aa;
    }

    .affiliate-summary .pending-orders {
        background-color: #ff9900;
    }

    .affiliate-summary .approved-orders {
        background-color: #28a745;
    }

    .affiliate-summary .circle-text {
        font-size: 16px;
    }
</style>

<?php

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'affiliate_managers';
$orders_table_name = $wpdb->prefix . 'affiliate_orders';

$id = intval($_GET['id']);
$affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

if ($affiliate) {
    // Pobierz wszystkie zamówienia handlowca
    $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $orders_table_name WHERE affiliate_id = %d", $id));

    // Zmienne do liczenia zamówień
    $total_orders = 0;
    $pending_orders = 0;
    $approved_orders = 0;

    // Liczenie zamówień według statusu, pomijając te o statusie 'trash'
    foreach ($orders as $order) {
        $wc_order = wc_get_order($order->order_id);
        $order_status = $wc_order->get_status();

        if ($order_status === 'trash') {
            continue; // Pomijanie zamówień w koszu
        }

        $total_orders++; // Zwiększanie liczby tylko jeśli zamówienie nie jest w koszu

        if ($order_status === 'on-hold' || $order_status === 'pending') {
            $pending_orders++;
        } elseif ($order_status === 'processing' || $order_status === 'completed') {
            $approved_orders++;
        }
    }

    echo '<div class="wrap">';
    echo '<h2 style="margin-bottom:25px;">Raport zamówień dla: ' . esc_html($affiliate->name) . '</h2>';
    
    // Wyświetlenie podsumowania w formie 3 kółeczek
    echo '<div class="affiliate-summary">';
    
    echo '<div class="circle-container">';
    echo '<div class="circle total-orders">';
    echo '<p>' . esc_html($total_orders) . '</p>';
    echo '</div>';
    echo '<div class="circle-text">Łączna liczba zamówień</div>';
    echo '</div>';
    
    echo '<div class="circle-container">';
    echo '<div class="circle pending-orders">';
    echo '<p>' . esc_html($pending_orders) . '</p>';
    echo '</div>';
    echo '<div class="circle-text">Zamówienia oczekujące</div>';
    echo '</div>';
    
    echo '<div class="circle-container">';
    echo '<div class="circle approved-orders">';
    echo '<p>' . esc_html($approved_orders) . '</p>';
    echo '</div>';
    echo '<div class="circle-text">Zatwierdzone zamówienia</div>';
    echo '</div>';
    
    echo '</div>'; // Koniec affiliate-summary

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top:25px;">';
    echo '<thead><tr><th>Numer zamówienia</th><th>Data</th><th>Wartość zamówienia</th><th>Status zamówienia</th></tr></thead><tbody>';

    if ($orders) {
        foreach ($orders as $order) {
            $wc_order = wc_get_order($order->order_id);
            $order_status = $wc_order->get_status();

            if ($order_status === 'trash') {
                continue; // Pomijanie zamówień w koszu
            }

            $order_total = $wc_order->get_total();
            $currency = get_woocommerce_currency_symbol();

            // Link do edycji zamówienia w panelu WooCommerce
            $order_edit_link = admin_url('post.php?post=' . $order->order_id . '&action=edit');

            echo '<tr>';
            echo '<td><a href="' . esc_url($order_edit_link) . '" target="_blank">' . esc_html($order->order_id) . '</a></td>';
            echo '<td>' . esc_html($order->order_date) . '</td>';
            echo '<td>' . esc_html(number_format($order_total, 2)) . ' ' . esc_html($currency) . '</td>';
            echo '<td>' . esc_html(wc_get_order_status_name($order_status)) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">Brak zamówień</td></tr>';
    }

    echo '</tbody></table></div>';
} else {
    echo '<div class="error"><p>Handlowiec nie istnieje.</p></div>';
}