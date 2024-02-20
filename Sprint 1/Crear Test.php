<?php
    header('Content-Type: text/html; charset=utf-8');

    /*
Plugin Name: Skill-Kiri
Description: Un plugin creado por estudiantes de la Universidad Distrital de Colombia con el objetivo de evaluar habilidades digitales mediante cuestionarios con puntajes y pesos específicos.
Version: 1.1
Author: Emmanuel Torres Rodríguez
*/

// Agregar enlaces adicionales junto al nombre y la versión del plugin
function mostrar_nombre_y_enlaces($plugin_data, $plugin_file) {
    // Verificar si el plugin actual es el que queremos modificar
    if (plugin_basename(__FILE__) === $plugin_file) {
        $additional_links = array(
            '<a href="https://drive.google.com/file/d/1GVhZP05kaelFSu11ZQYa2Q0IERV-g4yW/view?usp=drive_link" target="_blank">Manual De Usuario</a>',
        );
        
        $plugin_data['Name'] .= ' ' . implode(' | ', $additional_links);
    }
    return $plugin_data;
}
add_filter('plugin_row_meta', 'mostrar_nombre_y_enlaces', 10, 2);

    global $mi_plugin_test_db_version;
    $mi_plugin_test_db_version = '1.0';

    // Función para crear la tabla de la base de datos cuando se activa el plugin
    function mi_plugin_test_install() {
    global $wpdb;
    $mi_plugin_test_db_version = '1.0';

    $table_name = $wpdb->prefix . 'tests';
    $table_name_categories = $wpdb->prefix . 'test_categories';
    $table_name_dimensions = $wpdb->prefix . 'test_dimensions';
    $table_name_results = $wpdb->prefix . 'test_results'; 
    $tabla_mensajes_condicionales = $wpdb->prefix . 'test_mensajes_condicionales';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        titulo text NOT NULL,
        descripcion text NOT NULL,
        fecha_creacion datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql_categories = "CREATE TABLE $table_name_categories (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        dimension_id mediumint(9) NOT NULL,
        nombre_categoria text NOT NULL,
        puntaje_categoria mediumint(9) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (test_id) REFERENCES $table_name(id) ON DELETE CASCADE 
    ) $charset_collate;";

    $sql_dimensions = "CREATE TABLE $table_name_dimensions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        nombre_dimension text NOT NULL,
        puntaje_dimension mediumint(9) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (test_id) REFERENCES $table_name(id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql_results = "CREATE TABLE $table_name_results (  
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        correo VARCHAR(255) NOT NULL,
        puntaje_total mediumint(9) NOT NULL,
        fecha_realizacion DATETIME NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (test_id) REFERENCES $table_name(id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql_puntajes_categoria = "CREATE TABLE " . $wpdb->prefix . 'test_puntajes_categoria' . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        resultado_id mediumint(9) NOT NULL,
        categoria_id mediumint(9) NOT NULL,
        puntaje_categoria mediumint(9) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (resultado_id) REFERENCES $table_name_results(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id) REFERENCES $table_name_categories(id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql_puntajes_dimension = "CREATE TABLE " . $wpdb->prefix . 'test_puntajes_dimension' . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        resultado_id mediumint(9) NOT NULL,
        dimension_id mediumint(9) NOT NULL,
        puntaje_dimension mediumint(9) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (resultado_id) REFERENCES $table_name_results(id) ON DELETE CASCADE,
        FOREIGN KEY (dimension_id) REFERENCES $table_name_dimensions(id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql_mensajes_condicionales = "CREATE TABLE " . $wpdb->prefix . 'test_mensajes_condicionales' . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        tipo_elemento varchar(50) NOT NULL,
        id_elemento varchar(100) NOT NULL,
        rango_inicial int(11) NOT NULL,
        rango_final int(11) NOT NULL,
        mensaje text NOT NULL,
        nombre_link varchar(100), /* Nuevo campo: nombre del link */
        link varchar(255), /* Nuevo campo: link */
        imagen varchar(255), /* Nombre o ruta de la imagen */
        fecha_realizacion DATETIME NOT NULL, /* Nueva columna: fecha de creación */
        PRIMARY KEY (id),
        FOREIGN KEY (test_id) REFERENCES $table_name(id) ON DELETE CASCADE
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_categories);
    dbDelta($sql_dimensions);
    dbDelta($sql_results);
    dbDelta($sql_puntajes_categoria); 
    dbDelta($sql_puntajes_dimension); 
    dbDelta($sql_mensajes_condicionales);

    add_option('mi_plugin_test_db_version', $mi_plugin_test_db_version);
    }

    register_activation_hook(__FILE__, 'mi_plugin_test_install');

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
