// Función para agregar el formulario de creación de test en el panel de administración
    function mi_plugin_test_admin_menu() {
    add_menu_page(
        'Crear Test',
        'Crear Test',
        'manage_options',
        'mi-plugin-test',
        'mi_plugin_test_page'
    );
    }
    add_action('admin_menu', 'mi_plugin_test_admin_menu');

    // Función para mostrar el formulario de creación de test en la página de administración
    function mi_plugin_test_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'tests';
    $table_name_categories = $wpdb->prefix . 'test_categories';

    // Procesar la eliminación de un test si se hace clic en el enlace "Eliminar"
    if (isset($_GET['delete_test']) && isset($_GET['_wpnonce'])) {
        $test_id = intval($_GET['delete_test']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);

        if (wp_verify_nonce($nonce, 'eliminar_test_' . $test_id)) {
            $wpdb->delete(
                $table_name,
                array('id' => $test_id),
                array('%d')
            );

            // También elimina las categorías asociadas al test
            $wpdb->delete(
                $table_name_categories,
                array('test_id' => $test_id),
                array('%d')
            );
        }
    }

    // Obtener todos los tests creados
    $tests = $wpdb->get_results("SELECT * FROM $table_name ORDER BY fecha_creacion DESC");

    ?>
    <div class="wrap">
        <h2>Crear un nuevo Test</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="crear_test">
            <?php wp_nonce_field('crear_test_nonce', 'crear_test_nonce'); ?>

            <label for="titulo">Título del Test:</label>
            <input type="text" name="titulo" id="titulo" required><br><br>
            
            <label for="descripcion">Descripción del Test:</label>
            <?php
    $content = ''; // Inicializa el contenido vacío si es un nuevo test, o recupera el contenido almacenado si estás editando un test existente
    $editor_id = 'descripcion'; // ID del editor, debe ser único para cada campo
    $settings = array(
    'media_buttons' => false, // Puedes habilitar o deshabilitar los botones multimedia según tus necesidades
    'textarea_name' => 'descripcion', // El nombre del campo que se enviará al procesar el formulario
    );
    wp_editor($content, $editor_id, $settings);
    ?>
    <br> 
    <input type="submit" name="crear_test" value="Crear Test">

    </form>

    <h2>Tests Creados</h2>
    <table class="wp-list-table widefat fixed striped">
    <thead>
    <tr>
        <th>Título</th>
        <th>Descripción</th>
        <th>Shortcode</th>
        <th>Fecha de Creación</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($tests as $test) {
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=mi-plugin-test&delete_test=' . $test->id),
            'eliminar_test_' . $test->id
        );

            // Generar el shortcode para este test
        $shortcode = "[mi_test id='{$test->id}']";

        echo "<tr>";
        echo "<td>{$test->titulo}</td>";

            // Limitar la descripción a 50 caracteres y agregar tres puntos suspensivos
        $descripcion_cortada = strlen($test->descripcion) > 50 ? substr($test->descripcion, 0, 50) . '...' : $test->descripcion;
        echo "<td>{$descripcion_cortada}</td>";

        echo "<td>$shortcode</td>";
        echo "<td>{$test->fecha_creacion}</td>";
        echo "<td>";
        echo "<a href='" . admin_url("admin.php?page=mi-plugin-test-categories&test_id={$test->id}") . "' class='ver-categorias-button'>Elementos</a> | ";
        echo "<a href='" . admin_url("admin.php?page=mi-plugin-test-stats&test_id={$test->id}") . "' class='ver-estadísticas-button'>Estadísticas</a>";
        echo "<a href='" . admin_url("admin.php?page=mi-plugin-test-condicionales&test_id={$test->id}") . "' class='ver-condicionales-button'>Condicionales</a> | ";
        echo "<a href='$delete_url' class='delete-test'>Eliminar</a";
        echo "</td>";
        echo "</tr>";
    }
    ?>
    </tbody>
    </table>

    </div>
    <script>
        // Confirmación antes de eliminar un test
    jQuery(document).ready(function($) {
    $('.delete-test').click(function(e) {
    if (!confirm('?Est? seguro de que deseas eliminar este test?')) {
        e.preventDefault();
    }
    });
    });
    </script>
    <?php
    }
