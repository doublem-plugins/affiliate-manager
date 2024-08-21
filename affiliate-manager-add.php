<?php

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'affiliate_managers';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $code = generate_affiliate_code();
    
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'code' => $code,
        )
    );

    echo '<div class="updated"><p>Handlowiec dodany.</p></div>';
}

?>

<div class="wrap">
    <h2>Dodaj nowego handlowca</h2>
    <form method="POST" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name">Imię i nazwisko</label></th>
                <td><input name="name" type="text" id="name" value="" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email">Email</label></th>
                <td><input name="email" type="email" id="email" value="" class="regular-text" required></td>
            </tr>
        </table>
        <p class="submit"><input type="submit" class="button-primary" value="Dodaj handlowca"></p>
    </form>
</div>