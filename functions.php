<?php
/**
 * YM Scientific Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package YM Scientific
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_YM_SCIENTIFIC_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {
	wp_enqueue_style( 'ym-scientific-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_YM_SCIENTIFIC_VERSION, 'all' );
}
add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

// 后台全局加载jQuery和媒体库
function ym_enqueue_admin_scripts($hook) {
    // 全局加载jQuery（WordPress后台通常已包含，但确保加载）
    wp_enqueue_script('jquery');
    
    // 在产品分类页面加载媒体库
    if ($hook == 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_category') {
        wp_enqueue_media();
    }
    
    // 在编辑/添加产品页面加载媒体库
    if ($hook == 'post.php' || $hook == 'post-new.php') {
        global $post_type;
        if ($post_type == 'product') {
            wp_enqueue_media();
        }
    }
}
add_action('admin_enqueue_scripts', 'ym_enqueue_admin_scripts');

function create_news_post_type() {
    register_post_type('news', array(
        'labels' => array(
            'name' => __('News'),
            'singular_name' => __('News Item')
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-media-document',
        'rewrite' => array('slug' => 'our-news'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
        'show_in_rest' => true, 
    ));
}
add_action('init', 'create_news_post_type');

function enable_elementor_for_news($post_types) {
    $post_types[] = 'news';
    return $post_types;
}
add_filter('elementor/cpt_support', 'enable_elementor_for_news');

// 关闭 news 类型的评论与引用通告（前台）
add_filter('comments_open', function ($open, $post_id) {
    return get_post_type($post_id) === 'news' ? false : $open;
}, 10, 2);

add_filter('pings_open', function ($open, $post_id) {
    return get_post_type($post_id) === 'news' ? false : $open;
}, 10, 2);

// 后台移除 news 的评论支持与相关面板
add_action('admin_init', function () {
    remove_post_type_support('news', 'comments');
    remove_post_type_support('news', 'trackbacks');
});

add_action('add_meta_boxes', function () {
    remove_meta_box('commentstatusdiv', 'news', 'normal'); // 讨论
    remove_meta_box('commentsdiv', 'news', 'normal');      // 评论
});

function ym_register_product_cpt() {
  $labels = ['name' => 'Products', 'singular_name' => 'Product'];
  $args = [
    'labels' => $labels, 
    'public' => true, 
    'has_archive' => true,
    'menu_icon' => 'dashicons-products', 
    'rewrite' => ['slug' => 'products'],
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'show_in_nav_menus' => true, // 确保在菜单中可见
  ];
  register_post_type('product', $args);
  
  register_taxonomy('product_category', 'product', [
    'label' => 'Product Categories', 
    'hierarchical' => true, 
    'rewrite' => ['slug' => 'product-category'],
    'show_admin_column' => true,
    'show_ui' => true,
    'show_in_nav_menus' => true, // 确保在菜单中可见
    'public' => true, // 确保是公开的
    'publicly_queryable' => true, // 可以公开查询
  ]);
}
add_action('init', 'ym_register_product_cpt');

function enable_elementor_for_products($post_types) {
    $post_types[] = 'products';
    return $post_types;
}
add_filter('elementor/cpt_support', 'enable_elementor_for_products');


// -------------------------------
// 为 Product Category 分类添加图片上传字段
// -------------------------------
function ym_add_product_category_image_field($taxonomy) {
    ?>
    <div class="form-field term-group">
        <label for="product_category_image">Category Image</label>
        <input type="hidden" id="product_category_image" name="product_category_image" value="">
        <div id="product_category_image_preview" style="margin-top:10px;"></div>
        <button type="button" class="button upload_image_button">Upload Image</button>
        <button type="button" class="button remove_image_button">Remove Image</button>
    </div>
    <?php
}
add_action('product_category_add_form_fields', 'ym_add_product_category_image_field', 10, 2);

// 编辑分类时显示
function ym_edit_product_category_image_field($term, $taxonomy) {
    $image_id = get_term_meta($term->term_id, 'product_category_image', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="product_category_image">Category Image</label></th>
        <td>
            <input type="hidden" id="product_category_image" name="product_category_image" value="<?php echo esc_attr($image_id); ?>">
            <div id="product_category_image_preview" style="margin-top:10px;">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:100px;">
                <?php endif; ?>
            </div>
            <button type="button" class="button upload_image_button">Upload Image</button>
            <button type="button" class="button remove_image_button">Remove Image</button>
        </td>
    </tr>
    <?php
}
add_action('product_category_edit_form_fields', 'ym_edit_product_category_image_field', 10, 2);

// 保存图片字段
function ym_save_product_category_image($term_id, $tt_id) {
    if (isset($_POST['product_category_image']) && '' !== $_POST['product_category_image']) {
        update_term_meta($term_id, 'product_category_image', sanitize_text_field($_POST['product_category_image']));
    } else {
        delete_term_meta($term_id, 'product_category_image');
    }
}
add_action('created_product_category', 'ym_save_product_category_image', 10, 2);
add_action('edited_product_category', 'ym_save_product_category_image', 10, 2);


// 在产品分类页面添加图片上传脚本
function ym_product_category_image_script() {
    // 检查是否在产品分类页面
    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_category') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // 上传图片按钮
            $('.upload_image_button').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Choose Category Image',
                    button: {
                        text: 'Choose Image'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#product_category_image').val(attachment.id);
                    $('#product_category_image_preview').html('<img src="' + attachment.url + '" style="max-width:100px;">');
                });
                
                mediaUploader.open();
            });
            
            // 移除图片按钮
            $('.remove_image_button').on('click', function(e) {
                e.preventDefault();
                $('#product_category_image').val('');
                $('#product_category_image_preview').html('');
            });
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'ym_product_category_image_script');

// ========================================
// Product 自定义字段 Meta Box
// ========================================

// 添加 Product Meta Box
function ym_add_product_meta_boxes() {
    add_meta_box(
        'product_details',
        'Product Details',
        'ym_product_details_callback',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ym_add_product_meta_boxes');

// Product Meta Box 回调函数
function ym_product_details_callback($post) {
    wp_nonce_field('ym_product_details_save', 'ym_product_details_nonce');
    
    // Short Description
    $short_desc = get_post_meta($post->ID, '_product_short_description', true);
    echo '<div style="margin-bottom: 20px;">';
    echo '<label for="product_short_description"><strong>Short Description</strong></label><br>';
    echo '<textarea name="product_short_description" id="product_short_description" rows="4" style="width: 100%;">' . esc_textarea($short_desc) . '</textarea>';
    echo '<p class="description">Brief description of the product</p>';
    echo '</div>';
    
    // Content Description (Detailed)
    // $content_desc = get_post_meta($post->ID, '_product_content_description', true);
    // echo '<div style="margin-bottom: 20px;">';
    // echo '<label for="product_content_description"><strong>Content Description</strong></label><br>';
    // wp_editor($content_desc, 'product_content_description', array(
    //     'textarea_name' => 'product_content_description',
    //     'media_buttons' => false,
    //     'textarea_rows' => 10
    // ));
    // echo '</div>';
    
    // Gallery Images
    echo '<div style="margin-bottom: 20px;">';
    echo '<label><strong>Product Gallery Images</strong></label><br>';
    echo '<button type="button" class="button" id="product_gallery_upload">Upload Images</button>';
    echo '<button type="button" class="button" id="product_gallery_remove">Remove All</button>';
    echo '<div id="product_gallery_preview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;"></div>';
    echo '<input type="hidden" name="product_gallery_images" id="product_gallery_images" value="' . esc_attr(get_post_meta($post->ID, '_product_gallery_images', true)) . '">';
    echo '</div>';
    
    // PDF File
    echo '<div style="margin-bottom: 20px;">';
    echo '<label><strong>Product PDF (Downloadable)</strong></label><br>';
    $pdf_id = get_post_meta($post->ID, '_product_pdf_file', true);
    $pdf_url = $pdf_id ? wp_get_attachment_url($pdf_id) : '';
    echo '<button type="button" class="button" id="product_pdf_upload">' . ($pdf_url ? 'Change PDF' : 'Upload PDF') . '</button>';
    echo '<button type="button" class="button" id="product_pdf_remove">Remove PDF</button>';
    if ($pdf_url) {
        echo '<p><a href="' . esc_url($pdf_url) . '" target="_blank">' . basename($pdf_url) . '</a></p>';
    }
    echo '<input type="hidden" name="product_pdf_file" id="product_pdf_file" value="' . esc_attr($pdf_id) . '">';
    echo '<div id="product_pdf_preview"></div>';
    echo '</div>';
    
    // 添加 JavaScript
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Gallery Images
        var galleryUploader;
        $('#product_gallery_upload').on('click', function(e) {
            e.preventDefault();
            
            if (galleryUploader) {
                galleryUploader.open();
                return;
            }
            
            galleryUploader = wp.media({
                title: 'Select Product Images',
                button: { text: 'Add Images' },
                multiple: true,
                library: { type: 'image' }
            });
            
            galleryUploader.on('select', function() {
                var attachments = galleryUploader.state().get('selection').toJSON();
                var imageIds = attachments.map(function(att) { return att.id; }).join(',');
                var imageUrls = attachments.map(function(att) { return att.url; });
                
                $('#product_gallery_images').val(imageIds);
                
                var previewHtml = imageUrls.map(function(url) {
                    return '<img src="' + url + '" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px;">';
                }).join('');
                $('#product_gallery_preview').html(previewHtml);
            });
            
            galleryUploader.open();
        });
        
        $('#product_gallery_remove').on('click', function() {
            $('#product_gallery_images').val('');
            $('#product_gallery_preview').html('');
        });
        
        // 加载已保存的图片
        var savedImages = $('#product_gallery_images').val();
        if (savedImages) {
            var ids = savedImages.split(',');
            var html = '';
            ids.forEach(function(id) {
                wp.media.attachment(id).fetch().done(function(att) {
                    html += '<img src="' + att.get('url') + '" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px;">';
                    $('#product_gallery_preview').html(html);
                });
            });
        }
        
        // PDF File
        var pdfUploader;
        $('#product_pdf_upload').on('click', function(e) {
            e.preventDefault();
            
            if (pdfUploader) {
                pdfUploader.open();
                return;
            }
            
            pdfUploader = wp.media({
                title: 'Select PDF File',
                button: { text: 'Use PDF' },
                multiple: false,
                library: { type: 'application/pdf' }
            });
            
            pdfUploader.on('select', function() {
                var attachment = pdfUploader.state().get('selection').first().toJSON();
                $('#product_pdf_file').val(attachment.id);
                $('#product_pdf_preview').html('<p><a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a></p>');
            });
            
            pdfUploader.open();
        });
        
        $('#product_pdf_remove').on('click', function() {
            $('#product_pdf_file').val('');
            $('#product_pdf_preview').html('');
        });
    });
    </script>
    <?php
}

// 保存 Product Meta Box 数据
function ym_save_product_details($post_id) {
    // 检查 nonce
    if (!isset($_POST['ym_product_details_nonce']) || !wp_verify_nonce($_POST['ym_product_details_nonce'], 'ym_product_details_save')) {
        return;
    }
    
    // 检查权限
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 自动保存检查
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 保存字段
    if (isset($_POST['product_short_description'])) {
        update_post_meta($post_id, '_product_short_description', sanitize_textarea_field($_POST['product_short_description']));
    }
    
    if (isset($_POST['product_content_description'])) {
        update_post_meta($post_id, '_product_content_description', wp_kses_post($_POST['product_content_description']));
    }
    
    if (isset($_POST['product_gallery_images'])) {
        update_post_meta($post_id, '_product_gallery_images', sanitize_text_field($_POST['product_gallery_images']));
    }
    
    if (isset($_POST['product_pdf_file'])) {
        update_post_meta($post_id, '_product_pdf_file', sanitize_text_field($_POST['product_pdf_file']));
    }
}
add_action('save_post_product', 'ym_save_product_details');

// 获取产品字段的辅助函数
function ym_get_product_short_description($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, '_product_short_description', true);
}

function ym_get_product_content_description($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, '_product_content_description', true);
}

function ym_get_product_gallery_images($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $images = get_post_meta($post_id, '_product_gallery_images', true);
    if (!$images) {
        return array();
    }
    $image_ids = explode(',', $images);
    $image_urls = array();
    foreach ($image_ids as $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
            $image_urls[] = $image_url;
        }
    }
    return $image_urls;
}

function ym_get_product_pdf($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $pdf_id = get_post_meta($post_id, '_product_pdf_file', true);
    return $pdf_id ? wp_get_attachment_url($pdf_id) : '';
}

// ✅ 注册一个自定义小工具来显示 Product Categories
class Product_Categories_Widget extends WP_Widget {
  function __construct() {
    parent::__construct(
      'product_categories_widget', // 小工具 ID
      __('Product Categories', 'astra-child'), // 小工具标题（显示在后台）
      array('description' => __('Displays a list of Product Categories.', 'astra-child'))
    );
  }

  // 前端显示的内容
  function widget($args, $instance) {
    echo $args['before_widget'];

    // 小工具标题
    if (!empty($instance['title'])) {
      echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
    } else {
      echo $args['before_title'] . 'Product Categories' . $args['after_title'];
    }

    // 获取 Product Categories
    $terms = get_terms(array(
      'taxonomy' => 'product_category',
      'parent'     => 0, 
      'hide_empty' => false,
    ));

    if (!empty($terms) && !is_wp_error($terms)) {
      echo '<ul class="product-category-list">';
      foreach ($terms as $term) {
        echo '<li><a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a></li>';
      }
      echo '</ul>';
    } else {
      echo '<p>No product categories found.</p>';
    }

    echo $args['after_widget'];
  }

  // 后台表单：允许自定义标题
  function form($instance) {
    $title = !empty($instance['title']) ? $instance['title'] : __('Product Categories', 'astra-child');
    ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
             name="<?php echo esc_attr($this->get_field_name('title')); ?>"
             type="text" value="<?php echo esc_attr($title); ?>">
    </p>
    <?php
  }

  // 保存设置
  function update($new_instance, $old_instance) {
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
    return $instance;
  }
}

// ✅ 注册小工具
function register_product_categories_widget() {
  register_widget('Product_Categories_Widget');
}
add_action('widgets_init', 'register_product_categories_widget');

// 确保 Product Categories 在菜单编辑器中显示
function ym_ensure_product_category_in_menu() {
  $taxonomy = get_taxonomy('product_category');
  if ($taxonomy) {
    $taxonomy->show_in_nav_menus = true;
  }
}
add_action('admin_init', 'ym_ensure_product_category_in_menu');

// 在菜单编辑器页面强制显示 Product Categories
add_filter('nav_menu_meta_box_object', function($object) {
  if (isset($object->name) && $object->name === 'product_category') {
    $object->_default_query = array('orderby' => 'name');
    // 确保分类法参数正确
    $taxonomy = get_taxonomy('product_category');
    if ($taxonomy) {
      $object->labels = $taxonomy->labels;
    }
  }
  return $object;
});


add_filter( 'document_title_parts', function( $title ) {

    // 如果是产品归档页（archive-product.php）
    if ( is_post_type_archive( 'product' ) ) {
        $title['title'] = 'Our Products'; // 自定义标题
    }

    // 如果是单个产品页面
    if ( is_singular( 'product' ) ) {
        $title['title'] = 'Product Details | '.get_the_title(); // 显示产品名 + 后缀
    }

    // 如果是产品分类页面
    if ( is_tax( 'product_category' ) ) {
        $term = get_queried_object();
        $title['title'] = 'Product Category: ' . $term->name; // 分类名称
    }

    return $title;
});
