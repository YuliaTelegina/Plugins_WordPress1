<?php
/*
 * Reviews Telegina
 *
 * Plugin Name: Reviews Telegina
 */

register_activation_hook(__FILE__, 'activation_telegina');

function activation_telegina(){
//    error_log('Активация произошла успешно');
}

register_deactivation_hook(__FILE__, 'deactivation_telegina');

function deactivation_telegina(){
//    error_log('Деактивация произошла успешно');
}

function add_content_before_comments_table() {
    echo '<div class="button" id="btn_add_custom_reviews">Добавить отзыв</div>';
}
add_action('manage_comments_nav', 'add_content_before_comments_table');


function get_list_car_dealers()
{
    $query_tools = array(
        'post_type' => 'post',
        'posts_per_page' => -1
    );

    $query_dealers = new WP_Query($query_tools);

    if ($query_dealers->have_posts()){
        $car_dealers_html = '';
        while ($query_dealers->have_posts()){
            $query_dealers->the_post();

            $car_dealers_html .= '
                <span class="car_dealer_item"> 
                    <label for="car_dealer_'.get_the_ID() . ' "> 
                        <input type="checkbox" name="car_dealers[]" id="car_dealer_'.get_the_ID() . ' " value="'.get_the_ID() . ':'.get_the_title() . '"> 
                        <span class="car_dealer_title">'.get_the_title() . '</span>
                    </label>               
                </span>
            ';
        }
        wp_reset_postdata();

        $wrapper_car_dealers_html = '
        <div id="wrapper_car_dealers">
             <form id="form_car_dealers">
                <span id="list_car_dealers">'. $car_dealers_html . '</span>
                <button id="anchor_submit" class="button" type="button">Привязать</button>
                <button id="anchor_close" class="button" type="button">Закрыть</button>
            </form>
        </div>';
        return $wrapper_car_dealers_html;

    }
    return '';
}

