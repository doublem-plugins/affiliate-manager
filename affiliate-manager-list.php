<?php

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'affiliate_managers';
$orders_table_name = $wpdb->prefix . 'affiliate_orders';

$affiliates = $wpdb->get_results("SELECT * FROM $table_name");

echo '<div class="wrap"><h2 style="margin-bottom:25px;">Lista Handlowców</h2>';
echo '<table class="wp-list-table widefat fixed striped">';
echo '<thead><tr>
        <th>Handlowiec</th>
        <th>Link i kod afiliacyjny</th>
        <th>Status zamówień</th>
        <th style="text-align:right;">Akcje</th>
    </tr></thead><tbody>';

foreach ($affiliates as $affiliate) {
    // Przygotowanie linku afiliacyjnego
    $affiliate_link = home_url('/?ref=' . $affiliate->code);

    // Pobranie wszystkich zamówień handlowca
    $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $orders_table_name WHERE affiliate_id = %d", $affiliate->id));

    // Zmienne do liczenia zamówień
    $total_orders = 0;
    $pending_orders = 0;
    $approved_orders = 0;

    // Liczenie zamówień według statusu, pomijając zamówienia w koszu (trash)
    foreach ($orders as $order) {
        $wc_order = wc_get_order($order->order_id);
        $order_status = $wc_order->get_status();

        if ($order_status === 'trash') {
            continue; // Pomijanie zamówień w koszu
        }

        $total_orders++; // Zwiększanie liczby zamówień tylko jeśli nie są w koszu

        if ($order_status === 'on-hold' || $order_status === 'pending') {
            $pending_orders++;
        } elseif ($order_status === 'processing' || $order_status === 'completed') {
            $approved_orders++;
        }
    }

    echo "<tr>
        <td>
            <strong>{$affiliate->name}</strong><br>
            <small>{$affiliate->email}</small>
        </td>
        <td><a href='{$affiliate_link}' target='_blank'>{$affiliate_link}</a><br><br>{$affiliate->code}</td>
        <td>
            Łącznie: {$total_orders}
            <br>
            Oczekujących: {$pending_orders}
            <br>
            Zatwierdzonych: {$approved_orders}
        </td>
        <td style='text-align:right;'>
            <a href='?page=affiliate-manager-edit&id={$affiliate->id}'>Edytuj</a> |
            <a href='?page=affiliate-manager-delete&id={$affiliate->id}' onclick='return confirm(\"Czy na pewno chcesz usunąć tego handlowca?\")'>Usuń</a> |
            <a href='?page=affiliate-manager-view&id={$affiliate->id}'>Zobacz raport</a>
        </td>
    </tr>";
}

echo '</tbody></table></div>';
?>