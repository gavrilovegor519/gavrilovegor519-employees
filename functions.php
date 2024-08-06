<?php

/* Plugin name: Работники компании */

add_action('init', 'employees_main');

function employees_main()
{
    $taxLabels = [
        'name'              => 'Отделы',
        'singular_name'     => 'Отдел',
        'search_items'      => 'Поиск отделов',
        'all_items'         => 'Все отделы',
        'view_item '        => 'Просмотреть отдел',
        'parent_item'       => 'Родительский отдел',
        'parent_item_colon' => 'Родительский отдел:',
        'edit_item'         => 'Редактировать отдел',
        'update_item'       => 'Обновить отдел',
        'add_new_item'      => 'Добавить отдел',
        'new_item_name'     => 'Новый отдел',
        'menu_name'         => 'Отдел',
        'back_to_items'     => '← Вернуться к отделам',
    ];

    $taxArgs = [
        'public' => true,
        'labels' => $taxLabels,
        'hierarchical' => true,
    ];

    register_taxonomy('department', ['staff'], $taxArgs);


    $labels = array(
        'name' => 'Сотрудники',
        'singular_name' => 'Сотрудник',
        'add_new' => 'Добавить сотрудника',
        'add_new_item' => 'Добавить сотрудника',
        'edit_item' => 'Редактировать сотрудника',
        'new_item' => 'Новый сотрудник',
        'all_items' => 'Все сотрудники',
        'search_items' => 'Искать сотрудников',
        'not_found' =>  'Сотрудников по заданным критериям не найдено.',
        'not_found_in_trash' => 'В корзине нет сотрудников.',
        'menu_name' => 'Сотрудники'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-admin-users',
        'menu_position' => 3,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions')
    );

    register_post_type('staff', $args);
}

add_action('add_meta_boxes', 'staff_add_custom_box');

function staff_add_custom_box()
{
    $screens = ['staff'];
    foreach ($screens as $screen) {
        add_meta_box(
            'staff_metabox',
            'Карточка сотрудника',
            'staff_custom_box_html',
            $screen,
            'normal',
            'high'
        );
    }
}

add_action('post_edit_form_tag', 'staff_post_edit_form_tag');

function staff_post_edit_form_tag($post)
{
    if ($post->post_type === 'staff') {
        echo ' enctype="multipart/form-data"';
    }
}

function staff_custom_box_html($post)
{
    // сначала получаем значения этих полей
    $staff_name = get_post_meta($post->ID, 'employee_name', true);
    $staff_age = get_post_meta($post->ID, 'employee_age', true);
    $staff_phone = get_post_meta($post->ID, 'employee_phone', true);

    wp_nonce_field('gavrilovegor519-employees-' . $post->ID, '_truenonce');

?>
    <label for="image_box">Фото сотрудника</label>
    <input type="file" id="image_box" name="image_box" value="">

    <br />

    <label for="name">Имя сотрудника</label>
    <input type="text" value="<?= esc_attr($staff_name); ?>" id="name" name="name" class="regular-text">

    <br />

    <label for="age">Возраст сотрудника</label>
    <input type="number" value="<?= esc_attr($staff_age); ?>" id="age" name="age" class="regular-text">

    <br />

    <label for="phone">Номер телефона сотрудника</label>
    <input type="tel" value="<?= esc_attr($staff_phone); ?>" id="phone" name="phone" class="regular-text">
<?php
}

add_action('save_post', 'true_save_meta_staff', 10, 2);

function true_save_meta_staff($post_id, $post)
{

    // проверка одноразовых полей
    if (!isset($_POST['_truenonce']) || !wp_verify_nonce($_POST['_truenonce'], 'gavrilovegor519-employees-' . $post->ID)) {
        return $post_id;
    }

    // проверяем, может ли текущий юзер редактировать пост
    $post_type = get_post_type_object($post->post_type);

    if (!current_user_can($post_type->cap->edit_post, $post_id)) {
        return $post_id;
    }

    // ничего не делаем для автосохранений
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // проверяем тип записи
    if (!in_array($post->post_type, array('staff'))) {
        return $post_id;
    }

    if (!empty($_FILES['image_box']['name'])) {
        $supported_types = array('image/jpeg', 'image/png', 'image/webp');

        // Получаем тип файла
        $arr_file_type = wp_check_filetype(basename($_FILES['image_box']['name']));
        $uploaded_type = $arr_file_type['type'];

        // Проверяем тип файла на совместимость
        if (in_array($uploaded_type, $supported_types)) {
            $upload = wp_upload_bits($_FILES['image_box']['name'], null, file_get_contents($_FILES['image_box']['tmp_name']));

            if (isset($upload['error']) && $upload['error'] != 0) {
                error_log($message, 3, $pluginlog);
            } else {
                update_post_meta($post_id, 'employee_photo', $upload['url']);
            }
        } else {
            wp_die("The file type that you've uploaded is not a JPEG/PNG/WebP.");
        }
    }

    if (isset($_POST['name'])) {
        update_post_meta($post_id, 'employee_name', sanitize_text_field($_POST['name']));
    } else {
        delete_post_meta($post_id, 'employee_name');
    }
    if (isset($_POST['phone'])) {
        update_post_meta($post_id, 'employee_phone', sanitize_text_field($_POST['phone']));
    } else {
        delete_post_meta($post_id, 'employee_phone');
    }
    if (isset($_POST['age'])) {
        update_post_meta($post_id, 'employee_age', sanitize_text_field($_POST['age']));
    } else {
        delete_post_meta($post_id, 'employee_age');
    }

    return $post_id;
}

