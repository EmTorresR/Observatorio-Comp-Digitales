function mi_plugin_test_stats_page() {
    if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'tests';
    $table_name_results = $wpdb->prefix . 'test_results'; // Nombre de la tabla corregido

    // Obtén el ID del test desde la URL
    $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

    // Verifica si el test existe
    $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $test_id));

    if (!$test) {
    echo "No se encontró el test con ID $test_id.";
    return;
    }

    // Imprime el título de la página de estadísticas
    echo "<div class='wrap'>";
    echo "<h2>Estadísticas del Test \"{$test->titulo}\"</h2>";

    // Agrega el título "Test Realizados"
    echo "<h3>Test Realizados</h3>";

    // Agrega la tabla de resultados de tests realizados
    echo "<table class='wp-list-table widefat fixed striped'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Nombre</th>";
    echo "<th>Correo</th>";
    echo "<th>Puntaje Total</th>";
    echo "<th>Fecha Realización</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    // Realiza la consulta para obtener los resultados de los tests realizados
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_results WHERE test_id = %d", $test_id));

    if ($results) {
    foreach ($results as $result) {
    echo "<tr>";
    echo "<td>{$result->nombre}</td>";
    echo "<td>{$result->correo}</td>";
    echo "<td>{$result->puntaje_total}</td>";
    echo "<td>{$result->fecha_realizacion}</td>";
    echo "</tr>";
    }
    } else {
    echo "<tr><td colspan='4'>No se encontraron resultados para este test.</td></tr>";
    $wpdb->print_error(); // Muestra el error si hay alguno
    }

    echo "</tbody>";
    echo "</table>";

    echo "</div>"; // Cierra el contenedor wrap
    }

    // Agregar la página de estadísticas al menú de administración
    function mi_plugin_test_stats_menu() {
    add_submenu_page(
        null,
        'Estadísticas del Test',
        'Estadísticas del Test',
        'manage_options',
        'mi-plugin-test-stats',
        'mi_plugin_test_stats_page'
    );
    }
    add_action('admin_menu', 'mi_plugin_test_stats_menu');