function create_form_comments(){

    echo '
        <div id="wrapper_form_reviews">
            <div>
                <label for="review_author">
                    <input type="text" name="author" placeholder="Иван Иванов" id="review_author">
                </label>
                
                 <label for="review_email">
                    <input type="text" name="email" placeholder="Ivan@gmail.com" id="review_email">
                </label>
                
                 
                <div id="review_wrapper_stars">
                
                    <label for="rstar_rating_1" >
                        1<input type="radio" name="rstar_rating"  id="rstar_rating_1">
                    </label> 
                    
                    <label for="rstar_rating_2">
                        2<input type="radio" name="rstar_rating"  id="rstar_rating_2">
                    </label> 
                    
                    <label for="rstar_rating_3">
                        3<input type="radio" name="rstar_rating"  id="rstar_rating_3">
                    </label> 
                    
                    <label for="rstar_rating_4">
                        4<input type="radio" name="rstar_rating" id="rstar_rating_4">
                    </label> 
                    
                    <label for="rstar_rating_5">
                        5<input type="radio" name="rstar_rating"  id="rstar_rating_5">
                    </label> 
                </div>
                
                 <label for="review_date">
                    <input type="date" name="date" placeholder="01.01.2025" id="review_date">
                </label>
                
                 <label for="review__text">
                    <textarea name="review_text" id="review__text" placeholder="Введите текст" cols="30" rows="10"></textarea>
                </label>
                
                 <div id="review_car_dealers">
                        <button id="anchor_car_dealer" class="button" type="button" >Привязать к автосалону</button>
                        <input type="hidden" name="car_dealer" id="car_dealer_hidden" value=""> 
                        <span id="list_select_car_dealer"></span>     
                </div>
                <button type="submit" id="send_review" class="button">Создать отзыв</button>
            </div>
        </div>
        
        <script type="text/javascript">
        
            function change_html_form(){
                
                const wrapperForm = document.createElement("form");
                wrapperForm.id = "form_add_review";
                
                // Дотягиваемся до кнопки "Добавить отзыв"
                 let btn_add_custom_reviews = document.getElementById("btn_add_custom_reviews");
                 
                 // Берём блок-обёртку формы отзывов
                 let wrapper_form_reviews = document.getElementById("wrapper_form_reviews");
                 
                 
                let wrapper_html_form = document.getElementById("wrapper_form_reviews").outerHTML;

                document.getElementById("wrapper_form_reviews").remove();
                wrapperForm.innerHTML = wrapper_html_form+"";
                
                 // Берём линию с кнопками
                 let form_comment = document.querySelector( "#comments-form .tablenav.top" );
                 
                 let comments_form = document.querySelector( "#comments-form" );
                 if( comments_form && wrapper_form_reviews ){
                     comments_form.before( wrapperForm );
                 }
                
                 // Вешаем на кнопку "Добавить отзыв" событие "клик"
                 if( btn_add_custom_reviews ){
                     
                     btn_add_custom_reviews.addEventListener( "click", function(){
                         
                         // Добавляем класс active - он позволит открыть блок с формой добавления отзывов
                         wrapper_form_reviews.classList.add( "active" );
                         
                         // Добавляем класс к линии с кнопками
                         form_comment.classList.add("active_form" );
                     } );
                     
                 }
                 
                 document.body.addEventListener("click", function(event) {
                    // Проверяем, является ли целевой элемент кнопкой с id="btn"
                    
                    if (event.target && event.target.id === "btn_add_custom_reviews") {
                        let wrapper_form_reviews_new = document.getElementById("wrapper_form_reviews");
                        wrapper_form_reviews_new.classList.add( "active" );
                    }
                });
                 
                 let form_review = document.getElementById( "form_review" );
                 if( form_review ){  
                    form_review.addEventListener( "submit", function( e ){
                        
                        e.preventDefault();
                        
                        var reviewConnect = new Promise( function(resolve,reject){
                            
                            var ajaxurl = "<?php echo admin_url(\'admin-ajax.php\'); ?>";
                            var ajaxnonce = "<?php echo wp_create_nonce( \'my_ajax_nonce\' ); ?>";
        
                            let formData = new FormData( form_review );
                            var xhr = new XMLHttpRequest();
        
                            formData.append( "ajax_nonce",ajaxnonce );
                            formData.append( "action", "action_review");
        
                            xhr.open("POST", ajaxurl, true);
                            xhr.responseType = "json";
                            xhr.send(formData);
                            
                            resolve(xhr);
                         })
        
                        reviewConnect.then( function( xhr ){
                            xhr.onreadystatechange = function(){
                                if( xhr.readyState == 4 ){
                                    // Здесь мы добавим форму ответа
                                    console.log( xhr.response );
                                }
                            }
                        })
                         
                    } );
                }
            }
            
            function the_click_btn_anchor(){
                document.body.addEventListener("click", function(event) {
                    // Проверяем, является ли целевой элемент кнопкой с id="btn"
                    
                    let wrapper_car_dealers = document.getElementById( "wrapper_car_dealers" );
                    
                    if( wrapper_car_dealers ){
                        if (event.target && event.target.id === "anchor_car_dealer") {
                            if( wrapper_car_dealers.classList !== undefined ){
                                wrapper_car_dealers.classList.add( "open" );
                            }
                        }
                        
                        if (event.target && event.target.id === "anchor_close") {
                            if( wrapper_car_dealers.classList !== undefined && wrapper_car_dealers.classList.contains("open") ){
                                wrapper_car_dealers.classList.remove( "open" );
                            }
                        }
                    }
                });
                
            }
            
            function the_select_dealers(){
                
                let anchor_submit = document.getElementById( "anchor_submit" );
                let form_car_dealers = document.getElementById( "form_car_dealers" );
                
                let wrapper_car_dealers = document.getElementById( "wrapper_car_dealers" );
                let list_select_car_dealer = document.getElementById( "list_select_car_dealer" );
                
                if( anchor_submit ){
                    
                    anchor_submit.addEventListener( "click", function(){
                        
                        let listDealersId = [];
                        let listDealersNames = [];
                        
                        let formData = new FormData(form_car_dealers);
                        
                        for (const [key, value] of formData.entries()) {
                            let splitValue = value.split(":");
                            listDealersId.push(splitValue[0])
                            listDealersNames.push( "<span class=\"review_dealer_name\">"+splitValue[1]+"</span>" )
                        }
                        
                        let strDealersId = listDealersId.join(",");
                        let strDealersNames = listDealersNames.join("");
                        
                        let car_dealer_hidden = document.getElementById( "car_dealer_hidden" );
                        car_dealer_hidden.value = strDealersId;
                        list_select_car_dealer.innerHTML = strDealersNames;
                        
                        if( wrapper_car_dealers.classList !== undefined && wrapper_car_dealers.classList.contains("open") ){
                            wrapper_car_dealers.classList.remove( "open" );
                        }
                        
                    } );
                    
                }
                
            }
        
             window.onload = function(){
                   
                 // Переносим форму и обрабатываем её
                 change_html_form();
                 
                 // Открываем окно выбора дилеров
                 the_click_btn_anchor();
                 
                 // Выбор дилеров и вставка в форму
                 the_select_dealers();
                 
             }   
                
        </script>';

}

add_action('manage_comments_nav', 'create_form_comments',11);

function anchor_form_car_dealers(){
    echo get_list_car_dealers();
}

add_action('in_admin_footer', 'anchor_form_car_dealers');


add_action( 'admin_enqueue_scripts', 'my_scripts_method' );
function my_scripts_method(){
    wp_enqueue_style( 'newcssreviews', plugins_url( 'assets/css/my.css', __FILE__ ));
}

