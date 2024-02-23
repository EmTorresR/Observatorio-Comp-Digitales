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

    // Función para procesar la creación de un nuevo test
    function crear_test() {
    if (
    isset($_POST['crear_test']) &&
    wp_verify_nonce($_POST['crear_test_nonce'], 'crear_test_nonce')
    ) {
    $titulo = sanitize_text_field($_POST['titulo']);
        $descripcion = wp_kses_post($_POST['descripcion']); // Permite HTML en la descripción
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tests';

        $wpdb->insert(
            $table_name,
            array(
                'titulo' => $titulo,
                'descripcion' => $descripcion, // Guarda la descripción con formato HTML
                'fecha_creacion' => current_time('mysql', 1),
            )
        );

        // Obtener el ID del test recién creado
        $test_id = $wpdb->insert_id;

        // Redirige a la página de categorías de puntuación para este test
        wp_redirect(admin_url("admin.php?page=mi-plugin-test-categories&test_id=$test_id"));
        exit;
    }
    }
    add_action('admin_post_crear_test', 'crear_test');

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

    // Agregar el formulario "form-grafica" oculto
    $output .= "<div id='form-grafica' style='display: none;'>";

    $output .= "<div style='margin-top: 10px;'>";
    $output .= "<span style='font-style: italic; font-weight: bold;'>Revisa tus Puntuaciones en comparación al promedio!!</span>";
    $output .= "<a href='http://localhost/wordpress/graficas/' target='_blank' class='btn btn-outline-success' style='margin-left: 10px;'>Revisar Gráfica</a>";
    $output .= "</div>";

    $output .= "</div>"; // Cerrar el formulario "form-grafica"
    
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

    $output .= "<div id='formulario-historial' style='display: none;'>"; // Nuevo formulario oculto inicialmente
    $output .= "<div style='font-style: italic; font-weight: bold;'>Quieres consultar tu historial de resultados? Escribe tu correo electrónico y haz clic en el botón</div>";

    $output .= "<form method='post' action='' id='form-consultar-historial'>"; // Identificador único para el formulario de consultar historial

    // Campo para correo electrónico del formulario de historial
    $output .= "<label for='correo_historial'>Correo Electrónico:</label>";
    $output .= "<input type='email' name='correo_historial' id='correo_historial' required>";

    // Botón para consultar historial
    $output .= "<button type='submit' name='consultar_historial' class='btn btn-outline-primary' style='margin-left: 10px;'>Consultar Historial</button>";

    $output .= "</form>";
    $output .= "</div>"; // Cierra el div del nuevo formulario

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

        // Obtener los datos de los promedios y nombres de responseData
    var promediosData = responseData.promedios;

    // Mostrar los datos de promedios y nombres en #promedio-competencias
    var promediosHtml = '';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">Sabías que el puntaje promedio de los usuarios entre (0/100) es ' + promediosData.promedio_puntaje_total + ' !!!</span><br>';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">El promedio en la categoría <i>' + promediosData.nombre_categoria + '</i> (0/' + promediosData.max_puntaje_categoria + ') es aproximadamente ' + promediosData.promedio_puntaje_categoria + '.</span><br>';
    promediosHtml += '<span class=\"badge rounded-pill bg-primary text-white\">En cuestión de dimensiones, la dimensión <i>' + promediosData.nombre_dimension + '</i> (0/' + promediosData.max_puntaje_dimension + ') tiene un promedio que ronda ' + promediosData.promedio_puntaje_dimension + '.</span><br>';

    $('#promedio-competencias').html(promediosHtml);
    $('#promedio-competencias').show();

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

    $('#badge-recomendaciones').on('click', function() {

    // Obtener formData del formulario #form-calculo-puntaje
    var formData = $('#form-calculo-puntaje').serialize();

    $.ajax({
    type: 'POST',
    url: '" . admin_url('admin-ajax.php') . "',
    data: {
        action: 'cargar_contenido_recomendaciones',
        formData: formData // Enviar formData al servidor
    },
    success: function(response) {
        $('#recomendaciones-container').html(response); // Agrega el contenido al contenedor
        $('#recomendaciones-container').show(); // Muestra el contenedor

        // Inicializar las pestañas (tabs)
        $('#myTab a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
    });
    });
    });
    </script>";

                $output .= "</div>"; // Cierra el div principal
            } else {
                $output .= "<p>No hay preguntas disponibles para este test.</p>";
            }

            $output .= "</div>";
        } else {
            $output .= "No se encontró el test con ID $test_id.";
        }

        return $output;
    }
    add_shortcode('mi_test', 'mostrar_test');

    // Desactivar la versión de jQuery que viene con WordPress
    function my_dequeue_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];

    if ($script->deps) { // Comprobar si hay dependencias
        $script->deps = array_diff($script->deps, array('jquery-migrate'));
    }
    }
    }

    add_action('wp_default_scripts', 'my_dequeue_jquery_migrate');

    // Función para cargar Bootstrap y jQuery desde CDN
    function enqueue_bootstrap() {
    wp_enqueue_script('popper-js', 'https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js', array('jquery'), '', true); // Cargar Popper.js desde CDN
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js', array('jquery', 'popper-js'), '', true); // Cargar Bootstrap desde CDN
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css');
    }

    add_action('wp_enqueue_scripts', 'enqueue_bootstrap');

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

    function obtener_numero_badge_segun_puntajes($test_id, $puntaje_categoria_dimension) {
    global $wpdb;

    $table_name_mensajes_condicionales = $wpdb->prefix . 'test_mensajes_condicionales';

    $query = $wpdb->prepare("
    SELECT tipo_elemento, id_elemento, rango_inicial, rango_final 
    FROM $table_name_mensajes_condicionales 
    WHERE test_id = %d", $test_id);

    $results = $wpdb->get_results($query);

    if ($results) {
    $contador_badge = 0;

    foreach ($results as $result) {
        $puntaje_actual = 0;

        if ($result->tipo_elemento === 'categorias') {
            $categoria_id = $result->id_elemento;
            $nombre_categoria = array_search($categoria_id, $puntaje_categoria_dimension['ids_por_categoria']);
            $puntaje_actual = $puntaje_categoria_dimension['puntajes_por_categoria'][$nombre_categoria] ?? 0;

        } elseif ($result->tipo_elemento === 'dimensiones') {
        // Obtener puntaje actual por dimensión
            $dimension_id = $result->id_elemento;
            $nombre_dimension = array_search($dimension_id, $puntaje_categoria_dimension['ids_por_dimension']);
            $puntaje_actual = $puntaje_categoria_dimension['puntajes_por_dimension'][$nombre_dimension] ?? 0;

        }

        if ($puntaje_actual >= $result->rango_inicial && $puntaje_actual <= $result->rango_final) {
            $contador_badge++;
        }
    }

    return $contador_badge;
    } else {
    error_log("No se encontraron resultados en la tabla de mensajes condicionales para el test ID: " . $test_id);
    return 0;
    }
    }

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

    function calcular_puntaje_ajax() {
    $respuestas = $_POST['formData'];
    $puntaje_categoria_dimension = calcular_puntaje_por_categoria_y_dimension($respuestas);

    // Deserializar la cadena en un array asociativo
    parse_str($respuestas, $respuesta_array);

    // Acceder a los datos
    $nombre = sanitize_text_field($respuesta_array['nombre']);
    $correo = sanitize_email($respuesta_array['correo']);
    $test_id = intval($respuesta_array['test_id']);

    // Obtener el número para el badge
    $numero_badge = obtener_numero_badge_segun_puntajes($test_id, $puntaje_categoria_dimension);

    // Guardar el resultado en la base de datos
    guardar_resultados_en_db($test_id, $nombre, $correo, $puntaje_categoria_dimension);

    // Obtener los datos de la encuesta con el test_id específico usando obtener_datos_encuesta()
    $datos_encuesta = obtener_datos_encuesta();

    // Obtener los promedios con el test_id específico usando obtener_promedios()
    $promedios = obtener_promedios($test_id);

    // Construir el array con la información de los mensajes, el número del badge, los datos de la encuesta y los promedios
    $response_data = array(
    'messages' => array(),
    'badgeNumber' => $numero_badge,
    'datosEncuesta' => $datos_encuesta, 
    'promedios' => $promedios, 
    'puntajesCategoria' => $puntaje_categoria_dimension['puntajes_por_categoria'],
    'puntajesDimension' => $puntaje_categoria_dimension['puntajes_por_dimension'],
    'puntajeFinal' => $puntaje_categoria_dimension['puntaje_final']
    );

    // Devolver la respuesta como un objeto JSON
    echo json_encode($response_data);

    wp_die(); // Es importante para terminar la ejecución del script WordPress
    }

    // Nueva función para guardar los resultados en la base de datos
    function guardar_resultados_en_db($test_id, $nombre, $correo, $puntaje_categoria_dimension) {
    global $wpdb;

    $table_name_results = $wpdb->prefix . 'test_results';

    $wpdb->insert(
    $table_name_results,
    array(
    'test_id' => $test_id,
    'nombre' => $nombre,
    'correo' => $correo,
    'puntaje_total' => $puntaje_categoria_dimension['puntaje_final'],
    'fecha_realizacion' => current_time('mysql'),
    )
    );

    // Obtiene el ID del último resultado insertado
    $resultado_id = $wpdb->insert_id;

    // Inserta los datos en la tabla de puntajes por categoría
    $table_name_puntajes_categoria = $wpdb->prefix . 'test_puntajes_categoria';
    foreach ($puntaje_categoria_dimension['puntajes_por_categoria'] as $categoria => $puntaje_categoria) {
    $categoria_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}test_categories WHERE nombre_categoria = %s", $categoria));

    $wpdb->insert(
    $table_name_puntajes_categoria,
    array(
        'resultado_id' => $resultado_id,
        'categoria_id' => $categoria_id,
        'puntaje_categoria' => $puntaje_categoria
    )
    );
    }

    // Inserta los datos en la tabla de puntajes por dimensión
    $table_name_puntajes_dimension = $wpdb->prefix . 'test_puntajes_dimension';
    foreach ($puntaje_categoria_dimension['puntajes_por_dimension'] as $dimension => $puntaje_dimension) {
    $dimension_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}test_dimensions WHERE nombre_dimension = %s", $dimension));

    $wpdb->insert(
    $table_name_puntajes_dimension,
    array(
        'resultado_id' => $resultado_id,
        'dimension_id' => $dimension_id,
        'puntaje_dimension' => $puntaje_dimension
    )
    );
    }
    }

    add_action('wp_ajax_calcular_puntaje', 'calcular_puntaje_ajax');
    add_action('wp_ajax_nopriv_calcular_puntaje', 'calcular_puntaje_ajax');

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

    // Función para cargar los estilos CSS desde un archivo separado
    function cargar_estilos_del_plugin() {
    wp_enqueue_style('estilos-del-plugin', plugins_url('styles.css', __FILE__));
    }
    add_action('admin_enqueue_scripts', 'cargar_estilos_del_plugin');

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