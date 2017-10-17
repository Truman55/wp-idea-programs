<?php
/*
Plugin Name: IDEA School Программы и цены
Description: Bla bla.
Version: 1.0
Author: Konstantin Trunov
Author URI: http://truman.pro
*/

define('ENGLISH_COURSE', 101);
define('ROBOTS_COURSE', 110);

include dirname(__FILE__) . '/deactivate.php';

register_activation_hook(__FILE__, 'ids_activate');
register_deactivation_hook(__FILE__, 'ids_deactivate');
add_action('wp_ajax_ids_update_price', 'ids_update_price');
add_action('wp_ajax_ids_update_property_main', 'ids_update_property_main');
add_shortcode('ids_best_courses', 'ids_best_courses');

function ids_activate() {
    $date = '['. date('Y-m-d H:m:s') . ']';
    error_log($date . " -> Плагин активирован\r\n", 3, dirname(__FILE__) . '/wp-idea-errors.log');

    //call global object - wpdb
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS `ids_programs` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` int(10) NOT NULL,
            `is_main` int(10) NOT NULL,
            `price` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $wpdb->query($sql);
}

//create admin menu
add_action('admin_menu', 'ids_admin_menu');

function ids_admin_menu() {
    add_menu_page('Программы и цены', 'Программы и цены', 'manage_options','ids_edit_pages', 'ids_programs_view'  , 'dashicons-format-aside');
    add_action('admin_enqueue_scripts', 'ids_admin_scripts');
}

function ids_admin_scripts($hook) {
    if ($hook != 'toplevel_page_ids_edit_pages') return;
    wp_enqueue_style('ids-style', plugins_url('css/programs.css', __FILE__));
    wp_enqueue_script('ids-script', plugins_url('js/programs.js', __FILE__), array('jquery'));
}

function ids_programs_view() {
    global $wpdb;
    $sql = "SELECT PP.post_title, PM.post_id, IP.price, CONVERT(SUBSTRING_INDEX(IP.price,'-',-1),UNSIGNED INTEGER) as price, IP.is_main FROM `roe0_postmeta` AS PM
            LEFT JOIN `roe0_posts` as PP ON PM.post_id = PP.id 
            LEFT JOIN `ids_programs` as IP ON PM.post_id = IP.page_id
            WHERE PM.meta_key = 'is_course' AND PM.meta_value = 1 AND PP.post_status = 'publish'
            ORDER BY price";
    $data = $wpdb->get_results($sql);

    $html = '<div class="wrap">';
    $html .= '<h2 class="ids_title">Программы и цены</h2>';
    if (empty($data)) {
        $html .= '<p>Программы еще не созданы .</p>';
    }
    if (!empty($data)) {
        $html .= '<table class="ids_table"><thead><tr>
                  <th>Направление</th>
                  <th>Лого</th>
                  <th>Название курса</th>
                  <th style="width: 200px">Цена</th>
                  <th>Показывать на главной</th>
                  </tr></thead><tbody>
                  ';
        foreach ($data as $item) {
            $price = $item->price != null ? $item->price : 0;
            $showOnMain = $item->is_main == '0' || $item->is_main == null ? '' : 'checked';
            $cat = get_post_ancestors($item->post_id);
            $catName = get_the_title($cat[0]);
            $html .='
                 <tr>
                 <td>' . $catName . '</td>
                 <td>' . get_the_post_thumbnail($item->post_id, array(50, 50)) . '</td>
                 <td><a href="' . get_edit_post_link($item->post_id) . '" title="Редактировать описание">'.$item->post_title .'</a></td>
                 <td class="ids_price" title="Редактировать цену">
                    <span>'. $price . '</span>
                    <input type="hidden" name="course_id" value="'.$item->post_id.'">
                    <input type="hidden" name="price" value="'. $price .'">
                 </td>
                 <td data-cat="'.$catName.'"><input type="checkbox" name="is_main" ' . $showOnMain . '></td>   
                 </tr>';
        }

        $html .= '</tbody></table>';
    }

    $html .= '<div class="ids_notice"></div></div>';

    echo $html;
}

function ids_update_price() {
    $data = $_POST['formData'];

    global $wpdb;

    if ( $wpdb->get_var($wpdb->prepare(
        "SELECT `page_id` FROM `ids_programs` WHERE page_id=%s", $data['course_id']
    ))) {
        $wpdb->update('ids_programs',
            array('price' => $data['price']),
            array('page_id' => $data['course_id'])
        );
        wp_send_json(array(
            'result' => 'Данные обновлены'
        ));

    } else {
        if ($wpdb->query($wpdb->prepare(
            "INSERT INTO ids_programs(page_id, is_main, price) VALUES (%s, %s, %s)", $data['course_id'], '0', $data['price']
        ))) {
            wp_send_json(array(
                'result' => 'Данные добавлены'
            ));
        };
    };
}

function ids_update_property_main() {
    $data = $_POST['data'];
    global $wpdb;

    if ( $wpdb->get_var($wpdb->prepare(
        "SELECT `page_id` FROM `ids_programs` WHERE page_id=%s", $data['course_id']
    ))) {
        $wpdb->update('ids_programs',
            array('is_main' => $data['isChecked']),
            array('page_id' => $data['course_id'])
        );
        wp_send_json(array(
            'result' => 'Данные обновлены ' .$data['isChecked']. ' ' .$data['course_id']
        ));

    } else {
        if ($wpdb->query($wpdb->prepare(
            "INSERT INTO ids_programs(page_id, is_main, price) VALUES (%s, %s, %s)", $data['course_id'], $data['isChecked'], '0'
        ))) {
            wp_send_json(array(
                'result' => 'Данные добавлены'
            ));
        };
    };
}

function ids_best_courses($args) {
    $program = null;
    if ($args['ids'] == 'english') {
        $program = ENGLISH_COURSE;
    }

    if($args['ids'] == 'robots') {
        $program = ROBOTS_COURSE;
    }
    global $wpdb;
    $sql = "SELECT * FROM `ids_programs` AS ID 
            LEFT JOIN roe0_posts AS PP ON ID.page_id = PP.id
            WHERE ID.is_main=1";
    $data = $wpdb->get_results($sql);

    $response_html = '<div class="row">
                      <h3>'. $args['text'] .'</h3>';
    $i = 0;
    foreach ($data as $course) {
        $i++;
        $class = 'col-md-6';
        $programClass = $course->post_title == 'Первые механизмы' ? ' programs__course_border' : '';

        if ($i % 3 == 0) {
            $class ='col-md-12';
        }

        $parent = get_post_ancestors($course->page_id);
        $course_img_url = get_the_post_thumbnail_url($course->page_id, array(200, 200));
        if ((int)$parent[0] === $program) {
            $response_html .= '<div class="col-12 ' .$class .' col-xl-4">
                               <div class="programs__course'.$programClass.'" style="background-image: url('.$course_img_url.')"><span class="programs__price">От '.$course->price.' р.</span></div>
                               <p class="programs__course-desc">' .$course->post_title. '</p></div>';
        }
    }

    $response_html .= '<a class="programs__all" href="'.get_permalink($program).'">Смотреть все курсы</a>';
    $response_html .= '</div>';
    return $response_html;
}
