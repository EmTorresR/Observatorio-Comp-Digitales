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

        // Obtener los datos de los promedios y nombres de responseData
    var promediosData = responseData.promedios;

    // Mostrar los datos de promedios y nombres en #promedio-competencias
    var promediosHtml = '';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">Sabías que el puntaje promedio de los usuarios entre (0/100) es ' + promediosData.promedio_puntaje_total + ' !!!</span><br>';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">El promedio en la categoría <i>' + promediosData.nombre_categoria + '</i> (0/' + promediosData.max_puntaje_categoria + ') es aproximadamente ' + promediosData.promedio_puntaje_categoria + '.</span><br>';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">En cuestión de dimensiones, la dimensión <i>' + promediosData.nombre_dimension + '</i> (0/' + promediosData.max_puntaje_dimension + ') tiene un promedio que ronda ' + promediosData.promedio_puntaje_dimension + '.</span><br>';

    $('#promedio-competencias').html(promediosHtml);
    $('#promedio-competencias').show();

    // Definir la función para obtener los promedios de la encuesta con el test_id específico
    function obtener_promedios($test_id) {
    global $wpdb; // Acceder a la instancia de la base de datos de WordPress

    // Nombre de las tablas
    $table_name_results = $wpdb->prefix . 'test_results';
    $table_name_categories = $wpdb->prefix . 'test_categories';
    $table_name_dimensions = $wpdb->prefix . 'test_dimensions';
    $table_name_puntajes_categoria = $wpdb->prefix . 'test_puntajes_categoria';
    $table_name_puntajes_dimension = $wpdb->prefix . 'test_puntajes_dimension';

    // Consulta SQL para obtener el promedio de puntaje_total para un test_id específico
    $sql_puntaje_total = $wpdb->prepare(
    "SELECT AVG(puntaje_total) AS promedio_puntaje_total 
    FROM $table_name_results 
    WHERE test_id = %d",
    $test_id
    );

    // Ejecutar la consulta para obtener el promedio de puntaje_total
    $promedio_puntaje_total = $wpdb->get_var($sql_puntaje_total);

    // Consulta SQL para obtener todos los id de categoría asociados con el test_id actual
    $sql_categories = $wpdb->prepare(
    "SELECT id, nombre_categoria FROM $table_name_categories WHERE test_id = %d",
    $test_id
    );

    // Obtener todos los id y nombres de categorías asociadas con el test_id
    $categories = $wpdb->get_results($sql_categories);

    // Verificar si se encontraron categorías para el test_id
    if ($categories) {
    // Seleccionar aleatoriamente un índice para obtener un id de categoría aleatorio
    $random_category_index = array_rand($categories);
    $random_category_id = $categories[$random_category_index]->id;
    $nombre_categoria = $categories[$random_category_index]->nombre_categoria;

    // Consulta SQL para obtener el promedio de puntaje_categoria para el random_category_id
    $sql_puntaje_categoria = $wpdb->prepare(
    "SELECT AVG(puntaje_categoria) AS promedio_puntaje_categoria 
    FROM $table_name_puntajes_categoria 
    WHERE categoria_id = %d",
    $random_category_id
    );

    // Ejecutar la consulta para obtener el promedio de puntaje_categoria
    $promedio_puntaje_categoria = $wpdb->get_var($sql_puntaje_categoria);
    }

    // Consulta SQL para obtener el puntaje máximo de la categoría (categoria_id) seleccionada aleatoriamente
    $sql_max_puntaje_categoria = $wpdb->prepare(
    "SELECT MAX(puntaje_categoria) AS max_puntaje_categoria 
    FROM $table_name_puntajes_categoria 
    WHERE categoria_id = %d",
    $random_category_id
    );

    // Ejecutar la consulta para obtener el puntaje máximo de la categoría
    $max_puntaje_categoria = $wpdb->get_var($sql_max_puntaje_categoria);

    // Consulta SQL para obtener todos los id de dimensión asociados con el test_id actual
    $sql_dimensions = $wpdb->prepare(
    "SELECT id, nombre_dimension FROM $table_name_dimensions WHERE test_id = %d",
    $test_id
    );

    // Obtener todos los id y nombres de dimensiones asociadas con el test_id
    $dimensions = $wpdb->get_results($sql_dimensions);

    // Verificar si se encontraron dimensiones para el test_id
    if ($dimensions) {
    // Seleccionar aleatoriamente un índice para obtener un id de dimensión aleatorio
    $random_dimension_index = array_rand($dimensions);
    $random_dimension_id = $dimensions[$random_dimension_index]->id;
    $nombre_dimension = $dimensions[$random_dimension_index]->nombre_dimension;

    // Consulta SQL para obtener el promedio de puntaje_dimension para el random_dimension_id
    $sql_puntaje_dimension = $wpdb->prepare(
    "SELECT AVG(puntaje_dimension) AS promedio_puntaje_dimension 
    FROM $table_name_puntajes_dimension 
    WHERE dimension_id = %d",
    $random_dimension_id
    );

    // Ejecutar la consulta para obtener el promedio de puntaje_dimension
    $promedio_puntaje_dimension = $wpdb->get_var($sql_puntaje_dimension);
    }

    // Consulta SQL para obtener el puntaje máximo de la dimensión (dimension_id) seleccionada aleatoriamente
    $sql_max_puntaje_dimension = $wpdb->prepare(
    "SELECT MAX(puntaje_dimension) AS max_puntaje_dimension 
    FROM $table_name_puntajes_dimension 
    WHERE dimension_id = %d",
    $random_dimension_id
    );

    // Ejecutar la consulta para obtener el puntaje máximo de la dimensión
    $max_puntaje_dimension = $wpdb->get_var($sql_max_puntaje_dimension);

    // Verificar si se obtuvieron resultados de categorías y dimensiones
    if (isset($promedio_puntaje_total) && isset($promedio_puntaje_categoria) && isset($promedio_puntaje_dimension)) {
    // Formatear los resultados de los promedios según su tipo (entero o decimal con dos decimales)
    $formatted_promedio_puntaje_total = is_int($promedio_puntaje_total) ? $promedio_puntaje_total : number_format($promedio_puntaje_total, 2);
    $formatted_promedio_puntaje_categoria = is_int($promedio_puntaje_categoria) ? $promedio_puntaje_categoria : number_format($promedio_puntaje_categoria, 2);
    $formatted_promedio_puntaje_dimension = is_int($promedio_puntaje_dimension) ? $promedio_puntaje_dimension : number_format($promedio_puntaje_dimension, 2);

    // Retornar los resultados formateados en un array asociativo
    return array(
    'promedio_puntaje_total' => $formatted_promedio_puntaje_total,
    'nombre_categoria' => $nombre_categoria,
    'promedio_puntaje_categoria' => $formatted_promedio_puntaje_categoria,
    'max_puntaje_categoria' => $max_puntaje_categoria, 
    'nombre_dimension' => $nombre_dimension,
    'promedio_puntaje_dimension' => $formatted_promedio_puntaje_dimension,
    'max_puntaje_dimension' => $max_puntaje_dimension
    );
    }

    // En caso de error o falta de datos, retornar un array vacío o un mensaje de error
    return array();
    }