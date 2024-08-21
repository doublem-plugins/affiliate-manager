<?php

// Zapobiega bezpośredniemu dostępowi do pliku
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'affiliate_managers';

$id = intval($_GET['id']);
$affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    $wpdb->update(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
        ),
        array('id' => $id)
    );

    echo '<div class="updated"><p>Dane handlowca zaktualizowane.</p></div>';
    $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

?>

<div class="wrap">
    <h2>Edytuj handlowca</h2>
    <form method="POST" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name">Imię i nazwisko</label></th>
                <td><input name="name" type="text" id="name" value="<?php echo esc_attr($affiliate->name); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email">Email</label></th>
                <td><input name="email" type="email" id="email" value="<?php echo esc_attr($affiliate->email); ?>" class="regular-text" required></td>
            </tr>
        </table>
        <p class="submit"><input type="submit" class="button-primary" value="Zaktualizuj handlowca"></p>
    </form>
</div>