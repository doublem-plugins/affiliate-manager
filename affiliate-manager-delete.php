<?php

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'affiliate_managers';

$id = intval($_GET['id']);

$wpdb->delete($table_name, array('id' => $id));

echo '<div class="updated"><p>Handlowiec usunięty.</p></div>';
echo '<a href="?page=affiliate-manager" class="button-primary">Powrót do listy handlowców</a>';
?>