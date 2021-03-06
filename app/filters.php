<?php

namespace App;

/**
 * Display SVG icons in social links menu.
 */
add_filter('walker_nav_menu_start_el', function ($item_output, $item, $depth, $args) {
    if ('social' === $args->theme_location) {
        $svg = SvgIcons::getSocialLinkSvg($item->url);
        if (empty($svg)) {
            $svg = get_theme_svg('link');
        }
        $item_output = str_replace($args->link_after, '</span>' . $svg, $item_output);
    }
    return $item_output;
}, 10, 4);

/**
 * Add dropdown icon if menu item has children.
 */
add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
    if ('primary_navigation' === $args->theme_location) {
        foreach ($item->classes as $value) {
            if ('menu-item-has-children' === $value || 'page_item_has_children' === $value) {
                $title = $title . '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="5" aria-hidden="true" role="img" class="angleDown"><path fill="currentColor" d="M1 0l3 3 3-3 1 1-4 4-4-4z"/></svg>';
            }
        }
    }
    return $title;
}, 10, 4);

/**
 * Display sidebar
 */
add_filter('sage/display_sidebar', function ($display) {
    static $display;

    isset($display) || $display = in_array(true, [
      // The sidebar will be displayed if any of the following return true
      is_single(),
    ]);

    return $display;
});

/**
 * Make Custom Image Sizes Selectable in Admin
 */
add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, array(
        'one_fourth'        => '1/4',
        'one_fourth_square' => '1/4 Square',
        'one_third'         => '1/3',
        'one_third_square'  => '1/3 Square',
        'one_half'          => '1/2',
        'one_half_square'   => '1/2 Square',
        'one'               => '1/1',
    ));
});

/**
 * Disable Gutenberg editor
 */
add_filter('use_block_editor_for_post', '__return_false');

/**
 * Enables the HTTP Strict Transport Security (HSTS) header.
 */
add_action('send_headers', function () {
    header('Strict-Transport-Security: max-age=10886400');
});

/**
 * Proper ob_end_flush() for all levels
 *
 * This replaces the WordPress `wp_ob_end_flush_all()` function
 * with a replacement that doesn't cause PHP notices.
 */
remove_action('shutdown', 'wp_ob_end_flush_all', 1);
add_action('shutdown', function () {
    while (@ob_end_flush());
});

/**
 * Add <body> classes
 */
add_filter('body_class', function (array $classes) {
    /** Add page slug if it doesn't exist */
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }

    /** Add class if sidebar is active */
    if (display_sidebar()) {
        $classes[] = 'sidebar-primary';
    }

    /** Clean up class names for custom templates */
    $classes = array_map(function ($class) {
        return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
    }, $classes);

    return array_filter($classes);
});

/**
 * Add "… Continued" to the excerpt
 */
add_filter('excerpt_more', function () {
    return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
});

/**
 * Template Hierarchy should search for .blade.php files
 */
collect([
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
    'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment', 'embed'
])->map(function ($type) {
    add_filter("{$type}_template_hierarchy", __NAMESPACE__.'\\filter_templates');
});

/**
 * Render page using Blade
 */
add_filter('template_include', function ($template) {
    collect(['get_header', 'wp_head'])->each(function ($tag) {
        ob_start();
        do_action($tag);
        $output = ob_get_clean();
        remove_all_actions($tag);
        add_action($tag, function () use ($output) {
            echo $output;
        });
    });
    $data = collect(get_body_class())->reduce(function ($data, $class) use ($template) {
        return apply_filters("sage/template/{$class}/data", $data, $template);
    }, []);
    if ($template) {
        echo template($template, $data);
        return get_stylesheet_directory().'/index.php';
    }
    return $template;
}, PHP_INT_MAX);

/**
 * Render comments.blade.php
 */
add_filter('comments_template', function ($comments_template) {
    $comments_template = str_replace(
        [get_stylesheet_directory(), get_template_directory()],
        '',
        $comments_template
    );

    $data = collect(get_body_class())->reduce(function ($data, $class) use ($comments_template) {
        return apply_filters("sage/template/{$class}/data", $data, $comments_template);
    }, []);

    $theme_template = locate_template(["views/{$comments_template}", $comments_template]);

    if ($theme_template) {
        echo template($theme_template, $data);
        return get_stylesheet_directory().'/index.php';
    }

    return $comments_template;
}, 100);
