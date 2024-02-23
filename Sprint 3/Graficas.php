// Agregar el formulario "form-grafica" oculto
    $output .= "<div id='form-grafica' style='display: none;'>";

    $output .= "<div style='margin-top: 10px;'>";
    $output .= "<span style='font-style: italic; font-weight: bold;'>Revisa tus Puntuaciones en comparación al promedio!!</span>";
    $output .= "<a href='http://localhost/wordpress/graficas/' target='_blank' class='btn btn-outline-success' style='margin-left: 10px;'>Revisar Gráfica</a>";
    $output .= "</div>";

    $output .= "</div>"; // Cerrar el formulario "form-grafica"

    /* Con ayuda del Plugin "wpDataTables" se crean las tablas mediante una consulta SQL las cuales son las siguientes:

    Tabla Dimensiones:

    SELECT 
    dim.nombre_dimension AS Nombre_Dimension,
    dim.puntaje_dimension AS Valor_Dimension,
    pd.puntaje_dimension AS Puntuacion_Usuario,
    (
        SELECT 
            AVG(puntaje_dimension) 
        FROM 
            wp_test_puntajes_dimension pd_avg 
        WHERE 
            pd_avg.dimension_id = dim.id
    ) AS Puntuacion_Promedio_Total
FROM 
    wp_test_dimensions dim
INNER JOIN 
    wp_test_puntajes_dimension pd ON dim.id = pd.dimension_id
WHERE 
    pd.resultado_id = (
        SELECT 
            MAX(resultado_id)
        FROM 
            wp_test_puntajes_dimension
    )
GROUP BY 
    dim.id
ORDER BY 
    dim.id;

    Tabla Categorias:

    SELECT 
    cat.nombre_categoria AS Nombre_Categoria,
    cat.puntaje_categoria AS Puntaje_Categoria,
    pc.puntaje_categoria AS Puntuacion_Usuario,
    (
        SELECT 
            AVG(puntaje_categoria) 
        FROM 
            wp_test_puntajes_categoria pc_avg 
        WHERE 
            pc_avg.categoria_id = cat.id
    ) AS Puntuacion_Promedio_Total,
    dim.nombre_dimension AS Nombre_Dimension
FROM 
    wp_test_categories cat
INNER JOIN 
    wp_test_puntajes_categoria pc ON cat.id = pc.categoria_id
INNER JOIN 
    wp_test_dimensions dim ON cat.dimension_id = dim.id
WHERE 
    pc.resultado_id = (
        SELECT 
            MAX(resultado_id)
        FROM 
            wp_test_puntajes_categoria
    )
GROUP BY 
    cat.id
ORDER BY 
    cat.id;

    El paso siguiente que se realizo fue graficar dichas tablas usando las los siguientes elementos de las tablas:

    Grafico Categorias (Motor HighCharts):

    Nombre Categoria (String)
    Valor Categoria (int)
    Puntuacion Usuario (int)
    Puntuacion promedio (float)

    Grafico Dimensiones (Motor HighCharts):

    Nombre Dimension (String)
    Valor Dimension (int)
    Puntuacion Usuario (int)
    Puntuacion promedio total (float)

    Grafico Categorias Filtradas (Motor ApexCharts):

    Nombre Categoria (String)
    Valor Categoria (int)
    Puntuacion Usuario (int)
    Puntuacion promedio (float)
    */