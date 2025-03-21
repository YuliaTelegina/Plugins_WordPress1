/**
 * Plugin Name: Simple Testimonials
 * Description: Плагин для управления отзывами с привязкой к автосалонам и выбором типа отзыва.
 * Version: 1.4
 * Author: Telegina
 */

if (!defined('ABSPATH')) {
    exit;
}

// Регистрация типа записей "Testimonial"
function simple_testimonials_register_post_type() {
    $labels = array(
        'name'          => __('Testimonials', 'simple-testimonials'),
        'singular_name' => __('Testimonial', 'simple-testimonials'),
        'menu_name'     => __('Testimonials', 'simple-testimonials'),
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'supports'      => array('title', 'editor', 'thumbnail', 'comments'),
        'menu_icon'     => 'dashicons-testimonial',
        'show_in_rest'  => true,
		'rewrite'       => true,
		'query_var'		=> true,
    );

    register_post_type('testimonial', $args);
}
add_action('init', 'simple_testimonials_register_post_type');

// Добавляем таксономию "Автосалоны"
function simple_testimonials_register_taxonomy() {
    $labels = array(
        'name'          => __('Автосалоны', 'simple-testimonials'),
        'singular_name' => __('Автосалон', 'simple-testimonials'),
        'menu_name'     => __('Автосалоны', 'simple-testimonials'),
    );

    $args = array(
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true, // Позволяет создавать категории
        'show_admin_column' => true, // Показывать колонку в админке
        'show_in_rest'      => true, // Доступно в редакторе Гутенберг
    );

    register_taxonomy('car_dealer', 'testimonial', $args);
}
add_action('init', 'simple_testimonials_register_taxonomy');

// Автоматическое добавление автосалонов при активации плагина
function simple_testimonials_add_default_dealers() {
    // Проверяем, были ли уже добавлены автосалоны
    if (get_option('simple_testimonials_dealers_added') !== '1') {
        $dealers = array(
            'Рольф-Вешки СТО',
            'Автосалон АвтоЛидер',
            'Авто Сургут',
            'Автосалон Декар',
            'АЦ Профсоюз',
            'Автосалон КарПлекс',
            'Автомолл Картель',
            'АЦ Восточный',
            'Автосалон Иртыш',
            'Автосалон КарПлаза',
            'Автосалон Fresh Auto',
            'Инком Авто',
            'Автосалон Автомолл',
            'Автосалон Омск Карс',
            'Автосалон Квант Авто (Автосалон Самара)',
            'Автосалон Сталь авто',
            'Автосалон КарПлекс',
            'Автосалон Sool Авто',
            'Автосалон Ленд Моторс',
            'Автосалон Crystal Motors(г. Барнаул)',
            'Автосалон Сиберия',
        );

        foreach ($dealers as $dealer) {
            // Проверяем, существует ли автосалон с таким названием
            if (!term_exists($dealer, 'car_dealer')) {
                wp_insert_term($dealer, 'car_dealer');
            }
        }

        // Помечаем, что автосалоны были добавлены
        update_option('simple_testimonials_dealers_added', '1');
    }
}
add_action('admin_init', 'simple_testimonials_add_default_dealers');

// Добавление поля "Тип отзыва" в админке
function simple_testimonials_add_review_type_field() {
    add_meta_box(
        'review_type', 
        __('Тип отзыва', 'simple-testimonials'), 
        'simple_testimonials_review_type_field', 
        'testimonial', 
        'side', 
        'default'
    );
}
add_action('add_meta_boxes', 'simple_testimonials_add_review_type_field');