add_shortcode('staff_list', 'staff_list_shortcode');

function staff_list_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'department' => '', 
    ), $atts);

    $args = array(
        'post_type' => 'staff',
        'posts_per_page' => -1, 
        'tax_query' => array(
            array(
                'taxonomy' => 'department',
                'field' => 'slug', 
                'terms' => $atts['department'], 
            ),
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '<div class="staff-list">';
        $output .= '<h2>' . get_term_by('slug', $atts['department'], 'department')->name . '<h2/>'; 
        while ($query->have_posts()) {
            $query->the_post();

            $name = get_post_meta(get_the_ID(), 'employee_name', true);
            $photo = get_post_meta(get_the_ID(), 'employee_photo', true);
            $phone = get_post_meta(get_the_ID(), 'employee_phone', true);
            $age = get_post_meta(get_the_ID(), 'employee_age', true);

            $output .= '<div class="staff-item">';
            $output .= '<h3>' . $name . '</h3>';
            $output .= '<img src="' . $photo . '" alt="' . $name . '" width="300px" />';
            if (!empty($phone)) {
                $output .= '<p>Телефон: ' . esc_html($phone) . '</p>';
            }
            if (!empty($age)) {
                $output .= '<p>Возраст: ' . esc_html($age) . ' лет</p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Нет сотрудников в этом отделе';
    }
}

add_shortcode('departments', 'departments_taxonomies_shortcode');

function departments_taxonomies_shortcode()
{
    $taxonomy = 'department';
        
    // Получаем список разделов таксономии
    $terms = get_terms($taxonomy);
        
    // Создаём HTML для шорткода
    $html = '<ul>';
    foreach ($terms as $term) {
        $html .= '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
    }
    $html .= '</ul>';
        
    // Возвращаем HTML
    return $html;
}

class departments_taxonomies_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array(
            'classname' => 'departments_taxonomies_widget',
            'description' => 'Отделы',
        );
        parent::__construct( 'departments_taxonomies_widget', 'Отделы', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        $taxonomy = 'department';
        $title = $instance[ 'title' ];
        
        // Получаем список разделов таксономии
        $terms = get_terms($taxonomy);
        
        // Создаём HTML для виджета
        $html = $args['before_widget'] . $args['before_title'] . $title . $args['after_title'] . '<ul>';
        foreach ($terms as $term) {
            $html .= '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
        }
        $html .= '</ul>' . $args['after_widget'];
        
        // Возвращаем HTML
        echo $html;
    }
    
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : ''; ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p><?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
        return $instance;
    }
}

class staff_list_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array(
            'classname' => 'staff_list_widget',
            'description' => 'Сотрудники',
        );
        parent::__construct( 'staff_list_widget', 'Сотрудники', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        $title = $instance[ 'title' ];
        
        $args1 = array(
            'post_type' => 'staff',
            'posts_per_page' => 5, // Выводим 5 записей
        );
    
        $query = new WP_Query($args1);
    
        $output = $args['before_widget'] . $args['before_title'] . $title . $args['after_title'];
        if ($query->have_posts()) {
            $output .= '<ul>';
            while ($query->have_posts()) {
                $query->the_post();
    
                $name = get_post_meta(get_the_ID(), 'employee_name', true);
                
                $output .= '<li><a href="' . get_permalink() . '">' . $name . '</a></li>';
            }
            wp_reset_postdata();
            $output .= '</ul>' . $args['after_widget'];
            echo $output;
        } else {
            $output .= '<p>Нет сотрудников<p>' . $args['after_widget'];
            echo $output;
        }
    }
    
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : ''; ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p><?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
        return $instance;
    }
}

function staff_register_widget() {
    register_widget( 'departments_taxonomies_widget' );
    register_widget( 'staff_list_widget' );
}

add_action( 'widgets_init', 'staff_register_widget' );
