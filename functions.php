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
    
    // Gallery Images
    $saved_gallery_ids = get_post_meta($post->ID, '_product_gallery_images', true);
    echo '<div style="margin-bottom: 20px;">';
    echo '<label><strong>Product Gallery Images</strong></label><br>';
    echo '<button type="button" class="button" id="product_gallery_upload">Upload Images</button>';
    echo '<button type="button" class="button" id="product_gallery_remove">Remove All</button>';
    echo '<div id="product_gallery_preview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;">';
    // 直接输出已保存的图片缩略图
    if ($saved_gallery_ids) {
        $image_ids = array_filter(array_map('intval', explode(',', $saved_gallery_ids)));
        foreach ($image_ids as $img_id) {
            $thumb = wp_get_attachment_image_src($img_id, 'thumbnail');
            if ($thumb) {
                echo '<img src="' . esc_url($thumb[0]) . '" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" data-attachment-id="' . esc_attr($img_id) . '">';
            }
        }
    }
    echo '</div>';
    echo '<input type="hidden" name="product_gallery_images" id="product_gallery_images" value="' . esc_attr($saved_gallery_ids) . '">';
    echo '</div>';
    
    // PDF File
    $pdf_id = get_post_meta($post->ID, '_product_pdf_file', true);
    $pdf_url = $pdf_id ? wp_get_attachment_url($pdf_id) : '';
    $pdf_filename = '';
    if ($pdf_id) {
        $pdf_filename = get_the_title($pdf_id);
        // 如果标题为空，尝试从文件名获取
        if (empty($pdf_filename)) {
            $pdf_attachment = get_post($pdf_id);
            if ($pdf_attachment && $pdf_attachment->post_title) {
                $pdf_filename = $pdf_attachment->post_title;
            } else if ($pdf_url) {
                $pdf_filename = basename($pdf_url);
            }
        }
    } else if ($pdf_url) {
        $pdf_filename = basename($pdf_url);
    }
    
    echo '<div style="margin-bottom: 20px;">';
    echo '<label><strong>Product PDF (Downloadable)</strong></label><br>';
    echo '<button type="button" class="button" id="product_pdf_upload">' . ($pdf_url ? 'Change PDF' : 'Upload PDF') . '</button>';
    echo '<button type="button" class="button" id="product_pdf_remove" style="' . (!$pdf_url ? 'display:none;' : '') . '">Remove PDF</button>';
    echo '<input type="hidden" name="product_pdf_file" id="product_pdf_file" value="' . esc_attr($pdf_id) . '">';
    echo '<div id="product_pdf_preview" style="margin-top: 10px; min-height: 30px;">';
    if ($pdf_url && $pdf_filename) {
        echo '<p style="margin: 5px 0;"><strong>Current PDF:</strong> <a href="' . esc_url($pdf_url) . '" target="_blank" style="text-decoration: underline;">' . esc_html($pdf_filename) . '</a></p>';
    }
    echo '</div>';
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
                button: { 
                    text: 'Add Images'
                },
                multiple: true,
                library: { 
                    type: 'image'
                }
            });
            
            // 更新按钮文本的函数
            function updateButtonText() {
                setTimeout(function() {
                    // 尝试多种方式查找按钮
                    var button = galleryUploader.$el.find('.media-button-select, .media-button-primary, button.media-button-select, button.media-button-primary');
                    if (!button.length) {
                        // 如果找不到，尝试直接查找工具栏中的按钮
                        button = galleryUploader.$el.find('.media-toolbar button');
                    }
                    if (button.length) {
                        // 检查按钮是否有文本，如果没有或为空则设置
                        var currentText = button.text().trim();
                        if (!currentText || currentText === '' || currentText.length < 3) {
                            button.text('Add Images');
                            button.html('Add Images');
                        } else if (currentText !== 'Add Images') {
                            // 如果文本不是我们想要的，也更新
                            button.text('Add Images');
                            button.html('Add Images');
                        }
                    }
                }, 50);
            }
            
            // 确保按钮文本正确显示 - 监听多个事件
            galleryUploader.on('open', function() {
                updateButtonText();
            });
            
            // 当选择改变时也更新按钮文本 - 立即更新并持续检查
            galleryUploader.on('selection:single', function() {
                updateButtonText();
                // 额外检查，确保按钮文本不会消失
                setTimeout(updateButtonText, 100);
                setTimeout(updateButtonText, 300);
            });
            
            galleryUploader.on('selection:multiple', function() {
                updateButtonText();
                // 额外检查，确保按钮文本不会消失
                setTimeout(updateButtonText, 100);
                setTimeout(updateButtonText, 300);
            });
            
            galleryUploader.on('selection:unsingle', function() {
                updateButtonText();
            });
            
            galleryUploader.on('selection:unmultiple', function() {
                updateButtonText();
            });
            
            // 监听所有状态变化
            galleryUploader.on('activate', function() {
                updateButtonText();
            });
            
            // 使用 MutationObserver 监听按钮变化 - 使用防抖优化
            var updateTimeout;
            var observerInterval;
            galleryUploader.on('ready', function() {
                var toolbar = galleryUploader.$el.find('.media-toolbar');
                if (toolbar.length) {
                    var observer = new MutationObserver(function() {
                        // 防抖处理，避免频繁更新
                        clearTimeout(updateTimeout);
                        updateTimeout = setTimeout(function() {
                            updateButtonText();
                        }, 50);
                    });
                    observer.observe(toolbar[0], {
                        childList: true,
                        subtree: true,
                        characterData: true
                    });
                    
                    // 定期检查按钮文本（每500ms检查一次，直到媒体库关闭）
                    observerInterval = setInterval(function() {
                        updateButtonText();
                    }, 500);
                }
            });
            
            // 媒体库关闭时清除定时器
            galleryUploader.on('close', function() {
                if (observerInterval) {
                    clearInterval(observerInterval);
                    observerInterval = null;
                }
            });
            
            galleryUploader.on('select', function() {
                var attachments = galleryUploader.state().get('selection').toJSON();
                var newImageIds = attachments.map(function(att) { return att.id; });
                
                // 获取现有的图片ID
                var existingIds = $('#product_gallery_images').val();
                var allIds = [];
                if (existingIds && existingIds.trim() !== '') {
                    allIds = existingIds.split(',').filter(function(id) { return id.trim() !== ''; });
                }
                
                // 合并新图片（避免重复）
                var previewHtml = '';
                newImageIds.forEach(function(newId) {
                    if (allIds.indexOf(String(newId)) === -1) {
                        allIds.push(String(newId));
                        // 找到对应的attachment对象
                        var att = attachments.find(function(a) { return a.id == newId; });
                        if (att && att.url) {
                            // 优先使用缩略图
                            var thumbUrl = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) ? att.sizes.thumbnail.url : att.url;
                            previewHtml += '<img src="' + thumbUrl + '" style="max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" data-attachment-id="' + newId + '">';
                        }
                    }
                });
                
                // 更新隐藏字段
                $('#product_gallery_images').val(allIds.join(','));
                
                // 添加新图片到预览
                if (previewHtml) {
                    $('#product_gallery_preview').append(previewHtml);
                }
                
                // 选择完成后立即更新按钮文本（防止按钮文本消失）
                setTimeout(function() {
                    updateButtonText();
                }, 200);
            });
            
            galleryUploader.open();
        });
        
        $('#product_gallery_remove').on('click', function() {
            $('#product_gallery_images').val('');
            $('#product_gallery_preview').html('');
        });
        
        // PDF File
        var pdfUploader;
        $('#product_pdf_upload').on('click', function(e) {
            e.preventDefault();
            
            if (pdfUploader) {
                pdfUploader.open();
                return;
            }
            
            pdfUploader = wp.media({
                title: 'Select PDF File (Only one file allowed)',
                button: { text: 'Use PDF' },
                multiple: false, // 限制只能选择一个
                library: { 
                    type: 'application/pdf' 
                }
            });
            
            // 确保只能选择一个文件
            pdfUploader.on('open', function() {
                var selection = pdfUploader.state().get('selection');
                // 限制选择数量为1
                selection.on('add', function() {
                    if (selection.length > 1) {
                        selection.remove(selection.models[0]);
                    }
                });
            });
            
            pdfUploader.on('select', function() {
                var attachment = pdfUploader.state().get('selection').first().toJSON();
                var pdfId = attachment.id;
                var pdfUrl = attachment.url;
                var pdfFilename = attachment.filename || attachment.title || basename(pdfUrl);
                
                // 更新隐藏字段
                $('#product_pdf_file').val(pdfId);
                
                // 更新预览 - 显示文件名
                var previewHtml = '<p style="margin: 5px 0;"><strong>Current PDF:</strong> <a href="' + pdfUrl + '" target="_blank" style="text-decoration: underline;">' + pdfFilename + '</a></p>';
                $('#product_pdf_preview').html(previewHtml);
                
                // 更新按钮文本
                $('#product_pdf_upload').text('Change PDF');
                $('#product_pdf_remove').show();
            });
            
            pdfUploader.open();
        });
        
        $('#product_pdf_remove').on('click', function() {
            if (confirm('Are you sure you want to remove this PDF?')) {
                $('#product_pdf_file').val('');
                $('#product_pdf_preview').html('');
                $('#product_pdf_upload').text('Upload PDF');
                $(this).hide();
            }
        });
        
        // 辅助函数：从URL获取文件名
        function basename(path) {
            return path.split('/').pop().split('?')[0];
        }
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

