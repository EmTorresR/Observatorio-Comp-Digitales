// Obtener los datos de la encuesta
        var datosEncuesta = responseData.datosEncuesta;

        // Mostrar los datos de la encuesta en #datos-test
        var datosTestHtml = '';
        datosTestHtml += '<span class=\"badge rounded-pill bg-secondary\" style=\"color: white;\">Número de test realizados: ' + datosEncuesta.numTests + '</span>';
        datosTestHtml += '<span class=\"badge rounded-pill bg-secondary\" style=\"color: white; margin-left: 15px;\">Fecha de la última encuesta resuelta: ' + datosEncuesta.lastSurveyDate + '</span>';
        
        $('#datos-test').html(datosTestHtml);
        $('#datos-test').show();

        form.hide();
        $('#formulario-historial').show();
        $('#form-grafica').show();
    }
    });
    });


    function obtener_datos_encuesta() {
    global $wpdb;

    $table_name_results = $wpdb->prefix . 'test_results';

    // Consulta para obtener el número de tests realizados (número de registros en la tabla)
    $count_query = "SELECT COUNT(*) FROM $table_name_results";
    $tests_realizados = $wpdb->get_var($count_query);

    // Consulta para obtener la fecha de la última encuesta resuelta
    $last_survey_date_query = "SELECT MAX(fecha_realizacion) FROM $table_name_results";
    $ultima_encuesta = $wpdb->get_var($last_survey_date_query);

    // Crear un array con los datos obtenidos
    $datos_encuesta = array(
    'numTests' => $tests_realizados, 
    'lastSurveyDate' => $ultima_encuesta, 
    );

    return $datos_encuesta;
    }