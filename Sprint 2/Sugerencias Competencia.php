// Función para mostrar la página de "Condicionales" del test
    function mi_plugin_test_condicionales_page() {
    if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
    }

    // Incluye la librería de Font Awesome de manera oficial
    echo '<script src="https://kit.fontawesome.com/1947a7b519.js" crossorigin="anonymous"></script>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'tests';

    // Obtén el ID del test desde la URL
    $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

    // Verifica si el test existe
    $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $test_id));

    if (!$test) {
    echo "No se encontró el test con ID $test_id.";
    return;
    }

    // Imprime el título de la página de condicionales
    echo "<div class='wrap'>";
    echo "<h2>Condicionales del Test \"{$test->titulo}\"</h2>";

    echo "<form id='formulario-condicionales'>"; // Agregar un identificador al formulario
    echo "<h3>Texto Condicional Puntaje: </h3>"; // Etiqueta "Texto Condicional Puntaje"

    // Campo de texto de WordPress sin pestañas "Visual" y "HTML"
    $content = ''; // Contenido inicial vacío
    $editor_id = 'descripcion'; // ID del editor
    $settings = array(
    'media_buttons' => false, // Botones multimedia deshabilitados
    'textarea_name' => 'descripcion', // Nombre del campo
    'editor_height' => 200, // Altura del campo de texto (ajustar según tus necesidades)
    'quicktags' => false, // Desactiva también los botones de edición rápida
    );
    wp_editor($content, $editor_id, $settings);

    // Etiqueta y dropdown para seleccionar el elemento asociado al texto condicional
    echo "<h4>Selecciona qué elemento tendrá asociado el Texto Condicional:</h4>";
    echo "<label for='elemento_asociado'>Selecciona el elemento:</label>";
    echo "<select name='elemento_asociado' id='elemento_asociado'>";
    echo "<option value='dimensiones'>Dimensiones</option>";
    echo "<option value='categorias'>Categorías</option>";
    echo "</select>";

    // Etiqueta y dropdown para seleccionar el segundo elemento asociado al texto condicional con un margen a la izquierda
    echo "<label for='segundo_elemento_asociado' style='margin-left: 10px;'>Selecciona la Dimensión: </label>";
    echo "<select name='segundo_elemento_asociado' id='segundo_elemento_asociado'>"; // Aplicar margen
    echo "<option value='opcion1'>Opción 1</option>";
    echo "<option value='opcion2'>Opción 2</option>";
    echo "</select>";

    echo "<h4>Selecciona el rango de calificación en el que aparecerá el mensaje:</h4>";

    echo "<label for='rango_inicial'>Rango inicial:</label>";
    echo "<input type='number' name='rango_inicial' id='rango_inicial' min='0' placeholder='0'>";

    echo "<label for='rango_final' style='margin-left: 10px;'>Rango final:</label>";
    echo "<input type='number' name='rango_final' id='rango_final' min='0' placeholder='100'>";
    echo "<br><br>";

    // Script JavaScript para cambiar el texto del segundo label según la selección del primer dropdown
    echo "<script>
    document.getElementById('elemento_asociado').addEventListener('change', function() {
    var label = document.querySelector('label[for=\"segundo_elemento_asociado\"]');
    var selectedValue = this.value;

    if (selectedValue === 'categorias') {
        label.textContent = 'Selecciona la Categoría: ';
        } else if (selectedValue === 'dimensiones') {
            label.textContent = 'Selecciona la Dimensión: ';
        }
        });
        </script>";

        // Campo para el nombre del link
        echo "<label for='nombre_link'>Nombre del Link: </label>";
        echo "<input type='text' name='nombre_link' id='nombre_link' placeholder='Nombre del enlace'>";

        // Campo para el enlace/link con margen a la izquierda
        echo "<label for='link' style='margin-left: 10px;'>Link: </label>";
        echo "<input type='text' name='link' id='link' placeholder='URL del enlace'>";

    // Etiqueta "Imagen para el Texto Condicional"
        echo "<h4>Imagen para el Texto Condicional: </h4>";

    // Campo para subir imagen de WordPress con estilo personalizado y cambio dinámico de texto
        echo "<label for='imagen' class='upload-image-button gradient-border' id='uploadButton'>";
        echo "<i class='upload-icon fas fa-cloud-upload-alt'></i>Subir Imagen";
        echo "</label>";
        echo "<input type='file' id='imagen' name='imagen' accept='image/*' style='display:none;' onchange='showFileName(this)'>";

    // Etiqueta "Boton para el Texto Condicional"
        echo "<h4>Crea el Texto Condicional: </h4>";

    // Campo "Crear Condicional" con estilo personalizado
        echo "<div class='create-conditional-button gradient-border' id='createConditionalButton'>";
        echo "<i class='upload-icon fas fa-plus'></i>Crear Condicional";
        echo "<input type='button' id='crear_condicional' name='crear_condicional' style='display:none;'>";
        echo "</div>";

    // Script JavaScript para actualizar el segundo dropdown según la selección del primero
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var select = document.getElementById('segundo_elemento_asociado');
                var firstDropdown = document.getElementById('elemento_asociado');
                var label = document.querySelector('label[for="segundo_elemento_asociado"]');

                firstDropdown.addEventListener('change', function() {
        select.innerHTML = ''; // Limpiar opciones previas al cambiar la selección

        var selectedValue = this.value;

        label.textContent = (selectedValue === 'categorias') ? 'Selecciona la Categoría: ' : 'Selecciona la Dimensión: ';

        // Realizar la consulta correspondiente a la base de datos al cambiar la selección
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var data = new URLSearchParams();
        data.append('action', 'get_elements');
        data.append('element_type', selectedValue);
        var testId = <?php echo $test_id; ?>;
        data.append('test_id', testId); // Pasar el test ID aquí

        fetch(ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            data.forEach(function(element) {
                var option = document.createElement('option');
                option.value = element.id; // Ajustar a tu estructura de datos
                option.textContent = element.nombre; // Ajustar a tu estructura de datos
                select.appendChild(option);
            });
        });
    });

                var event = new Event('change');
                firstDropdown.dispatchEvent(event);
            });
        </script>

        <script>
    jQuery(document).ready(function($) {
    $('#createConditionalButton').on('click', function(event) {
    event.preventDefault(); // Detener el comportamiento predeterminado del botón

    var testId = <?php echo $test_id; ?>;
    var elementType = $('#elemento_asociado').val();
    var elementName = $('#segundo_elemento_asociado').val();
    var scoreStart = $('#rango_inicial').val();
    var scoreEnd = $('#rango_final').val();
    var message = $('#descripcion_ifr').contents().find('body').html(); // Obtener el contenido del editor de texto

    var fileInput = $('#imagen')[0];
    var formData = new FormData(); // Crear un objeto FormData para enviar los datos

    var nombreLink = $('#nombre_link').val(); // Obtener el valor del campo "Nombre del Link"
    var link = $('#link').val(); // Obtener el valor del campo "Link"

    // Agregar los datos al FormData
    formData.append('action', 'save_conditional_data'); // Acción para guardar los datos en la base de datos
    formData.append('test_id', testId);
    formData.append('element_type', elementType);
    formData.append('element_name', elementName);
    formData.append('score_start', scoreStart);
    formData.append('score_end', scoreEnd);
    formData.append('message', message);
    formData.append('imagen', fileInput.files[0]); // Agregar el archivo de imagen al FormData
    formData.append('nombre_link', nombreLink); // Agregar el valor del campo "Nombre del Link"
    formData.append('link', link); // Agregar el valor del campo "Link"

    // Realizar la petición AJAX para guardar los datos en la base de datos
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log("Los datos se han guardado correctamente.");
            console.log("Respuesta del servidor: " + response);
            updateConditionalButton(); // Llama a la función para cambiar el botón después del éxito de la petición AJAX

            // Restablecer los campos después de enviar los datos
            $('#descripcion_ifr').contents().find('body').html(''); // Restablece el contenido del editor de texto
            $('#rango_inicial').val(''); // Limpia el campo de rango inicial
            $('#rango_final').val(''); // Limpia el campo de rango final
            $('#nombre_link').val(''); // Limpia el campo "Nombre del Link"
            $('#link').val(''); // Limpia el campo "Link"
        },
        error: function(error) {
            console.log("Hubo un error al guardar los datos.");
            console.log("Error: " + JSON.stringify(error));
        }
    });

    // Resto del código para manejar otros datos
    });
    });
    </script>
        <?php

    // Cierre del formulario
        echo "</form>";

    echo "</div>"; // Cierra el contenedor wrap

    // Llama a la función JavaScript para el cambio dinámico del botón
    mi_plugin_custom_scripts();
    }

    add_action('wp_ajax_get_elements', 'get_elements_callback');
    add_action('wp_ajax_nopriv_get_elements', 'get_elements_callback');

    function get_elements_callback() {
    global $wpdb;

    $element_type = $_POST['element_type'];

    // Obtener el test ID desde la solicitud POST
    $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;

    $table_name = ($element_type === 'categorias') ? $wpdb->prefix . 'test_categories' : $wpdb->prefix . 'test_dimensions';

    // Obtener los nombres de las categorías o dimensiones según el tipo de elemento seleccionado y el test ID
    $column_name = ($element_type === 'categorias') ? 'nombre_categoria' : 'nombre_dimension';
    $results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, $column_name AS nombre 
        FROM $table_name 
        WHERE test_id = %d",
        $test_id
    )
    );

    wp_send_json($results);
    wp_die();
    }

    add_action('wp_ajax_save_conditional_data', 'save_conditional_data');
    add_action('wp_ajax_nopriv_save_conditional_data', 'save_conditional_data');

    function save_conditional_data() {
    global $wpdb;

    // Recibir los datos del FormData
    $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
    $element_type = isset($_POST['element_type']) ? sanitize_text_field($_POST['element_type']) : '';
    $element_name = isset($_POST['element_name']) ? sanitize_text_field($_POST['element_name']) : '';
    $score_start = isset($_POST['score_start']) ? intval($_POST['score_start']) : 0;
    $score_end = isset($_POST['score_end']) ? intval($_POST['score_end']) : 0;
    $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : '';
    $nombre_link = isset($_POST['nombre_link']) ? sanitize_text_field($_POST['nombre_link']) : ''; // Obtener el nombre del link
    $link = isset($_POST['link']) ? esc_url($_POST['link']) : ''; // Obtener el link
    $imagen = ''; // Variable para almacenar el nombre de la imagen

    // Verificar si se subió una imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
    $uploaded_file = $_FILES['imagen'];
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

    if ($movefile && empty($movefile['error'])) {
    // La imagen se subió correctamente, $movefile['file'] contiene la ruta del archivo subido
    $imagen = $movefile['file'];
    } else {
    // Hubo un error al subir la imagen
    status_header(400);
    echo 'Hubo un error al subir la imagen: ' . esc_html($movefile['error']);
    wp_die();
    }
    }

    // Obtener la fecha actual
    $current_date = current_time('mysql');

    // Insertar datos en la tabla
    $table_name = $wpdb->prefix . 'test_mensajes_condicionales';
    $wpdb->insert(
    $table_name,
    array(
    'test_id' => $test_id,
    'tipo_elemento' => $element_type,
    'id_elemento' => $element_name,
    'rango_inicial' => $score_start,
    'rango_final' => $score_end,
    'mensaje' => $message,
    'nombre_link' => $nombre_link, // Guardar el nombre del link en la base de datos
    'link' => $link, // Guardar el link en la base de datos
    'imagen' => $imagen, // Guardar el nombre o ruta de la imagen en la base de datos
    'fecha_realizacion' => $current_date // Guardar la fecha actual en la base de datos
    )
    );

    // Responder con un mensaje de éxito o error
    if ($wpdb->last_error) {
    status_header(400);
    echo 'Hubo un error al guardar los datos en la base de datos: ' . esc_html($wpdb->last_error);
    } else {
    echo 'Los datos se han guardado correctamente en la base de datos.';
    }

    wp_die(); // Importante para terminar la ejecución
    }


    function mi_plugin_custom_scripts() {
    // Incluye la librería de Font Awesome de manera oficial
    echo '<script src="https://kit.fontawesome.com/1947a7b519.js" crossorigin="anonymous"></script>';
    // Script JavaScript para cambiar dinámicamente el texto y el estilo del botón
    echo "<script>
    function showFileName(input) {
    var label = document.getElementById('uploadButton');
    var fileName = input.value.split('\\\\').pop(); // Obtiene solo el nombre del archivo sin la ruta
    if (fileName) {
    label.style.backgroundImage = 'linear-gradient(to right, #FF4081, #9C27B0)'; // Degradado de rojo a morado
    label.innerHTML = \"<i class='upload-icon fas fa-check'></i>Imagen Cargada\";
    } else {
        label.style.backgroundImage = 'linear-gradient(to right, #4CAF50, #2196F3)'; // Degradado original de verde a azul
        label.innerHTML = \"<i class='upload-icon fas fa-cloud-upload-alt'></i>Subir Imagen\";
    }
    }
    </script>";

    // Script JavaScript para cambiar dinámicamente el texto, el ícono y el estilo del botón "Crear Condicional"
    echo "<script>
    function updateConditionalButton() {
    var createButton = document.getElementById('createConditionalButton');
    createButton.style.backgroundImage = 'linear-gradient(to right, #FF7F00, #ffbb00)'; // Cambia el degradado a tonos naranja más oscuros
    createButton.innerHTML = \"<i class='upload-icon fas fa-check'></i>Condicional Creado\"; // Cambia el ícono y el texto del botón
    }
    </script>";
    }

    // Agregar la página de "Condicionales" al menú de administración
    function mi_plugin_test_condicionales_menu() {
    add_submenu_page(
    null,
    'Condicionales del Test',
    'Condicionales del Test',
    'manage_options',
    'mi-plugin-test-condicionales',
    'mi_plugin_test_condicionales_page'
    );
    }
    add_action('admin_menu', 'mi_plugin_test_condicionales_menu');

    // Agregar el formulario "form-recomendaciones" oculto
    $output .= "<div id='form-recomendaciones' style='display: none;'>";

    $output .= "<div style='margin-top: 10px;'>";
    $output .= "<span style='font-style: italic; font-weight: bold;'>Se ha analizado tu puntaje y tenemos las siguientes recomendaciones: </span>";
    $output .= "<button id='badge-recomendaciones' class='btn btn-outline-info' style='margin-left: 10px;'>";
    $output .= "Recomendaciones <span id='badge-number' class='badge badge-light'>0</span>";
    $output .= "</button>";
    $output .= "</div>";
    $output .= "</div><br>"; // Cerrar el formulario "form-recomendaciones" con un salto de línea

    $output .= "<div id='recomendaciones-container' style='display: none;'>";
    $output .= "<div class='card text-center'>";
    $output .= "<div class='card-header'>";
    $output .= "<ul class='nav nav-tabs' id='myTab' role='tablist'>";
    $output .= "<li class='nav-item'>";
    $output .= "<a class='nav-link active' id='inicio-tab' data-toggle='tab' href='#inicio' role='tab' aria-controls='inicio' aria-selected='true'>Inicio</a>";
    $output .= "</li>";
    $output .= "<li class='nav-item'>";
    $output .= "<a class='nav-link' id='categorias-tab' data-toggle='tab' href='#categorias' role='tab' aria-controls='categorias' aria-selected='false'>Categorías</a>";
    $output .= "</li>";
    $output .= "<li class='nav-item'>";
    $output .= "<a class='nav-link' id='dimensiones-tab' data-toggle='tab' href='#dimensiones' role='tab' aria-controls='dimensiones' aria-selected='false'>Dimensiones</a>";
    $output .= "</li>";
    $output .= "</ul>";
    $output .= "</div>";
    $output .= "<div class='card-body'>";
    $output .= "<div class='tab-content' id='myTabContent'>";
    $output .= "<div class='tab-pane fade show active' id='inicio' role='tabpanel' aria-labelledby='inicio-tab'></div>";
    $output .= "<div class='tab-pane fade' id='categorias' role='tabpanel' aria-labelledby='categorias-tab'></div>";
    $output .= "<div class='tab-pane fade' id='dimensiones' role='tabpanel' aria-labelledby='dimensiones-tab'></div>";
    $output .= "</div>";
    $output .= "</div>";
    $output .= "</div>";
    $output .= "</div><br>";

    function cargar_contenido_recomendaciones() {
        global $wpdb;
    
        $respuestas = isset($_POST['formData']) ? wp_unslash($_POST['formData']) : '';
    
        if (!empty($respuestas)) {
        parse_str($respuestas, $respuesta_array);
        $test_id = isset($respuesta_array['test_id']) ? intval($respuesta_array['test_id']) : 0;
    
        if ($test_id !== 0) {
        $puntaje_categoria_dimension = calcular_puntaje_por_categoria_y_dimension($respuestas);
    
        $table_name_mensajes_condicionales = $wpdb->prefix . 'test_mensajes_condicionales';
        $query = $wpdb->prepare("
            SELECT tipo_elemento, mensaje, imagen, id_elemento, rango_inicial, rango_final, nombre_link, link, fecha_realizacion
            FROM $table_name_mensajes_condicionales
            WHERE test_id = %d", $test_id);
    
        $results = $wpdb->get_results($query);
    
        $contenido_card_categorias = '';
            $contenido_card_dimensiones = '';
    
        if ($results) {
            
            foreach ($results as $result) {
                $puntaje_actual = 0;
    
                if ($result->tipo_elemento === 'categorias') {
                    $categoria_id = $result->id_elemento;
                    $nombre_categoria = array_search($categoria_id, $puntaje_categoria_dimension['ids_por_categoria']);
                    $puntaje_actual = $puntaje_categoria_dimension['puntajes_por_categoria'][$nombre_categoria] ?? 0;
    
                    $card_title = $nombre_categoria;
                } elseif ($result->tipo_elemento === 'dimensiones') {
                    $dimension_id = $result->id_elemento;
                    $nombre_dimension = array_search($dimension_id, $puntaje_categoria_dimension['ids_por_dimension']);
                    $puntaje_actual = $puntaje_categoria_dimension['puntajes_por_dimension'][$nombre_dimension] ?? 0;
    
                    $card_title = $nombre_dimension;
                }
    
                $imagen_url = $result->imagen;
                // Reemplazar la ruta local con la URL pública del sitio
                $base_url = site_url(); // Obtiene la URL base del sitio
                $imagen_url = str_replace(ABSPATH, $base_url . '/', $imagen_url);
    
                $fecha_realizacion = $result->fecha_realizacion;
    
                $card = ''; // Inicializa la variable para cada iteración
                $card .= '<div class="col-md-6 mb-3">';
                $card .= '<div class="card border-info h-100">';
                if (!empty($imagen_url)) {
                    $card .= '<img class="card-img-top" src="' . $imagen_url . '" alt="Card image cap">';
                }
                $card .= '<div class="card-body text-info">';
                $card .= '<h5 class="card-title">' . $card_title . '</h5>';
                $card .= '<p class="card-text">' . $result->mensaje;
    
        // Agregar enlace si el nombre del link y el link están disponibles en la BD
                if (!empty($result->nombre_link) && !empty($result->link)) {
                    $card .= ' <a href="' . esc_url($result->link) . '" class="badge badge-info" target="_blank">' . esc_html($result->nombre_link) . '</a>';
                }
    
                $card .= '</p>';
                $card .= '</div>';
                $card .= '<div class="card-footer border-info">';
                $card .= '<small class="text-muted">Fecha de creación: ' . $fecha_realizacion . '</small>';
                $card .= '</div></div></div>';
    
                if ($puntaje_actual >= $result->rango_inicial && $puntaje_actual <= $result->rango_final) {
        if ($result->tipo_elemento === 'categorias') {
        $contenido_card_categorias .= $card; // Agrega la tarjeta a las categorías
        } elseif ($result->tipo_elemento === 'dimensiones') {
        $contenido_card_dimensiones .= $card; // Agrega la tarjeta a las dimensiones
        }
        }
        }
    
            $contenido_pestañas = "<ul class='nav nav-pills mb-3' id='myTab' role='tablist'>
                    <li class='nav-item'>
                        <a class='nav-link active' id='inicio-tab' data-toggle='pill' href='#inicio' role='tab' aria-controls='inicio' aria-selected='true'>Inicio</a>
                    </li>
                    <li class='nav-item'>
                        <a class='nav-link' id='categorias-tab' data-toggle='pill' href='#categorias' role='tab' aria-controls='categorias' aria-selected='false'>Categorías</a>
                    </li>
                    <li class='nav-item'>
                        <a class='nav-link' id='dimensiones-tab' data-toggle='pill' href='#dimensiones' role='tab' aria-controls='dimensiones' aria-selected='false'>Dimensiones</a>
                    </li>
                </ul>";
    
            $contenido_final = "<div class='tab-content' id='myTabContent'>
        <div class='tab-pane fade show active' id='inicio' role='tabpanel' aria-labelledby='inicio-tab'>
        <div class='card border-info mb-3' style='max-width: 64rem;'>
        <div class='card-header text-info'>Inicio</div>
        <div class='card-body text-info'>
            <h5 class='card-title text-info'>Descripción</h5>
            <p class='card-text'>En las pestañas de categorías y dimensiones podrás ver las recomendaciones según tu puntaje obtenido, junto con un enlace para profundizar en esos temas y con eso mejorar tu nivel en dicha competencia...</p>
        </div>
        </div>
        </div>
        <div class='tab-pane fade' id='categorias' role='tabpanel' aria-labelledby='categorias-tab'>
        <div class='row'>$contenido_card_categorias</div>
        </div>
        <div class='tab-pane fade' id='dimensiones' role='tabpanel' aria-labelledby='dimensiones-tab'>
        <div class='row'>$contenido_card_dimensiones</div>
        </div>
        </div>";
    
        echo $contenido_pestañas . $contenido_final;
    
        } else {
            error_log("No se encontraron mensajes e imágenes en la tabla para el test ID: " . $test_id);
        }
        } else {
        error_log("El ID del test recibido no es válido.");
        }
        } else {
        echo 'No se recibieron datos válidos.';
        }
    
        wp_die();
        }
    
        add_action('wp_ajax_cargar_contenido_recomendaciones', 'cargar_contenido_recomendaciones');
        add_action('wp_ajax_nopriv_cargar_contenido_recomendaciones', 'cargar_contenido_recomendaciones');