function verif_fields(&$data)
{
    $data['errors'] = array();
    // Получаем данные из POST-запроса
    $author = isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $stars = isset($_POST['rstar_rating']) ? sanitize_text_field($_POST['rstar_rating']) : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
    $review_dealers = isset($_POST['car_dealer']) ? sanitize_textarea_field($_POST['car_dealer']) : '';



    // Проверка длины строки (< 3 символов) и отсутствия опасного кода
    if (strlen($author) < 3 || hasDangerousCode($author)) {
        $data['errors'][] = 'Неккоректное имя автора';
    }
    if (hasDangerousCode($email) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)){
        $data['errors'][] = 'Неккоректный email';
    }
    if(empty($stars) || hasDangerousCode($stars)){
        $data['errors'][] = 'Необходимо указать рейтинг';
    }
    if (!preg_match('/^(0[1-9]|[12][0-9]|3[01])\.(0[1-9]|1[0-2])\.\d{4}$/', $date) || hasDangerousCode($date)){
        $data['errors'][] = 'Необходимо указать дату';
    }
    if (hasDangerousCode($review_text) || empty($review_text)){
        $data['errors'][] = 'Введите текст отзыва';
    }
    if (hasDangerousCode($review_dealers) || empty($review_dealers)){
        $data['errors'][] = 'Необходимо выбрать хотя бы один автосалон';
    }

    if (empty($data['errors'])){
        $data['author'] = $author;
        $data['email'] = $email;
        $data['rating'] = $stars;
        $data['date'] = $date;
        $data['text'] = $review_text;
        $data['dealers'] = $review_dealers;
        return true;
    }
    return false;
}

/**
 * Проверяет строку на наличие потенциально опасного кода
 *
 * @param string $str Проверяемая строка
 * @return bool Возвращает true, если найден опасный код
 */
function hasDangerousCode($str)
{
    // Паттерны для поиска опасного кода
    $patterns = [
        '/<script.*?>.*?<\/script>/is',    // Теги <script>
        '/<\?php.*?\?>/is',                // PHP-код
        '/eval\s*\(/i',                    // Функция eval()
        '/system\s*\(/i',                  // Функция system()
        '/exec\s*\(/i',                    // Функция exec()
        '/shell_exec\s*\(/i',              // Функция shell_exec()
        '/passthru\s*\(/i',                // Функция passthru()
        '/`.*?`/',                        // Обратные кавычки (выполнение команд)
        '/\$_(GET|POST|REQUEST)\[.*?\]/',  // Прямой доступ к суперглобальным массивам
        '/base64_decode\s*\(/i',           // base64_decode
        '/javascript\s*:/i',               // JavaScript-схемы
        '/on[a-z]+\s*=/i',                 // HTML-события (onclick, onload и т.д.)
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $str)) {
            return true;
        }
    }

    return false;
}


add_action('wp_ajax_action_review', 'handle_my_custom_action');
add_action('wp_ajax_nopriv_action_review', 'handle_my_custom_action');



/**
 * Функция для ручного добавления комментария
 *
 * @param int $post_id ID записи
 * @param string $comment_content Текст комментария
 * @param string $author_name Имя автора (необязательно)
 * @param string $author_email Email автора (необязательно)
 * @return int|false ID нового комментария или false при ошибке
 */
function manual_add_comment($post_id, $data) {
    // Проверяем, существует ли пост
    if (!get_post($post_id)) {
        return false;
    }

    // Подготавливаем данные комментария
    $comment_data = array(
        'comment_post_ID'      => $post_id,
        'comment_content'      => $data['text'],
        'comment_author'       => $data['author'],
        'comment_author_email' => $data['email'],
        'comment_date' => date('Y-m-d H-i-s', strtotime($data['date'])),
        'comment_approved'     => 0, // 1 - approved, 0 - pending
        'comment_type'         => '', // Пустое значение для обычных комментариев
    );

    // Вставляем комментарий в базу данных
    $comment_id = wp_insert_comment($comment_data);

    // Обновляем счетчик комментариев
    if ($comment_id) {
        wp_update_comment_count($post_id);
    }

    return $comment_id;
}

function handle_my_custom_action(){

    // Проверка на безопасность по полю ajax_nonce
    if (!check_ajax_referer('my_nonce', 'ajax_nonce', false)) {
        wp_die(json_encode(['status' => 'error', 'message' => 'Неверный nonce']));
    }

    $data = [];

    if (!verif_fields($data)){
        wp_die( json_encode( array( 'status' => false, 'errors' => $data['errors'] ) ) );
    }
    $list_posts = explode(',', $data['dealers']);
    foreach ($list_posts as $post_id){
        manual_add_comment($post_id, $data);
    }

    wp_die( json_encode( array( 'status' => true ) ) );
}
