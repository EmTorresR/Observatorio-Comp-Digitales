// Funci? para agregar una página de categorías de puntuación
    function mi_plugin_test_categories_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta p?ina.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'tests';
    $table_name_dimensions = $wpdb->prefix . 'test_dimensions';
    $table_name_categories = $wpdb->prefix . 'test_categories';
    $table_name_questions = $wpdb->prefix . 'test_questions'; 
    $table_name_answers = $wpdb->prefix . 'test_answers'; 

    // Obtiene el ID del test desde la URL
    $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

    // Verifica si el test existe
    $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $test_id));

    if (!$test) {
        echo "No se encontr? el test con ID $test_id.";
        return;
    }

    // Procesar la eliminación de una Dimensión si se hace clic en el enlace "Eliminar"
    if (isset($_GET['delete_dimension']) && isset($_GET['_wpnonce'])) {
        $dimension_id = intval($_GET['delete_dimension']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);

        if (wp_verify_nonce($nonce, 'eliminar_dimension_' . $dimension_id)) {
            $wpdb->delete(
            $table_name_dimensions,
            array('id' => $dimension_id),
            array('%d')
        );
        }
    }

    // Obtén los puntajes de las dimensiones existentes
    $existing_dimensions = $wpdb->get_results($wpdb->prepare("SELECT puntaje_dimension FROM $table_name_dimensions WHERE test_id = %d", $test_id));

    // Calcula el puntaje total de las dimensiones existentes
    $total_existing_score = 0;
    foreach ($existing_dimensions as $existing_dimension) {
        $total_existing_score += $existing_dimension->puntaje_dimension;
    }

    // Calcula el puntaje disponible
    $puntaje_disponible = 100 - $total_existing_score;
    if ($puntaje_disponible < 0) {
        $puntaje_disponible = 0;
    }

    // Procesar la creación de una nueva Dimensión
    if (isset($_POST['crear_dimension'])) {
        $nombre_dimension = sanitize_text_field($_POST['nombre_dimension']);
        $puntaje_dimension = intval($_POST['puntaje_dimension']);

    // Verifica si el nuevo puntaje excede 100
        if (($total_existing_score + $puntaje_dimension) > 100) {
        // El nuevo puntaje excede 100, muestra un mensaje de error
            echo "Error: La suma de los puntajes de las dimensiones supera 100. No se puede crear la nueva dimensión.";
        } else {
        // La suma está dentro del límite, procede a insertar la nueva dimensión
            $wpdb->insert(
            $table_name_dimensions,
            array(
                'test_id' => $test_id,
                'nombre_dimension' => $nombre_dimension,
                'puntaje_dimension' => $puntaje_dimension,
            )
        );

        // Recalcula el puntaje disponible después de insertar la nueva dimensión
            $total_existing_score += $puntaje_dimension;
            $puntaje_disponible = 100 - $total_existing_score;
            if ($puntaje_disponible < 0) {
                $puntaje_disponible = 0;
            }
        }
    }

    // Procesar la eliminación de una categoría si se hace clic en el enlace "Eliminar"
    if (isset($_GET['delete_category']) && isset($_GET['_wpnonce'])) {
        $category_id = intval($_GET['delete_category']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);

        if (wp_verify_nonce($nonce, 'eliminar_categoria_' . $category_id)) {
            $wpdb->delete(
                $table_name_categories,
                array('id' => $category_id),
                array('%d')
            );
        }
    }

    // Obtén los puntajes de las categorías existentes por dimensión
    $existing_categories_by_dimension = $wpdb->get_results($wpdb->prepare("SELECT dimension_id, puntaje_categoria FROM $table_name_categories WHERE test_id = %d", $test_id));

    // Crear un array para realizar un seguimiento de los puntajes por dimensión
    $puntaje_por_dimension = array();

    // Inicializa el puntaje por dimensión
    foreach ($existing_categories_by_dimension as $existing_category) {
        $dimension_id = $existing_category->dimension_id;
        $puntaje_categoria = $existing_category->puntaje_categoria;

        if (!isset($puntaje_por_dimension[$dimension_id])) {
            $puntaje_por_dimension[$dimension_id] = 0;
        }

        $puntaje_por_dimension[$dimension_id] += $puntaje_categoria;
    }

    // Procesar la creación de una nueva Categoría
    if (isset($_POST['crear_categoria'])) {
        $nombre_categoria = sanitize_text_field($_POST['nombre_categoria']);
        $puntaje_categoria = intval($_POST['puntaje_categoria']);
        $dimension_id = intval($_POST['dimension_id']);

    // Verifica si el nuevo puntaje excede 100 para su dimensión
        if (!isset($puntaje_por_dimension[$dimension_id])) {
            $puntaje_por_dimension[$dimension_id] = 0;
        }

        if (($puntaje_por_dimension[$dimension_id] + $puntaje_categoria) > 100) {
        // El nuevo puntaje excede 100 para esta dimensión, muestra un mensaje de error
            echo "Error: La suma de los puntajes de las categorías para esta dimensión supera 100. No se puede crear la nueva categoría.";
        } else {
        // La suma está dentro del límite, procede a insertar la nueva categoría
            $wpdb->insert(
                $table_name_categories,
                array(
                    'test_id' => $test_id,
                    'nombre_categoria' => $nombre_categoria,
                    'puntaje_categoria' => $puntaje_categoria,
                    'dimension_id' => $dimension_id,
                )
            );

        // Actualiza el puntaje por dimensión
            $puntaje_por_dimension[$dimension_id] += $puntaje_categoria;

        // Calcula el puntaje disponible después de insertar la nueva categoría
            $puntaje_disponible_categoria = 100 - $puntaje_por_dimension[$dimension_id];
            if ($puntaje_disponible_categoria < 0) {
                $puntaje_disponible_categoria = 0;
            }

        // Imprime el puntaje disponible para esta dimensión
            echo "Puntaje disponible para esta dimensión: $puntaje_disponible_categoria";
        }
    }

    // Procesar la eliminación de una pregunta si se hace clic en el enlace "Eliminar"
    if (isset($_GET['delete_question']) && isset($_GET['_wpnonce'])) {
        $question_id = intval($_GET['delete_question']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);

        if (wp_verify_nonce($nonce, 'eliminar_pregunta_' . $question_id)) {
        // Eliminar la pregunta
            $wpdb->delete(
                $table_name_questions,
                array('id' => $question_id),
                array('%d')
            );
        }
    }

    // Obtener los puntajes de las preguntas existentes por categoría
    $existing_questions_by_category = $wpdb->get_results($wpdb->prepare("SELECT categoria_id, puntaje_pregunta FROM $table_name_questions"));

    // Crear un array para realizar un seguimiento del puntaje por categoría
    $puntaje_por_categoria = array();

    // Inicializar el puntaje por categoría
    foreach ($existing_questions_by_category as $existing_question) {
        $categoria_id = $existing_question->categoria_id;
        $puntaje_pregunta = $existing_question->puntaje_pregunta;

        if (!isset($puntaje_por_categoria[$categoria_id])) {
            $puntaje_por_categoria[$categoria_id] = 0;
        }

        $puntaje_por_categoria[$categoria_id] += $puntaje_pregunta;
    }

    if (isset($_POST['crear_pregunta'])) {
        $categoria_id = intval($_POST['categoria_id']);
        $pregunta = sanitize_text_field($_POST['pregunta']);
        $puntaje_pregunta = intval($_POST['puntaje_pregunta']);

    // Obtener el puntaje de la categoría y la dimensión desde la base de datos
        $result = $wpdb->get_row($wpdb->prepare("SELECT puntaje_categoria, dimension_id FROM $table_name_categories WHERE id = %d", $categoria_id));
        $puntaje_categoria = intval($result->puntaje_categoria);
        $dimension_id = intval($result->dimension_id);

    // Obtén el puntaje total de las preguntas existentes en la categoría
        $total_existing_question_score = $puntaje_por_categoria[$categoria_id];

    // Calcula el puntaje disponible en tiempo real y devuelve también la dimensión
        $puntaje_disponible_pregunta = $puntaje_categoria - $total_existing_question_score;

    // Verifica si el nuevo puntaje excede 100 para la categoría
        if (($total_existing_question_score + $puntaje_pregunta) > 100) {
            echo "Error: La suma de los puntajes de las preguntas supera 100. No se puede crear la nueva pregunta.";
        } else {
            if ($puntaje_disponible_pregunta < 0) {
                $puntaje_disponible_pregunta = 0;
            }

            $wpdb->insert(
                $table_name_questions,
                array(
                    'categoria_id' => $categoria_id,
                    'pregunta' => $pregunta,
                    'puntaje_pregunta' => $puntaje_pregunta,
                    'dimension_id' => $dimension_id,
                )
            );
        }
    }

    // Obtener los puntajes de las respuestas existentes por pregunta
    $existing_answers_by_question = $wpdb->get_results($wpdb->prepare("SELECT pregunta_id, puntaje_respuesta FROM $table_name_answers"));

    // Crear un array para realizar un seguimiento del puntaje por pregunta
    $puntaje_por_pregunta = array();

    // Inicializar el puntaje por pregunta
    foreach ($existing_answers_by_question as $existing_answer) {
        $pregunta_id = $existing_answer->pregunta_id;
        $puntaje_respuesta = $existing_answer->puntaje_respuesta;

        if (!isset($puntaje_por_pregunta[$pregunta_id])) {
            $puntaje_por_pregunta[$pregunta_id] = 0;
        }

        $puntaje_por_pregunta[$pregunta_id] += $puntaje_respuesta;
    }

    // Procesar la creación de una nueva respuesta
    if (isset($_POST['crear_respuesta'])) {
        $pregunta_id = intval($_POST['pregunta_id']);
        $respuesta = sanitize_text_field($_POST['respuesta']);
        $puntaje_respuesta = intval($_POST['puntaje_respuesta']);

    // Obtener el puntaje de la pregunta desde la base de datos
        $pregunta = $wpdb->get_row($wpdb->prepare("SELECT puntaje_pregunta FROM $table_name_questions WHERE id = %d", $pregunta_id));
        $puntaje_pregunta = intval($pregunta->puntaje_pregunta);

    // Verificar que el puntaje de la respuesta no supere el puntaje de la pregunta
        if ($puntaje_respuesta > $puntaje_pregunta) {
            echo "El puntaje de la respuesta no puede ser mayor que el puntaje de la pregunta.";
        } else {
        // Insertar la nueva respuesta
            $wpdb->insert(
                $table_name_answers,
                array(
                    'pregunta_id' => $pregunta_id,
                    'respuesta' => $respuesta,
                    'puntaje_respuesta' => $puntaje_respuesta,
                )
            );

        // Actualizar el puntaje por pregunta
            if (isset($puntaje_por_pregunta[$pregunta_id])) {
                $puntaje_por_pregunta[$pregunta_id] += $puntaje_respuesta;
            } else {
                $puntaje_por_pregunta[$pregunta_id] = $puntaje_respuesta;
            }

        // Imprimir mensaje de éxito o cualquier otra acción necesaria
            echo "La respuesta se ha creado con éxito.";
        }
    }

    // Obtener todas las categor?s asociadas al test
    $categories = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_categories WHERE test_id = %d", $test_id));

    // Obtener todas las preguntas asociadas al test
    $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_questions WHERE categoria_id IN (SELECT id FROM $table_name_categories WHERE test_id = %d)", $test_id));

    // Obtener todas las respuestas asociadas a las preguntas
    $answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_answers WHERE pregunta_id IN (SELECT id FROM $table_name_questions WHERE categoria_id IN (SELECT id FROM $table_name_categories WHERE test_id = %d))", $test_id));

    // Obtener todas las dimensiones asociadas al test
    $dimensions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_dimensions WHERE test_id = %d", $test_id));

    ?>
    <div class="wrap">
        <h2>Componentes del Test "<?php echo esc_html($test->titulo); ?>"</h2>
        
        <h2>Crear Dimensión</h2>
        <form method="post" action="">
            <label for="nombre_dimension">Nombre de la Dimensión:</label>
            <input type="text" name="nombre_dimension" id="nombre_dimension" required>

            <label for="puntaje_dimension">Puntaje de la Dimensión:</label>
            <input type="number" name="puntaje_dimension" id="puntaje_dimension" required placeholder="Puntaje disponible: <?php echo esc_attr($puntaje_disponible); ?>">

            <input type="submit" name="crear_dimension" value="Crear Dimensión">
        </form>

        <h2>Dimensiones Creadas</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nombre de la Dimensión</th>
                    <th>Puntaje de la Dimensión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
             <?php
             foreach ($dimensions as $dimension) {
                $delete_url = wp_nonce_url(
                    admin_url("admin.php?page=mi-plugin-test-categories&test_id=$test_id&delete_dimension=" . $dimension->id),
                    'eliminar_dimension_' . $dimension->id
                );

                echo "<tr>";
                echo "<td>{$dimension->nombre_dimension}</td>";
                echo "<td>{$dimension->puntaje_dimension}</td>";
                echo "<td>";
                echo "<a href='$delete_url' class='delete-dimension'>Eliminar</a>";
                echo "</td>";
                echo "</tr>";
            }
            ?>

        </tbody>
    </table>
    <h2>Crear Categoría</h2>
    <form method="post" action="">
        <label for="dimension_id">Seleccionar Dimensión:</label>
        <select name="dimension_id" id="dimension_id" required>
            <?php
            foreach ($dimensions as $dimension) {
                echo "<option value='{$dimension->id}'>{$dimension->nombre_dimension}</option>";
            }
            ?>
        </select>

        <label for="nombre_categoria">Nombre de la Categoría:</label>
        <input type="text" name="nombre_categoria" id="nombre_categoria" required>

        <label for="puntaje_categoria">Puntaje de la Categoría:</label>
        <input type="number" name="puntaje_categoria" id="puntaje_categoria" placeholder="Puntaje disponible: ">

        <input type="submit" name="crear_categoria" value="Crear Categoría">
    </form>

    <h2>Categorías Creadas</h2>
    <!-- Agrega un selector para filtrar las categorías por dimensión -->
    <label for="filter_dimension">Filtrar por Dimensión:</label>
    <select name="filter_dimension" id="filter_dimension">
        <option value="">Todas las Dimensiones</option>
        <?php
        foreach ($dimensions as $dimension) {
            echo "<option value='{$dimension->id}'>{$dimension->nombre_dimension}</option>";
        }
        ?>
    </select>

    <table class="wp-list-table widefat fixed striped" id="category-table">
        <thead>
            <tr>
                <th>Dimensión</th>
                <th>Nombre de la Categoria</th>
                <th>Puntaje de la Categoria</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($categories as $category) {
                $delete_url = wp_nonce_url(
                    admin_url("admin.php?page=mi-plugin-test-categories&test_id=$test_id&delete_category=" . $category->id),
                    'eliminar_categoria_' . $category->id
                );

            // Consulta SQL para obtener el nombre de la dimensión asociada a esta categoría
                $dimension = $wpdb->get_row($wpdb->prepare("SELECT nombre_dimension FROM $table_name_dimensions WHERE id = %d", $category->dimension_id));

                echo "<tr>";
                echo "<td>{$dimension->nombre_dimension}</td>";
                echo "<td>{$category->nombre_categoria}</td>";
                echo "<td>{$category->puntaje_categoria}</td>";
                echo "<td>";
                echo "<a href='$delete_url' class='delete-category'>Eliminar</a>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <h2>Crear Pregunta</h2>
    <form method="post" action="">
        <label for="categoria_id">Seleccionar Categoría:</label>
        <select name="categoria_id" id="categoria_id" required>
            <?php
            foreach ($categories as $category) {
                echo "<option value='{$category->id}'>{$category->nombre_categoria}</option>";
            }
            ?>
        </select>

        <label for="pregunta">Pregunta:</label>
        <input type="text" name="pregunta" id="pregunta" required>

        <label for="puntaje_pregunta">Puntaje de la Pregunta:</label>
        <input type="number" name="puntaje_pregunta" id="puntaje_pregunta" placeholder="Puntaje disponible: ">

        <input type="submit" name="crear_pregunta" value="Crear Pregunta">
    </form>

    <h2>Preguntas Creadas</h2>
    <!-- Agrega un selector para filtrar las preguntas por categoría -->
    <label for="filter_categoria">Filtrar por Categoría:</label>
    <select name="filter_categoria" id="filter_categoria">
        <option value="">Todas las Categorías</option>
        <?php
        foreach ($categories as $category) {
            echo "<option value='{$category->id}'>{$category->nombre_categoria}</option>";
        }
        ?>
    </select>

    <table class="wp-list-table widefat fixed striped" id="question-table">
        <thead>
            <tr>
                <th>Dimensión</th>
                <th>Categoría</th>
                <th>Pregunta</th>
                <th>Puntaje de la Pregunta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($questions as $question) {
                $categoria = $wpdb->get_row($wpdb->prepare("SELECT nombre_categoria FROM $table_name_categories WHERE id = %d", $question->categoria_id));
                $delete_url = wp_nonce_url(
                    admin_url("admin.php?page=mi-plugin-test-categories&test_id=$test_id&delete_question=" . $question->id),
                    'eliminar_pregunta_' . $question->id
                );

            // Consulta SQL para obtener el nombre de la dimensión asociada a esta pregunta
                $dimension = $wpdb->get_row($wpdb->prepare("SELECT dimension_id FROM $table_name_categories WHERE id = %d", $question->categoria_id));
                $dimension_id = intval($dimension->dimension_id);
                $dimension_data = $wpdb->get_row($wpdb->prepare("SELECT nombre_dimension FROM $table_name_dimensions WHERE id = %d", $dimension_id));
                $dimension_nombre = $dimension_data->nombre_dimension;

                echo "<tr>";
            echo "<td>{$dimension_nombre}</td>"; // Columna de la dimensión
            echo "<td>{$categoria->nombre_categoria}</td>"; // Columna de la categoría
            echo "<td>{$question->pregunta}</td>"; // Columna de la pregunta
            echo "<td>{$question->puntaje_pregunta}</td>"; // Columna del puntaje de la pregunta
            echo "<td>";
            echo "<a href='$delete_url' class='delete-question'>Eliminar</a>"; // Columna de acciones (Eliminar)
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
    </table>

    <h2>Crear Respuesta</h2>
    <form method="post" action="">
    <label for="pregunta_id">Pregunta:</label>
    <select name="pregunta_id" id="pregunta_id" required>
        <?php
        foreach ($questions as $question) {
            echo "<option value='{$question->id}' data-puntaje-maximo='{$question->puntaje_pregunta}'>{$question->pregunta}</option>";
        }
        ?>
    </select>

    <label for="respuesta">Respuesta:</label>
    <input type="text" name="respuesta" id="respuesta" required>

    <label for="puntaje_respuesta">Puntaje de la Respuesta:</label>
    <input type="number" name="puntaje_respuesta" id="puntaje_respuesta" placeholder="Puntaje máximo: ">

    <input type="submit" name="crear_respuesta" value="Crear Respuesta">
    </form>

    <h2>Respuestas Creadas</h2>
    <!-- Agrega un selector para filtrar las respuestas por pregunta -->
    <label for="filter_pregunta">Filtrar por Pregunta:</label>
    <select name="filter_pregunta" id="filter_pregunta">
    <option value="">Todas las Preguntas</option>
    <?php
    foreach ($questions as $question) {
        echo "<option value='{$question->id}'>{$question->pregunta}</option>";
    }
    ?>
    </select>

    <table class="wp-list-table widefat fixed striped" id="answer-table">
    <thead>
        <tr>
            <th>Pregunta</th>
            <th>Respuesta</th>
            <th>Puntaje de la Respuesta</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($answers as $answer) {
            $pregunta = $wpdb->get_row($wpdb->prepare("SELECT pregunta FROM $table_name_questions WHERE id = %d", $answer->pregunta_id));

            echo "<tr data-pregunta-id='{$answer->pregunta_id}'>";
            echo "<td>{$pregunta->pregunta}</td>";
            echo "<td>{$answer->respuesta}</td>";
            echo "<td>{$answer->puntaje_respuesta}</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
    </table>
    </div>
    <script>
        // Confirmaci? antes de eliminar una categor?
    jQuery(document).ready(function($) {
    $('.delete-category').click(function(e) {
        if (!confirm('?Est? seguro de que deseas eliminar esta categor? de puntuaci??')) {
            e.preventDefault();
        }
    });
    });
    </script>
    <script>
    // Confirmación antes de eliminar una dimensión
    jQuery(document).ready(function($) {
    $('.delete-dimension').click(function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta dimensión?')) {
            e.preventDefault();
        }
    });
    });
    </script>

    <script>
    // Confirmación antes de eliminar una pregunta
    jQuery(document).ready(function($) {
    $('.delete-question').click(function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta pregunta?')) {
            e.preventDefault();
        }
    });
    });
    </script>
    <script>
    jQuery(document).ready(function($) {
        // Maneja el evento cuando se agrega una nueva dimensión
    $('#crear_dimension').click(function(e) {
        e.preventDefault();

            // Después de crear la dimensión, vuelve a calcular el puntaje disponible
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'calcular_puntaje_disponible',
                    test_id: <?php echo $test_id; ?>, 
                },
                success: function(response) {
                    // Actualiza el valor del puntaje disponible en el campo de entrada
                    $('#puntaje_dimension').attr('placeholder', 'Puntaje disponible: ' + response);
                },
            });
    });
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    // Obtén una referencia al elemento select
    var dimensionSelect = document.getElementById("dimension_id");

    // Obtén una referencia al elemento de puntaje
    var puntajeInput = document.getElementById("puntaje_categoria");

    // Crear un objeto que mapea las dimensiones a los puntajes disponibles
    var puntajesDisponibles = <?php echo json_encode($puntaje_por_dimension); ?>;

    // Agrega un evento de cambio al select
    dimensionSelect.addEventListener("change", function () {
        var selectedDimension = dimensionSelect.value;
        var puntajeTotal = puntajesDisponibles[selectedDimension] || 0; // 0 si no hay puntaje registrado

        // Calcula el puntaje disponible
        var puntajeDisponible = 100 - puntajeTotal;

        // Actualiza el placeholder del input "puntaje_categoria"
        puntajeInput.placeholder = "Puntaje disponible: " + puntajeDisponible;
    });

    // Dispara el evento de cambio para que el placeholder se actualice cuando la página se carga
    dimensionSelect.dispatchEvent(new Event("change"));
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    // Obtén una referencia al elemento select de categorías
    var categoriaSelect = document.getElementById("categoria_id");

    // Obtén una referencia al elemento de puntaje de pregunta
    var puntajePreguntaInput = document.getElementById("puntaje_pregunta");

    // Crear un objeto que mapea las categorías a los puntajes disponibles
    var puntajesDisponibles = <?php echo json_encode($puntaje_por_categoria); ?>;

    // Agrega un evento de cambio al select de categorías
    categoriaSelect.addEventListener("change", function () {
        var selectedCategoria = categoriaSelect.value;
        var puntajeTotal = puntajesDisponibles[selectedCategoria] || 0; // 0 si no hay puntaje registrado

        // Calcula el puntaje disponible
        var puntajeDisponible = 100 - puntajeTotal;

        // Actualiza el placeholder del input "puntaje_pregunta"
        puntajePreguntaInput.placeholder = "Puntaje disponible: " + puntajeDisponible;
    });

    // Dispara el evento de cambio para que el placeholder se actualice cuando la página se carga
    categoriaSelect.dispatchEvent(new Event("change"));
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    // Obtén una referencia al elemento select
    var preguntaSelect = document.getElementById("pregunta_id");

    // Obtén una referencia al elemento de puntaje
    var puntajeInput = document.getElementById("puntaje_respuesta");

    // Agrega un evento de cambio al select
    preguntaSelect.addEventListener("change", function () {
        var selectedQuestion = preguntaSelect.options[preguntaSelect.selectedIndex];
        var puntajeMaximo = selectedQuestion.getAttribute("data-puntaje-maximo");

        // Actualiza el placeholder del input "puntaje_respuesta"
        puntajeInput.placeholder = "Puntaje máximo: " + puntajeMaximo;
    });

    // Dispara el evento de cambio para que el placeholder se actualice cuando la página se carga
    preguntaSelect.dispatchEvent(new Event("change"));
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    var filterDimensionSelect = document.getElementById("filter_dimension");
    var categoryTable = document.getElementById("category-table").getElementsByTagName("tbody")[0];

    filterDimensionSelect.addEventListener("change", function () {
        var selectedDimensionId = filterDimensionSelect.value;
        var rows = categoryTable.getElementsByTagName("tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var dimensionCell = row.cells[0]; // La primera celda contiene la dimensión

            if (selectedDimensionId === "" || dimensionCell.textContent === filterDimensionSelect.options[filterDimensionSelect.selectedIndex].text) {
                row.style.display = "table-row";
            } else {
                row.style.display = "none";
            }
        }
    });
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    var filterCategoriaSelect = document.getElementById("filter_categoria");
    var questionTable = document.getElementById("question-table").getElementsByTagName("tbody")[0];

    filterCategoriaSelect.addEventListener("change", function () {
        var selectedCategoriaId = filterCategoriaSelect.value;
        var rows = questionTable.getElementsByTagName("tr");

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var categoriaCell = row.cells[1]; // La segunda celda contiene la categoría

            if (selectedCategoriaId === "" || categoriaCell.textContent === filterCategoriaSelect.options[filterCategoriaSelect.selectedIndex].text) {
                row.style.display = "table-row";
            } else {
                row.style.display = "none";
            }
        }
    });
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    var filterPreguntaSelect = document.getElementById("filter_pregunta");
    var answerTable = document.getElementById("answer-table").getElementsByTagName("tbody")[0];

    filterPreguntaSelect.addEventListener("change", function () {
        var selectedPreguntaId = filterPreguntaSelect.value;
        var rows = answerTable.getElementsByTagName("tr");

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var preguntaCell = row.getAttribute("data-pregunta-id");

            if (selectedPreguntaId === "" || preguntaCell === selectedPreguntaId) {
                row.style.display = "table-row";
            } else {
                row.style.display = "none";
            }
        }
    });
    });
    </script>

    <?php
    }

    // Agregar una p?ina de categor?s de puntuaci? al men? de administraci?
    function mi_plugin_test_categories_menu() {
    add_submenu_page(
    null,
    'Categor?s de Puntuaci?',
    'Categor?s de Puntuaci?',
    'manage_options',
    'mi-plugin-test-categories',
    'mi_plugin_test_categories_page'
    );
    }
    add_action('admin_menu', 'mi_plugin_test_categories_menu');

    function crear_tabla_preguntas() {
    global $wpdb;
    $table_name_questions = $wpdb->prefix . 'test_questions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name_questions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    dimension_id mediumint(9) NOT NULL,
    categoria_id mediumint(9) NOT NULL,
    pregunta text NOT NULL,
    puntaje_pregunta mediumint(9) NOT NULL,
    PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    }

    // Llama a la función para crear la tabla de preguntas una vez
    crear_tabla_preguntas();

    function crear_tabla_respuestas() {
    global $wpdb;
    $table_name_answers = $wpdb->prefix . 'test_answers';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name_answers (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    pregunta_id mediumint(9) NOT NULL,
    respuesta text NOT NULL,
    puntaje_respuesta mediumint(9) NOT NULL,
    PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    }
    crear_tabla_respuestas();