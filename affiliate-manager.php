<?php
/*
Plugin Name: Affiliate Manager
Description: Wtyczka do zarządzania handlowcami i śledzenia zamówień w WooCommerce.
Version: 1.1.0
Author: DoubleM
*/

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

// Rejestracja nowego menu w panelu administracyjnym
function affiliate_manager_menu() {
    add_menu_page('Affiliate Manager', 'Handlowcy', 'manage_options', 'affiliate-manager', 'affiliate_manager_page', 'dashicons-admin-users', 56);
    add_submenu_page('affiliate-manager', 'Dodaj handlowca', 'Dodaj handlowca', 'manage_options', 'affiliate-manager-add', 'affiliate_manager_add_page');
    add_submenu_page(null, 'Edytuj handlowca', 'Edytuj handlowca', 'manage_options', 'affiliate-manager-edit', 'affiliate_manager_edit_page');
    add_submenu_page(null, 'Usuń handlowca', 'Usuń handlowca', 'manage_options', 'affiliate-manager-delete', 'affiliate_manager_delete_page');
    add_submenu_page(null, 'Zobacz raport', 'Zobacz raport', 'manage_options', 'affiliate-manager-view', 'affiliate_manager_view_page');
}

function affiliate_manager_edit_page() {
    include('affiliate-manager-edit.php');
}

function affiliate_manager_delete_page() {
    include('affiliate-manager-delete.php');
}

function affiliate_manager_view_page() {
    include('affiliate-manager-view.php');
}


add_action('admin_menu', 'affiliate_manager_menu');

// Funkcja do wyświetlania strony głównej wtyczki
function affiliate_manager_page() {
    include('affiliate-manager-list.php');
}

// Funkcja do wyświetlania strony dodawania nowego handlowca
function affiliate_manager_add_page() {
    include('affiliate-manager-add.php');
}

// Rejestracja niestandardowej tabeli w bazie danych dla handlowców
function affiliate_manager_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'affiliate_managers';
    $charset_collate = $wpdb->get_charset_collate();

    // Tabela dla handlowców
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        code varchar(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $wpdb->query($sql);

    // Tabela dla zamówień z polecenia
    $orders_table_name = $wpdb->prefix . 'affiliate_orders';

    $sql_orders = "CREATE TABLE $orders_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        affiliate_id mediumint(9) NOT NULL,
        order_total float NOT NULL,
        order_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY (affiliate_id) REFERENCES $table_name(id) ON DELETE CASCADE
    ) $charset_collate;";

    $wpdb->query($sql_orders);
}

register_activation_hook(__FILE__, 'affiliate_manager_install');

// Funkcja do generowania unikalnego kodu afiliacyjnego
function generate_affiliate_code($length = 8) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

// Funkcja do śledzenia zamówień WooCommerce
function track_affiliate_order($order_id) {
    if (isset($_COOKIE['affiliate_code'])) {
        $affiliate_code = sanitize_text_field($_COOKIE['affiliate_code']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_managers';
        $orders_table_name = $wpdb->prefix . 'affiliate_orders';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $affiliate_code));
        
        if ($affiliate) {
            // Pobierz szczegóły zamówienia
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();
            $order_date = $order->get_date_created()->format('Y-m-d H:i:s');

            // Zapisz informacje o zamówieniu w tabeli affiliate_orders
            $wpdb->insert(
                $orders_table_name,
                array(
                    'order_id' => $order_id,
                    'affiliate_id' => $affiliate->id,
                    'order_total' => $order_total,
                    'order_date' => $order_date
                )
            );

            // Debugowanie - sprawdź, czy zamówienie zostało zapisane
            if ($wpdb->insert_id) {
                error_log("Zamówienie $order_id zostało poprawnie zapisane dla handlowca $affiliate->id.");
            } else {
                error_log("Nie udało się zapisać zamówienia $order_id dla handlowca $affiliate->id.");
            }
        } else {
            error_log("Nie znaleziono handlowca z kodem afiliacyjnym $affiliate_code dla zamówienia $order_id.");
        }
    } else {
        error_log("Brak ciasteczka 'affiliate_code' dla zamówienia $order_id.");
    }
}
add_action('woocommerce_checkout_order_processed', 'track_affiliate_order');

// Funkcja do ustawiania ciasteczka po wejściu z linku afiliacyjnego
function set_affiliate_cookie() {
    if (isset($_GET['ref'])) {
        $ref = sanitize_text_field($_GET['ref']);
        setcookie('affiliate_code', $ref, time() + (86400 * 30), '/');
    }
}
add_action('init', 'set_affiliate_cookie');

function notify_affiliate_order($order_id) {
    $order = wc_get_order($order_id);
    $affiliate_code = get_post_meta($order_id, '_affiliate_code', true);
    
    if ($affiliate_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_managers';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $affiliate_code));
        
        if ($affiliate) {
            $to = $affiliate->email;
            $subject = 'Nowe zamówienie przypisane do Ciebie';
            $body = 'Zamówienie #' . $order->get_order_number() . ' zostało złożone przez klienta, który użył Twojego kodu afiliacyjnego.';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            wp_mail($to, $subject, $body, $headers);
        }
    }
}
add_action('woocommerce_thankyou', 'notify_affiliate_order');