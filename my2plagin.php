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




// Создание таблицы в базе данных для хранения отзывов при активации плагина
function reviews_plugin_activate() {//будет выполненена при активации плагина.
    global $wpdb;// объект для предоставления доступа к базе данных wordpress - так мы взаимодействуем с базой данных
    
    $table_name = $wpdb->prefix . 'reviews'; // Префикс wp_ у таблиц в базах данных. Типо создаем имя таблицы что б получить правильный префыикс и добавить имя таблицы reviews 
    
    // SQL запрос для создания таблицы
    $charset_collate = $wpdb->get_charset_collate();//возвращает настройки кодировки для базы данных - для правльной работы с текстом и символами
    $sql = "CREATE TABLE $table_name (    
        id mediumint(9) NOT NULL AUTO_INCREMENT, /*уникальный идентификатор записи который делает + 1 для нвого отзыва*/
        name varchar(255) NOT NULL, /* это типо столбец для хранения имени пользователя который оставил отзыв*/
        email varchar(255) NOT NULL, /* это типо столбец для хранения емэил пользователя который оставил отзыв*/
        review text NOT NULL, /* это типо столбец для хранения текста отзыва*/
        rating int NOT NULL,/* это типо столбец для хранения рейтинга например от 1 до 5 */
        created_at datetime DEFAULT CURRENT_TIMESTAMP, /* это типо столбец для хранения времени когда был оставлен отзыв. Будет записываться текущее время*/
        PRIMARY KEY  (id) /* уникальный идентификатор для каждой записи*/
    ) $charset_collate;";

    // Включаем функцию dbDelta для выполнения запроса
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); /* ABSPATH — это предопределенная константа в WordPress, которая указывает на корневую директорию установки WordPress*/
    dbDelta( $sql ); /*  это функция, предоставляемая WordPress, которая предназначена для работы с базой данных, в частности для создания или обновления таблиц. */
}
register_activation_hook( __FILE__, 'reviews_plugin_activate' );  /*это функция WordPress, которая регистрирует функцию, которую нужно выполнить при активации плагина.*/



// Форма для отправки отзыва
function reviews_plugin_form() { 
    ob_start();//буферизация вывода. Это нужно, чтобы собрать HTML-код формы и потом вернуть его в виде строки.

    ?>
    <form id="reviews_form" method="POST">
        <label for="name">Ваше имя:</label>
        <input type="text" name="name" id="name" required>
        
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
        
        <input type="submit" name="submit_review" value="Отправить отзыв"> /*кнопка отправки*/
    </form>
    <?php
    if ( isset( $_POST['submit_review'] ) ) {
        reviews_plugin_handle_submission(); // если окей вызывается это  - ф-я обрабатфывает отзыв
    }
    
    return ob_get_clean(); //Получает весь HTML-код, который был в буфере. очищает буфер
}
