<?php


namespace Kviron;

use WP_Query;

class WP_CE
{
    /**
     * @var array
     */
    public static array $options;

    public static string $ajaxUrl;

    public static array $args;

    /**
     * @var array
     */
    public static array $debug = [
        'notice'   => null,
        'warnings' => null,
        'errors'   => null,
        'log'      => [],
    ];

    /**
     * --------------------------------------------------------------------------
     * Метод добавления ошибок
     * --------------------------------------------------------------------------
     */
    protected static function addError($error)
    {
        self::$debug['errors'] .= sprintf('<div class="dwr-error">%s</div>', $error);
    }

    protected static function addLog($log)
    {
        self::$debug['log'] = array_merge(self::$debug['log'], $log);
    }

    /**
     * --------------------------------------------------------------------------
     * Метод отображающий ошибку
     * --------------------------------------------------------------------------
     */
    protected static function vieErrors()
    {
        if (self::$options['debug']) {
            !self::$debug['notice'] ?: printf('<pre><b>Notice:</b> %s</pre>', self::$debug['notice']);
            !self::$debug['errors'] ?: printf('<pre><b>Errors:</b> %s</pre>', self::$debug['errors']);
            !self::$debug['warnings'] ?: printf('<pre><b>Warnings:</b> %s</pre>', self::$debug['warnings']);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Инициализация класса
     * --------------------------------------------------------------------------
     */
    public static function init($args = [])
    {
        self::$options['themeUri']        = $args['themeUri'] ?? get_template_directory_uri();
        self::$options['themePath']       = $args['themePath'] ?? get_template_directory();
        self::$options['templateParts']   = $args['templateParts'] ?? '/template-parts';
        self::$options['itemPath']        = $args['itemPath'] ?? '/items/item-';
        self::$options['themeAssetsPath'] = $args['themeAssetsPath'] ?? '/assets';
        self::$options['themeAssetsUri']  = $args['themeAssetsUri'] ?? '/assets';
        self::$options['componentsDir']   = $args['componentsDir'] ?? '/components';
        self::$options['ajaxUrl']         = admin_url('admin-ajax.php');
        self::$options['debug']           = $args['debug'] ?? false;
    }

    /**
     * --------------------------------------------------------------------------
     * Основной метод подключения шаблона
     * --------------------------------------------------------------------------
     */
    public static function template($file, $args = []): void
    {
        global $wp_query;

        if ($args) {
            extract($args, EXTR_OVERWRITE);
        }

        if ($wp_query->query_vars) {
            extract($wp_query->query_vars, EXTR_SKIP);
        }

        $filePath = preg_replace('|([/]+)|s', '/', self::$options['themePath'] . '/' . $file . '.php');

        if (file_exists($filePath)) {
            require $filePath;
        } else {
            self::addError(sprintf('File - %1s, <b>not found</b>', $filePath));
        }

        foreach ($args as $key => $value) {
            unset(${$key});
        }

        self::vieErrors();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот рукурсивного мерджа массивов
     * --------------------------------------------------------------------------
     */
    private static function _arrayMerge($array1, $array2)
    {
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
    public static function theTemplate($file, $args = [])
    {
        ob_start();
        self::template($file, $args);
        echo ob_get_clean();

        self::vieErrors();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот для получения шаблона
     * --------------------------------------------------------------------------
     */
    public static function getTemplate($file, $args = [])
    {
        ob_start();
        self::template($file, $args);
        return ob_get_clean();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот для вывода шаблона компонентов
     * --------------------------------------------------------------------------
     */
    public static function theComponent($component, $args = [])
    {
        ob_start();
        self::template(self::$options['componentsDir'] . '/' . $component, $args);
        echo ob_get_clean();

        self::vieErrors();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот для получения шаблона компонентов
     * --------------------------------------------------------------------------
     */
    public static function getComponent($component, $args = [])
    {
        ob_start();
        self::template(self::$options['componentsDir'] . '/' . $component, $args);
        return ob_get_clean();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот вывода списка постов с помощью WP_Query
     * --------------------------------------------------------------------------
     */
    public static function thePosts($args = []): void
    {
        global $wp_query;
        $post_type = $args['post_type'] ?? get_post_type();

        if (isset($args['post_type'])) {
            $state = $args['post_type'] == get_post_type() ? 'private' : 'global';
        } else {
            $state = 'private';
        }

        $argsDefault = [
            'post_type'      => $post_type,
            'state'          => $state,
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
                'template'  => self::$options['templateParts'] . self::$options['itemPath'] . $post_type,
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
        ];

        $queryParams = self::_arrayMerge($argsDefault, $args);

        if ($queryParams['state'] === 'private' && $wp_query->have_posts()) {
            ob_start();

            echo $queryParams['container']['start'] ?? null;
            self::loop($wp_query, $queryParams['item']['template'], $queryParams['item']);
            echo $queryParams['container']['end'] ?? null;

            echo ob_get_clean();
        } else if ($queryParams['state'] === 'global') {
            ob_start();

            echo $queryParams['container']['start'] ?? null;
            $query = new WP_Query($queryParams);
            if ($query->have_posts()) {
                self::loop($query, $queryParams['item']['template'], $queryParams['item']);
            }
            echo $queryParams['container']['end'] ?? null;

            echo ob_get_clean();
        }

        $wp_query->reset_postdata();

        if ($queryParams['state'] === 'private' && $queryParams['pagination']['enable']) {
            $pagination = self::createPagination($wp_query, $queryParams['pagination']);
        } elseif ($queryParams['state'] === 'global' && $queryParams['pagination']['enable']) {
            $pagination = self::createPagination($query, $queryParams['pagination']);
        }

        if (isset($pagination)) {
            echo $queryParams['pagination']['wrapper']['start'] ?? null;
            echo $pagination;
            echo $queryParams['pagination']['wrapper']['end'] ?? null;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Метот фильтрации строки
     * --------------------------------------------------------------------------
     */
    public static function filterString(string $string, array $array)
    {

        $keysClear = array_map(function ($keys) {
            return '{$' . $keys . '}';
        }, array_keys($array));

        $bufer = array_combine($keysClear, $array);

        $string = strtr($string, $bufer);
        return $string;
    }

    /**
     * --------------------------------------------------------------------------
     * Метот основного цикла вывода постов
     * --------------------------------------------------------------------------
     */
    public static function loop($query, $template, $args = [])
    {
        $number = 1;
        while ($query->have_posts()) {
            $bufer = $args;
            $query->the_post();
            global $post;

            $bufer['id']     = $post->ID;
            $bufer['number'] = $number;

            foreach ($bufer as $key => $item) {
                if (is_string($item) || is_numeric($item)) {
                    $bufer[$key] = self::filterString($bufer[$key], $bufer);
                }
            }

            $thumbnail  = get_the_post_thumbnail_url($post->ID, $args['thumbnail']['size'] ?? 'large') ?? null;
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
        self::vieErrors();
    }

    /**
     * --------------------------------------------------------------------------
     * Метот создания пагинации для цикла постов
     * --------------------------------------------------------------------------
     */
    public static function createPagination($wp_query = null, $args = array(), $before = '', $after = '')
    {
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

        $default_args = apply_filters('kama_pagenavi_args', $default_args); // чтобы можно было установить свои значения по умолчанию

        $args = array_merge($default_args, $args);

        extract($args);

        $posts_per_page = (int)$wp_query->query_vars['posts_per_page'];
        $paged          = (int)$wp_query->query_vars['paged'];
        $max_page       = $wp_query->max_num_pages;

        //проверка на надобность в навигации
        if ($max_page <= 1) {
            return false;
        }

        if (empty($paged) || $paged == 0) {
            $paged = 1;
        }

        $pages_to_show         = intval($num_pages ?? null);
        $pages_to_show_minus_1 = $pages_to_show - 1;

        $half_page_start = floor($pages_to_show_minus_1 / 2); //сколько ссылок до текущей страницы
        $half_page_end   = ceil($pages_to_show_minus_1 / 2);  //сколько ссылок после текущей страницы

        $start_page = $paged - $half_page_start; //первая страница
        $end_page   = $paged + $half_page_end;   //последняя страница (условно)

        if ($start_page <= 0) {
            $start_page = 1;
        }
        if (($end_page - $start_page) != $pages_to_show_minus_1) {
            $end_page = $start_page + $pages_to_show_minus_1;
        }
        if ($end_page > $max_page) {
            $start_page = $max_page - $pages_to_show_minus_1;
            $end_page   = (int)$max_page;
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
            $out           .= sprintf("<span class='pages'>$text_num_page</span> ", $paged, $max_page);
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
                $out .= '<a href="' . str_replace('___', $i, $link_base) . '" class="' . $link_class . '">' . $i . '</a> ';
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
            $out .= '<a class="last" href="' . str_replace('___', $max_page, $link_base) . '">' . ($last_page_text ?? $max_page) . '</a> ';
        }
        // вперед
        if (isset($next_text) && $paged != $end_page) {
            $out .= '<a class="next" href="' . str_replace('___', ($paged + 1), $link_base) . '">' . $next_text . '</a> ';
        }

        $out .= "</div>" . $after . "\n";

        $out = apply_filters('kama_pagenavi', $out);

        return $out;
    }

    /**
     * --------------------------------------------------------------------------
     * Метот получения типа страницы
     * --------------------------------------------------------------------------
     */
    public static function getPageType(): ?string
    {
        if (is_front_page()) {
            return 'front-page';
        } elseif (is_page()) {
            return 'page';
        } elseif (is_single()) {
            return 'single';
        } elseif (is_archive()) {
            return 'archive';
        }

        return null;
    }
}

WP_CE::init();