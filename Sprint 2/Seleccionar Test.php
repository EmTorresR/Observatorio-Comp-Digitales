// Función para mostrar el contenido del test mediante un shortcode
    function mostrar_test($atts) {
        // Obtiene el ID del test del atributo del shortcode
    $atts = shortcode_atts(
    array(
    'id' => 0,
    ),
    $atts,
    'mi_test'
    );

    $test_id = intval($atts['id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'tests';

        // Obtén el test específico según el ID proporcionado
    $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $test_id));

    $output = '';

    if ($test) {
    $output .= "<div class='mi-plugin-test'>";
    $output .= "<h2>{$test->titulo}</h2>";
    $output .= "<p>" . wpautop($test->descripcion) . "</p>";
    $output .= "<p><span class=\"badge rounded-pill bg-dark text-white\">Fecha de Creación de la Encuesta: {$test->fecha_creacion}</span></p>";

$output .= "<form method='post' action='' id='form-calculo-puntaje'>"; // Identificador único para el formulario de cálculo de puntaje

// Agregar sección de información de contacto
$output .= "<h3>Información de Contacto</h3>";

$output .= "<label for='nombre'>Nombre:</label>";
$output .= "<input type='text' name='nombre' id='nombre' required><br>";

$output .= "<label for='correo'>Correo Electrónico:</label>";
$output .= "<input type='email' name='correo' id='correo' required><br>";

$output .= "<input type='hidden' name='test_id' value='$test_id'>";

// Define la tabla de categorías
$table_name_categories = $wpdb->prefix . 'test_categories';

// Define la tabla de dimensiones
$table_name_dimensions = $wpdb->prefix . 'test_dimensions';

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

    $output .= "</form>";

    $output .= "<div id='puntajes-container'></div>"; // Contenedor de puntajes

    $output .= "<div id='datos-test'></div>"; // Contenedor de los datos del test

    $output .= "<div id='promedio-competencias'></div>";// Contenedor de "promedio-competencias" 