function simple_testimonials_review_type_field($post) {
    $review_type = get_post_meta($post->ID, '_review_type', true);

    $options = array(
        'positive' => __('Положительный', 'simple-testimonials'),
        'negative' => __('Отрицательный', 'simple-testimonials'),
    );
    ?>
    <label for="review_type"><?php _e('Выберите тип отзыва:', 'simple-testimonials'); ?></label><br>
    <select name="review_type" id="review_type" class="widefat">
        <?php foreach ($options as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($review_type, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// Сохраняем тип отзыва
function simple_testimonials_save_review_type($post_id) {
    // Проверяем, если это не авто-сохранение
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

    if ('testimonial' !== get_post_type($post_id)) return $post_id;

    if (isset($_POST['review_type'])) {
        update_post_meta($post_id, '_review_type', sanitize_text_field($_POST['review_type']));
    }

    return $post_id;
}
add_action('save_post', 'simple_testimonials_save_review_type');

// Шорткод для вывода отзывов с автосалонами и типом отзыва
// Шорткод для вывода отзывов с фильтрацией по автосалону и типу отзыва
function simple_testimonials_shortcode($atts) {
    $atts = shortcode_atts(array(
        'count' => 5,
        'dealer' => '',  // Добавим аттрибут для автосалона
    ), $atts);

    $args = array(
        'post_type'      => 'testimonial',
        'posts_per_page' => intval($atts['count']),
        'post_status'    => 'publish', // Добавляем проверку на опубликованные отзывы
    );

    // Если указан автосалон, фильтруем отзывы по таксономии 'car_dealer'
    if (!empty($atts['dealer'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'car_dealer',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($atts['dealer']),
            ),
        );
    }

    $testimonials = new WP_Query($args);

    if ($testimonials->have_posts()) {
        $output = '<div class="testimonials-list">';
        while ($testimonials->have_posts()) {
            $testimonials->the_post();

            // Получаем автосалон отзыва
            $dealers = get_the_terms(get_the_ID(), 'car_dealer');
            $dealer_names = $dealers ? wp_list_pluck($dealers, 'name') : ['Не указан'];
            $dealer_name  = esc_html(implode(', ', $dealer_names));

            // Получаем тип отзыва
            $review_type = get_post_meta(get_the_ID(), '_review_type', true);
            $review_type_label = $review_type === 'positive' ? 'Положительный' : 'Отрицательный';

            $output .= '<div class="testimonial">';
            $output .= '<h3>' . get_the_title() . '</h3>';
            $output .= '<p><strong>Автосалон:</strong> ' . $dealer_name . '</p>';
            $output .= '<p><strong>Тип отзыва:</strong> ' . $review_type_label . '</p>';
            $output .= '<div class="testimonial-content">' . get_the_content() . '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output = '<p>' . __('No testimonials found.', 'simple-testimonials') . '</p>';
    }

    return $output;
}
add_shortcode('testimonials', 'simple_testimonials_shortcode');



if (!defined('ABSPATH')) {
    exit;
}

// Добавляем поле "Тип отзыва" и "Автосалон" в комментарии
function simple_testimonials_comment_fields($fields) {
    $fields['dealer'] = '<p class="comment-form-dealer">
                            <label for="dealer">' . __('Автосалон', 'simple-testimonials') . '</label>
                            <select id="dealer" name="dealer">
                                <option value="Рольф-Вешки СТО">Рольф-Вешки СТО</option>
                                <option value="Автосалон АвтоЛидер">Автосалон АвтоЛидер</option>
                                <option value="Авто Сургут">Авто Сургут</option>
                                <option value="Автосалон Декар">Автосалон Декар</option>
                                <option value="АЦ Профсоюз">АЦ Профсоюз</option>
                                <option value="Автосалон КарПлекс">Автосалон КарПлекс</option>
                                <option value="Автомолл Картель">Автомолл Картель</option>
                                <option value="АЦ Восточный">АЦ Восточный</option>
                                <option value="Автосалон Иртыш">Автосалон Иртыш</option>
                                <option value="Автосалон КарПлаза">Автосалон КарПлаза</option>
                                <option value="Автосалон Fresh Auto">Автосалон Fresh Auto</option>
                                <option value="Инком Авто">Инком Авто</option>
                            </select>
                          </p>';
    $fields['review_type'] = '<p class="comment-form-review-type">
                                <label for="review_type">' . __('Тип отзыва', 'simple-testimonials') . '</label>
                                <select id="review_type" name="review_type">
                                    <option value="positive">Положительный</option>
                                    <option value="negative">Отрицательный</option>
                                </select>
                              </p>';
    return $fields;
}
add_filter('comment_form_default_fields', 'simple_testimonials_comment_fields');

function simple_testimonials_save_comment_meta($comment_id) {
    if (isset($_POST['dealer'])) {
        update_comment_meta($comment_id, 'dealer', sanitize_text_field($_POST['dealer']));
    }
    if (isset($_POST['review_type'])) {
        update_comment_meta($comment_id, 'review_type', sanitize_text_field($_POST['review_type']));
    }
}
add_action('comment_post', 'simple_testimonials_save_comment_meta');

// Отображение автосалона и типа отзыва в комментариях
function simple_testimonials_display_comment_meta($comment_text, $comment) {
    $dealer = get_comment_meta($comment->comment_ID, 'dealer', true);
    $review_type = get_comment_meta($comment->comment_ID, 'review_type', true);
    $review_label = $review_type === 'positive' ? 'Положительный' : 'Отрицательный';

    if ($dealer || $review_type) {
        $meta = '<p><strong>Автосалон:</strong> ' . esc_html($dealer) . '</p>';
        $meta .= '<p><strong>Тип отзыва:</strong> ' . esc_html($review_label) . '</p>';
        return $meta . $comment_text;
    }

    return $comment_text;
}
add_filter('comment_text', 'simple_testimonials_display_comment_meta', 10, 2);