/**
 * 为自定义产品类型添加“Best Seller”勾选框
 */
function ym_add_best_seller_meta_box() {
  add_meta_box(
    'ym_best_seller_box',
    'Best Seller',
    'ym_best_seller_box_callback',
    'product',
    'side',
    'high'
  );
}
add_action('add_meta_boxes', 'ym_add_best_seller_meta_box');

function ym_best_seller_box_callback($post) {
  $is_best_seller = get_post_meta($post->ID, '_is_best_seller', true);
  ?>
  <label>
    <input type="checkbox" name="ym_is_best_seller" value="1" <?php checked($is_best_seller, '1'); ?>>
    Mark this product as Best Seller
  </label>
  <?php
}

// 保存勾选状态
function ym_save_best_seller_meta($post_id) {
  if (array_key_exists('ym_is_best_seller', $_POST)) {
    update_post_meta($post_id, '_is_best_seller', '1');
  } else {
    delete_post_meta($post_id, '_is_best_seller');
  }
}
add_action('save_post_product', 'ym_save_best_seller_meta');

/**
 * Shortcode: [best_sales_products limit="6"]
 */
function ym_best_sales_products_shortcode($atts) {
  $atts = shortcode_atts(array(
    'limit' => 6,
  ), $atts, 'best_sales_products');

  $args = array(
    'post_type'      => 'product',
    'posts_per_page' => intval($atts['limit']),
    'meta_key'       => '_is_best_seller',
    'meta_value'     => '1',
  );

  $query = new WP_Query($args);

  ob_start();

  if ($query->have_posts()) :
    $post_count = $query->post_count;
    $carousel_id = 'best-sales-carousel-' . uniqid();
    ?>
    <div class="best-sales-carousel-wrapper">
      <div class="best-sales-carousel" id="<?php echo esc_attr($carousel_id); ?>">
        <div class="best-sales-carousel-container">
          <?php while ($query->have_posts()) : $query->the_post(); 
            // 获取产品分类
            $terms = get_the_terms( get_the_ID(), 'product_category' );
            $category_name = '';
            if ( $terms && ! is_wp_error( $terms ) ) {
              $category_name = $terms[0]->name;
            }
          ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('product-card best-sales-item'); ?>>
              <a href="<?php the_permalink(); ?>" class="product-card-link">
                <div class="product-thumb">
                  <?php if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'medium' );
                  } else {
                    echo '<div class="product-placeholder"></div>';
                  } ?>
                </div>
                
                <!-- Hover 覆盖层 -->
                <div class="product-hover-overlay">
                  <div class="product-hover-content">
                    <svg class="product-search-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="11" cy="11" r="8" stroke="white" stroke-width="2" fill="none"/>
                      <path d="m21 21-4.35-4.35" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <?php if ( $category_name ) : ?>
                      <div class="product-category-name"><?php echo esc_html( $category_name ); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="product-title-wrapper">
                  <h3 class="product-title">
                    <?php the_title(); ?>
                  </h3>
                  <div class="product-view-more">
                    <span>View More</span>
                    <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M5 12h14M12 5l7 7-7 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </div>
              </a>
            </article>
          <?php endwhile; ?>
        </div>
      </div>
      
      <?php if ($post_count > 1) : // 如果超过1个item，显示按钮 ?>
        <button class="best-sales-nav best-sales-nav-prev" aria-label="Previous">&lt;</button>
        <button class="best-sales-nav best-sales-nav-next" aria-label="Next">&gt;</button>
      <?php endif; ?>
    </div>
    
    <script>
    (function() {
      var carousel = document.getElementById('<?php echo esc_js($carousel_id); ?>');
      if (!carousel) return;
      
      var container = carousel.querySelector('.best-sales-carousel-container');
      var wrapper = carousel.closest('.best-sales-carousel-wrapper');
      var prevBtn = wrapper ? wrapper.querySelector('.best-sales-nav-prev') : null;
      var nextBtn = wrapper ? wrapper.querySelector('.best-sales-nav-next') : null;
      var items = container ? container.querySelectorAll('.best-sales-item') : [];
      var currentIndex = 0;
      var itemsPerView = 4;
      var maxIndex = 0;
      var resizeTimeout;
      
      function getItemsPerView() {
        return window.innerWidth <= 768 ? 1 : 4;
      }
      
      function updateCarousel() {
        if (!container || items.length === 0) return;
        
        itemsPerView = getItemsPerView();
        maxIndex = Math.max(0, items.length - itemsPerView);
        
        if (currentIndex > maxIndex) {
          currentIndex = maxIndex;
        }
        
        updateTransform();
        updateButtons();
      }
      
      function updateTransform() {
        if (!container || items.length === 0) return;
        
        var itemWidth = items[0].offsetWidth;
        var gap = 24; // 与CSS中的gap一致
        var translateX = -(currentIndex * (itemWidth + gap));
        container.style.transform = 'translateX(' + translateX + 'px)';
        container.style.transition = 'transform 0.3s ease';
      }
      
      function updateButtons() {
        if (prevBtn) {
          prevBtn.style.opacity = currentIndex > 0 ? '1' : '0.3';
          prevBtn.style.pointerEvents = currentIndex > 0 ? 'auto' : 'none';
        }
        if (nextBtn) {
          nextBtn.style.opacity = currentIndex < maxIndex ? '1' : '0.3';
          nextBtn.style.pointerEvents = currentIndex < maxIndex ? 'auto' : 'none';
        }
      }
      
      if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (currentIndex > 0) {
            currentIndex--;
            updateTransform();
            updateButtons();
          }
        });
      }
      
      if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (currentIndex < maxIndex) {
            currentIndex++;
            updateTransform();
            updateButtons();
          }
        });
      }
      
      // 窗口大小改变时重新计算
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
          updateCarousel();
        }, 100);
      });
      
      // 等待DOM加载完成后再初始化
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
          setTimeout(updateCarousel, 100);
        });
      } else {
        setTimeout(updateCarousel, 100);
      }
    })();
    </script>
    <?php
  else :
    echo '<p>No best seller products found.</p>';
  endif;

  wp_reset_postdata();
  return ob_get_clean();
}
add_shortcode('best_sales_products', 'ym_best_sales_products_shortcode');
