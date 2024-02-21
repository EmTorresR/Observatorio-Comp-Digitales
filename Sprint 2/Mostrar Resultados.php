// Función para calcular el puntaje por categoría y dimensión
    function calcular_puntaje_por_categoria_y_dimension($respuestas) {
    global $wpdb;

    $table_name_questions = $wpdb->prefix . 'test_questions';
    $table_name_categories = $wpdb->prefix . 'test_categories';
    $table_name_dimensions = $wpdb->prefix . 'test_dimensions';
    $table_name_answers = $wpdb->prefix . 'test_answers';

    // Divide las respuestas en un array asociativo
    parse_str($respuestas, $respuesta_array);

    $test_id = intval($respuesta_array['test_id']);

    // Inicializa los arrays para almacenar los puntajes por categoría y dimensión
    $puntajes_por_categoria = [];
    $puntajes_por_dimension = [];

    // Recorre el array de respuestas para calcular el puntaje por categoría
    foreach ($respuesta_array as $key => $value) {
        if (strpos($key, 'respuesta_') === 0) {
            $pregunta_id = intval(str_replace('respuesta_', '', $key));

            $pregunta = $wpdb->get_row($wpdb->prepare("SELECT categoria_id FROM $table_name_questions WHERE id = %d", $pregunta_id));

            if ($pregunta) {
                $categoria_id = $pregunta->categoria_id;

        // Obtener el puntaje de la respuesta seleccionada
                $respuesta_id = intval($value);
                $respuesta = $wpdb->get_row($wpdb->prepare("SELECT puntaje_respuesta FROM $table_name_answers WHERE id = %d", $respuesta_id));

                if ($respuesta) {
                    $categoria_info = $wpdb->get_row($wpdb->prepare("SELECT dimension_id, puntaje_categoria FROM $table_name_categories WHERE id = %d", $categoria_id));

                    $puntaje_categoria = $respuesta->puntaje_respuesta * ($categoria_info->puntaje_categoria / 100);

            // Sumar el puntaje de la categoría
                    $puntajes_por_categoria[$categoria_id] = ($puntajes_por_categoria[$categoria_id] ?? 0) + $puntaje_categoria;

                    $dimension_id = $categoria_info->dimension_id;

            // Obtener el puntaje de la dimensión
                    $dimension_info = $wpdb->get_row($wpdb->prepare("SELECT puntaje_dimension FROM $table_name_dimensions WHERE id = %d", $dimension_id));

            // Sumar el puntaje de la dimensión
                    $puntajes_por_dimension[$dimension_id] = ($puntajes_por_dimension[$dimension_id] ?? 0) + $puntaje_categoria * ($dimension_info->puntaje_dimension / 100);
                }
            }
        }
    }

    // Obtén los nombres de las categorías específicas para el test_id
    $categorias = $wpdb->get_results($wpdb->prepare(
        "SELECT id, nombre_categoria FROM $table_name_categories WHERE test_id = %d",
        $test_id
    ));

    // Obtén los nombres de las dimensiones específicas para el test_id
    $dimensiones = $wpdb->get_results($wpdb->prepare(
        "SELECT id, nombre_dimension FROM $table_name_dimensions WHERE test_id = %d",
        $test_id
    ));

    // Construye el resultado
    $resultado = [
        'puntajes_por_categoria' => [],
        'puntajes_por_dimension' => [],
        'ids_por_categoria' => [],
        'ids_por_dimension' => []
    ];

    foreach ($categorias as $categoria) {
        $categoria_id = $categoria->id;
        $nombre_categoria = $categoria->nombre_categoria;
        $puntaje_categoria = $puntajes_por_categoria[$categoria_id] ?? 0;
        $resultado['puntajes_por_categoria'][$nombre_categoria] = $puntaje_categoria;
    $resultado['ids_por_categoria'][$nombre_categoria] = $categoria_id; // Almacena el ID por categoría
    }

    foreach ($dimensiones as $dimension) {
    $dimension_id = $dimension->id;
    $nombre_dimension = $dimension->nombre_dimension;
    $puntaje_dimension = $puntajes_por_dimension[$dimension_id] ?? 0;
    $resultado['puntajes_por_dimension'][$nombre_dimension] = $puntaje_dimension;
    $resultado['ids_por_dimension'][$nombre_dimension] = $dimension_id; // Almacena el ID por dimensión
    }

    $resultado['puntaje_final'] = array_sum($puntajes_por_dimension);

    return $resultado;
    }

    // Agrega el nuevo contenedor de resultados independiente               
    $output .= "<br><div id='tabla-container' style='display: none;'>"; // Contenedor de la tabla oculto inicialmente

    // JavaScript para mostrar el nuevo formulario después de mostrar los puntajes
    $output .= "<script>
    jQuery(document).ready(function($) {
    $('#form-calculo-puntaje').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var form = $(this);

    $.ajax({
    type: 'POST',
    url: '" . admin_url('admin-ajax.php') . "',
    data: {
        action: 'calcular_puntaje',
        formData: formData
    },
    success: function(response) {
        var responseData = JSON.parse(response);
        var badgeNumber = responseData.badgeNumber;

        // Actualizar el número en el badge
        $('#form-recomendaciones').show().find('#badge-number').text(badgeNumber);

       // Construir el HTML para el accordion con clases más ajustadas y ancho limitado
    var accordionHTML = '<div class=\"accordion\" id=\"puntajesAccordion\" style=\"max-width: 600px;\">';

    // Puntaje por categoría
    accordionHTML += '<div class=\"card border-0\">';
    accordionHTML += '<div class=\"card-header bg-light p-1\" id=\"categoriasHeading\">';
    accordionHTML += '<h2 class=\"mb-0\">';
    accordionHTML += '<button class=\"btn btn-link btn-sm collapsed\" type=\"button\" data-toggle=\"collapse\" data-target=\"#categoriasCollapse\" aria-expanded=\"false\" aria-controls=\"categoriasCollapse\">';
    accordionHTML += 'Puntajes por Categorías';
    accordionHTML += '</button>';
    accordionHTML += '</h2>';
    accordionHTML += '</div>';
    accordionHTML += '<div id=\"categoriasCollapse\" class=\"collapse\" aria-labelledby=\"categoriasHeading\">';
    accordionHTML += '<div class=\"card-body p-2\">';
    accordionHTML += '<div class=\"list-group\">';

    // Insertar los puntajes por categoría en el accordion
    $.each(responseData.puntajesCategoria, function(categoria, puntaje) {
    accordionHTML += '<span class=\"list-group-item list-group-item-action py-1\">' + categoria + ': ' + puntaje + '</span>';
    });

    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';

    // Puntaje por dimensión
    accordionHTML += '<div class=\"card border-0\">';
    accordionHTML += '<div class=\"card-header bg-light p-1\" id=\"dimensionesHeading\">';
    accordionHTML += '<h2 class=\"mb-0\">';
    accordionHTML += '<button class=\"btn btn-link btn-sm collapsed\" type=\"button\" data-toggle=\"collapse\" data-target=\"#dimensionesCollapse\" aria-expanded=\"false\" aria-controls=\"dimensionesCollapse\">';
    accordionHTML += 'Puntajes por Dimensiones';
    accordionHTML += '</button>';
    accordionHTML += '</h2>';
    accordionHTML += '</div>';
    accordionHTML += '<div id=\"dimensionesCollapse\" class=\"collapse\" aria-labelledby=\"dimensionesHeading\">';
    accordionHTML += '<div class=\"card-body p-2\">';
    accordionHTML += '<div class=\"list-group\">';

    // Insertar los puntajes por dimensión en el accordion
    $.each(responseData.puntajesDimension, function(dimension, puntaje) {
    accordionHTML += '<span class=\"list-group-item list-group-item-action py-1\">' + dimension + ': ' + puntaje + '</span>';
    });

    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';

    // Puntaje final
    accordionHTML += '<div class=\"card border-0\">';
    accordionHTML += '<div class=\"card-header bg-light p-1\" id=\"puntajeFinalHeading\">';
    accordionHTML += '<h2 class=\"mb-0\">';
    accordionHTML += '<button class=\"btn btn-link btn-sm collapsed\" type=\"button\" data-toggle=\"collapse\" data-target=\"#puntajeFinalCollapse\" aria-expanded=\"false\" aria-controls=\"puntajeFinalCollapse\">';
    accordionHTML += 'Puntaje Final';
    accordionHTML += '</button>';
    accordionHTML += '</h2>';
    accordionHTML += '</div>';
    accordionHTML += '<div id=\"puntajeFinalCollapse\" class=\"collapse\" aria-labelledby=\"puntajeFinalHeading\">';
    accordionHTML += '<div class=\"card-body p-2\">';
    accordionHTML += '<div class=\"list-group\">';
    accordionHTML += '<span class=\"list-group-item list-group-item-action py-1\">Tu puntaje Final en la encuesta fue: ' + responseData.puntajeFinal + '</span>'; // Mensaje con el puntaje final
    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';
    accordionHTML += '</div>';

    accordionHTML += '</div>'; // Cierre de accordion
    accordionHTML += '<br>'; // Salto de línea al final del accordion

    // Insertar el HTML del accordion en #puntajes-container
    $('#puntajes-container').html(accordionHTML);

    