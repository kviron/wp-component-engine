<?php

class CE
{
    /**
     * @var array $options
     */
    public static $options;

    public static $logs = [];

    /**
     * --------------------------------------------------------------------------
     * Инициализация класса
     * --------------------------------------------------------------------------
     */
    public static function init($args = []) {
        self::$options = require CE_DIR . '/configs/options.php';

        //Установить ссылку до папки темы
        if (function_exists('get_template_directory_uri')) {
            self::$options['theme']['uri'] = get_template_directory_uri();
        }

        //Установить путь до папки темы
        if (function_exists('get_template_directory')) {
            self::$options['theme']['path'] = get_template_directory();
        }

        //Установить путь до ajax url
        if (function_exists('admin_url')) {
            self::$options['ajax_uri'] = admin_url('admin-ajax.php');
        }

        self::$options = self::_arrayMerge(self::$options, $args);

        if (self::$options['vue']) {
            add_action('wp_enqueue_scripts', ['CE', 'load_vue'], 20);
        }
    }

    public static function load_vue()
    {
        wp_enqueue_script('vendors-js', 'https://unpkg.com/vue@/' . self::$options['vue_version'], false, false, true);
    }

    /**
     * --------------------------------------------------------------------------
     * Основной метод подключения шаблона
     * --------------------------------------------------------------------------
     */
    public static function template($file, $args = [], $ext = '.php'): void {
        try {
            global $wp_query;

            if ($wp_query->query_vars) {
                extract($wp_query->query_vars, EXTR_SKIP);
            }

            if ($args) {
                extract($args, EXTR_OVERWRITE);
            }

            $slash = substr($file, 0, 1);
            if ($slash !== '/') {
                $slash = '/';
            } else {
                $slash = null;
            }

            $filePath = preg_replace('|([/]+)|s', '/', self::$options['theme']['path'] . $slash . $file . $ext);

            if (file_exists($filePath)) {
                require $filePath;
            } else {
                $file_path = self::$options['theme']['path'] . $file . $ext;
                throw new \Exception("<div style='font-size: 12px; background-color: #e9e9e9; padding: 5px;'>File - <b>$file_path</b> not found </div><br>");
            }
        } catch (\Exception $error) {
            if (self::$options['debug']) {
                echo $error->getMessage();
            }

            self::$logs[] = $error;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Метот рукурсивного мерджа массивов
     * --------------------------------------------------------------------------
     */
    private static function _arrayMerge($array1, $array2) {
        $bufer = $array1;

        foreach ($array1 as $key => $item) {
            if (is_array($item) && isset($array2[$key])) {
                $bufer[$key] = self::_arrayMerge($item, $array2[$key]);
            } else if (!is_array($item) && isset($array2[$key])) {
                $bufer[$key] = $array2[$key];
            }
        }

        foreach ($array2 as $key => $item) {
            if (!isset($array1[$key])) {
                $bufer[$key] = $item;
            }
        }

        return $bufer;
    }

    /**
     * --------------------------------------------------------------------------
     * Метот для вывода шаблона
     * --------------------------------------------------------------------------
     */
    public static function theTemplate($file, $args = [], $ext = '.php') {
        ob_start();
        self::template($file, $args, $ext);
        echo ob_get_clean();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот для получения шаблона
     * --------------------------------------------------------------------------
     */
    public static function getTemplate($file, $args = [], $ext = '.php') {
        ob_start();
        self::template($file, $args, $ext);
        return ob_get_clean();
    }

    /**
     * --------------------------------------------------------------------------
     * Метод для вывода шаблона компонентов
     * --------------------------------------------------------------------------
     */
    public static function theComponent($component, $args = [], $ext = '.php') {
        ob_start();
        self::template(self::$options['components'] . '/' . $component, $args, $ext);
        echo ob_get_clean();
    }

    public static function vueComponent($path, $args = [], $ext = '.php'){

        // Задаем имя компонента по умолчанию
        if (!isset($args['component_name'])) {
            $array_path = explode('/', $path);
            $args['component_name'] = end($array_path);

            ob_start();
            echo "<div data-vue-component='{$args['component_name']}'>";
            self::template($path, $args, $ext);
            echo '<div>';
            echo ob_get_clean();
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Метод для получения шаблона компонентов
     * --------------------------------------------------------------------------
     */
    public static function getComponent($component, $args = [], $ext = '.php') {
        ob_start();
        self::template(self::$options['components'] . '/' . $component, $args, $ext);
        return ob_get_clean();
    }

    /**
     * --------------------------------------------------------------------------
     * Метод вывода списка постов с помощью WP_Query
     * --------------------------------------------------------------------------
     */
    public static function thePosts($args = []): void {
        global $wp_query;

        if (function_exists('get_post_type')) {
            $post_type = $args['post_type'] ?? get_post_type();
        } else {
            $post_type = null;
        }

        $argsDefault = [
            'post_type'      => $post_type,
            'posts_per_page' => (int)$wp_query->get('posts_per_page') ?? 10,
            'paged'          => (int)$wp_query->get('paged') ?? 1,
            'pagination'     => [
                'enable'          => false,
                'text_num_page'   => '',
                'num_pages'       => 10,
                'step_link'       => 10,
                'dotright_text'   => '…',
                'dotright_text2'  => '…',
                'back_text'       => '« назад',
                'next_text'       => 'вперед »',
                'first_page_text' => '« к началу',
                'last_page_text'  => 'в конец »',
                'container_class' => 'dwr-pagination',
                'link_class'      => 'dwr-pagination__link',
                'wrapper'         => [
                    'start' => '',
                    'end'   => '',
                ]
            ],
            'item'           => [
                'id'        => null,
                'number'    => 1,
                'className' => null,
                'post'      => null,
                'template'  => self::$options['template_parts'] . self::$options['item'] . $post_type,
                'thumbnail' => [
                    'url'  => null,
                    'size' => null,
                ],
                'wrapper'   => [
                    'start' => null,
                    'end'   => null,
                ],
            ],
            'container'      => [
                'start' => null,
                'end'   => null,
            ],
            'no_posts'       => [
                'template' => self::$options['template_parts'] . self::$options['content_none'],
                'message'  => 'Мы не нашли ни одной записи',
            ]
        ];

        $queryParams = self::_arrayMerge($argsDefault, $args);

        try {
            echo $queryParams['container']['start'] ?? null;

            $query = new WP_Query($queryParams);

            if ($query->have_posts()) {
                self::loop($query, $queryParams['item']['template'], $queryParams['item']);
            } else {
                self::theTemplate($queryParams['no_posts']['template'], $queryParams['no_posts']);
            }

            echo $queryParams['container']['end'] ?? null;

            $wp_query->reset_postdata();

            //Создание пагинации
            if ($queryParams['pagination']['enable']) {
                echo $queryParams['pagination']['wrapper']['start'] ?? null;
                echo self::createPagination($query, $queryParams['pagination']);
                echo $queryParams['pagination']['wrapper']['end'] ?? null;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function getPosts($args = []) {
        global $wp_query;

        if (function_exists('get_post_type')) {
            $post_type = get_post_type();
        } else {
            $post_type = null;
        }

        $argsDefault = [
            'post_type'      => $post_type,
            'posts_per_page' => (int)$wp_query->get('posts_per_page') ?? 10,
            'paged'          => (int)$wp_query->get('paged') ?? 1,

        ];

        $queryParams = self::_arrayMerge($argsDefault, $args);

        try {
            return new WP_Query($queryParams);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Метод фильтрации строки
     * --------------------------------------------------------------------------
     */
    public static function filterString($string, $array) {

        $keysClear = array_map(function ($keys) {
            return '{$' . $keys . '}';
        }, array_keys($array));

        $bufer = array_combine($keysClear, $array);

        $string = strtr($string, $bufer);
        return $string;
    }

    /**
     * --------------------------------------------------------------------------
     * Метод основного цикла вывода постов
     * --------------------------------------------------------------------------
     */
    public static function loop($query, $template, $args = []) {
        $number = 1;
        while ($query->have_posts()) {
            $bufer = $args;
            $query->the_post();
            global $post;

            $bufer['id'] = $post->ID;
            $bufer['number'] = $number;

            foreach ($bufer as $key => $item) {
                if (is_string($item) || is_numeric($item)) {
                    $bufer[$key] = self::filterString($bufer[$key], $bufer);
                }
            }

            if (function_exists('get_the_post_thumbnail_url')){
                $thumbnail = get_the_post_thumbnail_url($post->ID, $args['thumbnail']['size'] ?? 'large');
            }

            $itemParams = self::_arrayMerge($bufer, [
                'post'      => $post,
                'thumbnail' => [
                    'url' => $thumbnail ?? null,
                ],
            ]);

            echo $args['wrapper']['start'] ?? null;
            self::theTemplate(
                $template,
                $itemParams
            );
            echo $args['wrapper']['end'] ?? null;
            $number++;
        }
        $query->reset_postdata();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот создания пагинации для цикла постов
     * --------------------------------------------------------------------------
     */
    public static function createPagination($wp_query = null, $args = array(), $before = '', $after = '') {
        if (!$wp_query) {
            global $wp_query;
        }

        // параметры по умолчанию
        $default_args = array(
            // Текст перед пагинацией. {current} - текущая; {last} - последняя (пр. 'Страница {current} из {last}' получим: "Страница 4 из 60" )
            'text_num_page'   => '',
            // сколько ссылок показывать
            'num_pages'       => 10,
            // ссылки с шагом (значение - число, размер шага (пр. 1,2,3...10,20,30). Ставим 0, если такие ссылки не нужны.
            'step_link'       => 10,
            // промежуточный текст "до".
            'dotright_text'   => '…',
            // промежуточный текст "после".
            'dotright_text2'  => '…',
            // текст "перейти на предыдущую страницу". Ставим 0, если эта ссылка не нужна.
            'back_text'       => '« назад',
            // текст "перейти на следующую страницу". Ставим 0, если эта ссылка не нужна.
            'next_text'       => 'вперед »',
            // текст "к первой странице". Ставим 0, если вместо текста нужно показать номер страницы.
            'first_page_text' => '« к началу',
            // текст "к последней странице". Ставим 0, если вместо текста нужно показать номер страницы.
            'last_page_text'  => 'в конец »',
            'container_class' => 'dwr-pagination',
            'link_class'      => 'dwr-pagination__link'
        );

        $default_args = apply_filters(
            'kama_pagenavi_args',
            $default_args
        ); // чтобы можно было установить свои значения по умолчанию

        $args = array_merge($default_args, $args);

        extract($args);

        $posts_per_page = (int)$wp_query->query_vars['posts_per_page'];
        $paged = (int)$wp_query->query_vars['paged'];
        $max_page = $wp_query->max_num_pages;

        //проверка на надобность в навигации
        if ($max_page <= 1) {
            return false;
        }

        if (empty($paged) || $paged == 0) {
            $paged = 1;
        }

        $pages_to_show = intval($num_pages ?? null);
        $pages_to_show_minus_1 = $pages_to_show - 1;

        $half_page_start = floor($pages_to_show_minus_1 / 2); //сколько ссылок до текущей страницы
        $half_page_end = ceil($pages_to_show_minus_1 / 2);  //сколько ссылок после текущей страницы

        $start_page = $paged - $half_page_start; //первая страница
        $end_page = $paged + $half_page_end;   //последняя страница (условно)

        if ($start_page <= 0) {
            $start_page = 1;
        }
        if (($end_page - $start_page) != $pages_to_show_minus_1) {
            $end_page = $start_page + $pages_to_show_minus_1;
        }
        if ($end_page > $max_page) {
            $start_page = $max_page - $pages_to_show_minus_1;
            $end_page = (int)$max_page;
        }

        if ($start_page <= 0) {
            $start_page = 1;
        }

        //выводим навигацию
        $out = '';

        // создаем базу чтобы вызвать get_pagenum_link один раз
        $link_base = str_replace(99999999, '___', get_pagenum_link(99999999));
        $first_url = get_pagenum_link(1);
        if (false === strpos($first_url, '?')) {
            $first_url = user_trailingslashit($first_url);
        }

        $out .= $before . "<div class='$container_class'>\n";

        if (isset($text_num_page)) {
            $text_num_page = preg_replace('!{current}|{last}!', '%s', $text_num_page);
            $out .= sprintf("<span class='pages'>$text_num_page</span> ", $paged, $max_page);
        }
        // назад
        if (isset($back_text) && $paged != 1) {
            $out .= sprintf(
                '<a class="%1s prev" href="%2s">%3s</a>',
                $link_class ?? null,
                (($paged - 1) == 1 ? $first_url : str_replace('___', ($paged - 1), $link_base)),
                $back_text
            );
        }
        // в начало
        if ($start_page >= 2 && $pages_to_show < $max_page) {
            $out .= sprintf(
                '<a class="%1s first" href="%2s">%3s</a>',
                $link_class ?? null,
                $first_url,
                $first_page_text ?? 1
            );
            if (isset($dotright_text) && $start_page != 2) {
                $out .= '<span class="extend">' . $dotright_text . '</span> ';
            }
        }
        // пагинация
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $paged) {
                $out .= '<span class="current">' . $i . '</span> ';
            } elseif ($i == 1) {
                $out .= '<a href="' . $first_url . '" class="' . $link_class . '">' . '1</a> ';
            } else {
                $out .= '<a href="' . str_replace(
                        '___',
                        $i,
                        $link_base
                    ) . '" class="' . $link_class . '">' . $i . '</a> ';
            }
        }

        //ссылки с шагом
        $dd = 0;
        if (isset($step_link) && $end_page < $max_page) {
            for ($i = $end_page + 1; $i <= $max_page; $i++) {
                if ($i % $step_link == 0 && $i !== $num_pages) {
                    if (++$dd == 1 && isset($dotright_text2)) {
                        $out .= '<span class="extend">' . $dotright_text2 . '</span> ';
                    }
                    $out .= '<a href="' . str_replace('___', $i, $link_base) . '">' . $i . '</a> ';
                }
            }
        }
        // в конец
        if ($end_page < $max_page) {
            if ($dotright_text && $end_page != ($max_page - 1)) {
                $out .= '<span class="extend">' . $dotright_text2 . '</span> ';
            }
            $out .= '<a class="last" href="' . str_replace(
                    '___',
                    $max_page,
                    $link_base
                ) . '">' . ($last_page_text ?? $max_page) . '</a> ';
        }
        // вперед
        if (isset($next_text) && $paged != $end_page) {
            $out .= '<a class="next" href="' . str_replace(
                    '___',
                    ($paged + 1),
                    $link_base
                ) . '">' . $next_text . '</a> ';
        }

        $out .= "</div>" . $after . "\n";

        if (function_exists('apply_filters')){
            $out = apply_filters('kama_pagenavi', $out);
        }

        return $out;
    }

    /**
     * --------------------------------------------------------------------------
     * Метот получения типа страницы
     * --------------------------------------------------------------------------
     */
    public static function getPageType(): ?string {
        if (function_exists('is_front_page') && is_front_page()) {
            return 'front-page';
        } elseif (function_exists('is_page') && is_page()) {
            return 'page';
        } elseif (function_exists('is_single') && is_single()) {
            return 'single';
        } elseif (function_exists('is_archive') && is_archive()) {
            return 'archive';
        }

        return null;
    }
}
