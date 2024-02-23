$('#form-consultar-historial').on('submit', function(e) {
    e.preventDefault();
    var historialFormData = $(this).serialize();
    console.log(historialFormData); // Agrega esta línea para verificar los datos del formulario

    $.ajax({
    type: 'POST',
    url: '" . admin_url('admin-ajax.php') . "',
    data: {
        action: 'consultar_historial',
        historialFormData: historialFormData
    },
    success: function(response) {
        $('#tabla-container').html(response);
        $('#tabla-container').show(); // Mostrar la tabla de resultados
    }
    });
    });
    

function consultar_historial_ajax() {
    $historialFormData = $_POST['historialFormData'];

    // Deserializa la cadena en un array asociativo
    parse_str($historialFormData, $historialArray);

    // Accede a los datos
    $correo = sanitize_email($historialArray['correo_historial']);

    // Realiza la consulta en la base de datos para obtener el historial
    global $wpdb;
    $table_name_results = $wpdb->prefix . 'test_results';

    $historial_results = $wpdb->get_results($wpdb->prepare(
    "SELECT id, fecha_realizacion, correo, puntaje_total
    FROM $table_name_results
    WHERE correo = %s",
    $correo
    ));

    // Muestra los resultados en una tabla con estilos de Bootstrap
    if (!empty($historial_results)) {
    $output = '<h3>Historial de Resultados:</h3>';
    $output .= '<div class="table-responsive">';
    $output .= '<table class="table table-striped">';
    $output .= '<thead class="thead-dark">';
    $output .= '<tr>';
    $output .= '<th>Fecha Realización</th>';
    $output .= '<th>Correo</th>';
    $output .= '<th>Puntaje Final</th>';
    $output .= '<th>Más Información</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($historial_results as $result) {
    $output .= '<tr>';
    $output .= '<td>' . $result->fecha_realizacion . '</td>';
    $output .= '<td>' . $result->correo . '</td>';
    $output .= '<td>' . $result->puntaje_total . '</td>';
        $modal_id = 'myModal_' . $result->id; // ID único para cada modal
        $output .= '<td><button class="btn btn-primary ver-mas" data-toggle="modal" data-target="#' . $modal_id . '">Ver Más</button></td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '</div>'; // Cierre de div para la tabla responsiva

    // Generar los modales fuera del bucle foreach de resultados
    foreach ($historial_results as $result) {
        $modal_id = 'myModal_' . $result->id; // ID único para cada modal

        $output .= '<div class="modal fade" id="' . $modal_id . '" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">';
        $output .= '<div class="modal-dialog" role="document">';
        $output .= '<div class="modal-content">';
        $output .= '<div class="modal-header">';
        $output .= '<h5 class="modal-title" id="exampleModalLabel">Información adicional del test</h5>';
        $output .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $output .= '<span aria-hidden="true">&times;</span>';
        $output .= '</button>';
        $output .= '</div>';
        $output .= '<div class="modal-body">';

        // Consulta para obtener los puntajes por categorías
        $sql_puntajes_categoria = $wpdb->prepare(
            "SELECT c.nombre_categoria AS Nombre_Categoria, pc.puntaje_categoria AS Puntaje_Categoria 
            FROM {$wpdb->prefix}test_results r 
            INNER JOIN {$wpdb->prefix}test_puntajes_categoria pc ON r.id = pc.resultado_id 
            INNER JOIN {$wpdb->prefix}test_categories c ON pc.categoria_id = c.id 
            WHERE r.id = %d",
            $result->id
        );

        $puntajes_categoria = $wpdb->get_results($sql_puntajes_categoria);

        if (!empty($puntajes_categoria)) {
            $output .= '<h5>Puntajes por Categorías:</h5>';
            $output .= '<div class="table-responsive">';
            $output .= '<table class="table">';
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Nombre Categoría</th>';
            $output .= '<th>Puntaje</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            foreach ($puntajes_categoria as $categoria) {
                $output .= '<tr>';
                $output .= '<td>' . $categoria->Nombre_Categoria . '</td>';
                $output .= '<td>' . $categoria->Puntaje_Categoria . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
        } else {
            $output .= '<p>No hay puntajes por categorías para este resultado.</p>';
        }

        // Consulta para obtener los puntajes por dimensiones
        $sql_puntajes_dimension = $wpdb->prepare(
            "SELECT d.nombre_dimension AS Nombre_Dimension, pd.puntaje_dimension AS Puntaje_Dimension 
            FROM {$wpdb->prefix}test_results r 
            LEFT JOIN {$wpdb->prefix}test_puntajes_dimension pd ON r.id = pd.resultado_id 
            LEFT JOIN {$wpdb->prefix}test_dimensions d ON pd.dimension_id = d.id 
            WHERE r.id = %d",
            $result->id
        );

        $puntajes_dimension = $wpdb->get_results($sql_puntajes_dimension);

        if (!empty($puntajes_dimension)) {
            $output .= '<h5>Puntajes por Dimensiones:</h5>';
            $output .= '<div class="table-responsive">';
            $output .= '<table class="table">';
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Nombre Dimension</th>';
            $output .= '<th>Puntaje</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            foreach ($puntajes_dimension as $dimension) {
                $output .= '<tr>';
                $output .= '<td>' . $dimension->Nombre_Dimension . '</td>';
                $output .= '<td>' . $dimension->Puntaje_Dimension . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
        } else {
            $output .= '<p>No hay puntajes por dimensiones para este resultado.</p>';
        }

        $output .= '</div>';
        $output .= '<div class="modal-footer">';
        $output .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
    }

    // Salida del contenido generado
    echo $output;
    } else {
    // Manejo si no hay resultados encontrados
    $output = '<p>No se encontraron resultados para el correo proporcionado.</p>';
    echo $output;
    }

    wp_die(); // Terminar la ejecución del script WordPress
    }

    add_action('wp_ajax_consultar_historial', 'consultar_historial_ajax');
    add_action('wp_ajax_nopriv_consultar_historial', 'consultar_historial_ajax');