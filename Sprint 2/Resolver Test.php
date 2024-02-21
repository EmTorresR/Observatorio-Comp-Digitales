
// Obtener todas las preguntas asociadas a este test
$table_name_questions = $wpdb->prefix . 'test_questions';
$questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_questions WHERE categoria_id IN (SELECT id FROM $table_name_categories WHERE test_id = %d)", $test_id));

if (!empty($questions)) {
    $output .= "<h3>Preguntas:</h3>";
    $questionNumber = 1; // Inicializar el contador

     foreach ($questions as $question) {
        $output .= "<p style='font-size: 17px;'><strong>{$questionNumber}. {$question->pregunta}</strong></p>";

        // Obtener las respuestas para esta pregunta
        $table_name_answers = $wpdb->prefix . 'test_answers';
        $answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_answers WHERE pregunta_id = %d", $question->id));

        if (!empty($answers)) {
            $output .= "<ul>";

             foreach ($answers as $answer) {
                // Agregar un botón de radio para seleccionar la respuesta
                $output .= "<li style='margin-bottom: 5px;'><input type='radio' name='respuesta_{$question->id}' value='{$answer->id}'> {$answer->respuesta}</li>";
            }

            $output .= "</ul>";
        } else {
            $output .= "<p>No hay respuestas disponibles para esta pregunta.</p>";
        }
         $questionNumber++; // Incrementar el contador
    }

    // Agrega el botón de enviar al final
    $output .= "<input type='submit' name='calcular_puntaje' value='Calcular Puntaje'>";