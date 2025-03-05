<?php
//заголовок плагина 
/*
Plugin Name: Reviews Plugin 
Plugin URI: http://kd3wyai1.beget.tech/wp-admin/plugins.php
Description: Плагин для оставления отзывов на сайте.
Version: 1.0
Author: Telegina
Author URI: я так понимаю http://kd3wyai1.beget.tech/
License: GPL2
*/


function reviews_plugin_form() { //Определяет функцию reviews_plugin_form(), которая генерирует HTML-код формы для отзывов.
    ob_start();//Включает буферизацию вывода.
//Это значит, что весь HTML-код формы сначала записывается во временную память, а потом возвращается одной строкой.
    ?>
    <form id="reviews_form" method="POST">//id="reviews_form" — задает уникальный идентификатор для формы
        <label for="name">Ваше имя:</label>//создает поле для ввода имени
        <input type="text" name="name" id="name" required>//required - 
        
        <label for="email">Ваш email:</label>
        <input type="email" name="email" id="email" required>
        
        <label for="review">Ваш отзыв:</label>
        <textarea name="review" id="review" required></textarea>
        
        <label for="rating">Оценка:</label>
        <select name="rating" id="rating" required>
            <option value="1">1 - Плохо</option>
            <option value="2">2 - Средне</option>
            <option value="3">3 - Хорошо</option>
            <option value="4">4 - Отлично</option>
            <option value="5">5 - Превосходно</option>
        </select>

        <?php wp_nonce_field('reviews_plugin_action', 'reviews_plugin_nonce'); ?>
        
        <input type="submit" name="submit_review" value="Отправить отзыв">
    </form>
    <?php
    
    if ( isset( $_POST['submit_review'] ) ) {
        reviews_plugin_handle_submission();
    }
    
    return ob_get_clean();
}


// Обработка отправки отзыва
function reviews_plugin_handle_submission() {
    if ( ! isset( $_POST['reviews_plugin_nonce'] ) || 
         ! wp_verify_nonce( $_POST['reviews_plugin_nonce'], 'reviews_plugin_action' ) ) {
        die('Ошибка проверки безопасности!');
    }

    if ( empty($_POST['name']) || empty($_POST['email']) || empty($_POST['review']) || empty($_POST['rating']) ) {
        echo 'Все поля обязательны!';
        return;
    }

    $name   = sanitize_text_field( $_POST['name'] );
    $email  = sanitize_email( $_POST['email'] );
    $review = sanitize_textarea_field( $_POST['review'] );
    $rating = intval( $_POST['rating'] );

    $review_post = array(
        'post_title'   => $name,
        'post_content' => $review,
        'post_status'  => 'publish', // Отзыв сразу публикуется
        'post_type'    => 'review',
        'meta_input'   => array(
            'reviewer_email' => $email,
            'review_rating'  => $rating
        )
    );

    $post_id = wp_insert_post( $review_post );

    if ($post_id) {
        echo 'Спасибо за ваш отзыв!';
    } else {
        echo 'Ошибка при отправке отзыва.';
    }
}

function register_reviews_post_type() {
    register_post_type('review', array(
        'labels' => array(
            'name'          => 'Отзывы',
            'singular_name' => 'Отзыв'
        ),
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'custom-fields'),
        'menu_icon'   => 'dashicons-testimonial'
    ));
}
add_action('init', 'register_reviews_post_type');


// Shortcode для отображения формы и отзывов
function reviews_plugin_shortcode() {
    ob_start();
    reviews_plugin_form();
    reviews_plugin_display_reviews();
    return ob_get_clean();
}

add_shortcode( 'reviews_plugin', 'reviews_plugin_shortcode' );
?>
