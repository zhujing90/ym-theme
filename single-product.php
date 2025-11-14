<?php
/**
 * Archive template for custom post type: product
 * - Top banner same style as single-news hero
 * - Left: product_category list
 * - Right: product list (3 columns, image + title)
 * - Pagination uses .page-numbers inside .wpsp-load-more wrapper (matches single-news styles)
 *
 * @package YM Scientific
 */

get_header();

// Banner image url
$product_banner_url = get_site_url() . '/wp-content/uploads/2025/10/products-banner-scaled.png';
$download_icon_url = get_site_url() . '/wp-content/uploads/2025/10/b01.png';
?>

<div class="product-hero-wrapper">
    <div class="product-hero" style="background-image: url('<?php echo esc_url( $product_banner_url ); ?>');"></div>
</div>

<div class="container">
    <div class="products-archive-content">
        <?php
        // Breadcrumb - 支持多种breadcrumb插件
        // 自定义简单的breadcrumb
        echo '<div class="breadcrumb-wrapper">';
        echo '<a href="' . esc_url(home_url('/')) . '">Home</a> > ';
        echo '<a href="' . esc_url(get_post_type_archive_link('products')) . '">Products</a> ';
        // echo '<span>' . esc_html(get_the_title()) . '</span>';
        echo '</div>';
        ?>
        <div class="products-archive">

            <!-- Left: Product Categories -->
            <aside class="product-categories">
                <!-- <h2 style="margin: 0 0 12px; font-size: 16px; font-weight: 600;">Categories</h2> -->
                <ul class="product-category-list">
                    <?php
                    // 获取所有分类（包含层级关系）
                    $terms = get_terms( [
                        'taxonomy'   => 'product_category',
                        'hide_empty' => false,
                        'parent'     => 0, // 只获取顶级分类
                    ] );

                    // 当前产品所属分类，用于高亮 active
                    $product_terms_for_active = get_the_terms( get_the_ID(), 'product_category' );
                    $current_term_id = 0;
                    if ( $product_terms_for_active && ! is_wp_error( $product_terms_for_active ) ) {
                        // 选取“最深层”的分类作为当前分类（优先二级/子级）
                        $deepest_term   = null;
                        $max_depth      = -1;
                        foreach ( $product_terms_for_active as $t ) {
                            $ancestors = get_ancestors( $t->term_id, 'product_category' );
                            $depth     = is_array( $ancestors ) ? count( $ancestors ) : 0;
                            if ( $depth > $max_depth ) {
                                $max_depth    = $depth;
                                $deepest_term = $t;
                            }
                        }
                        if ( $deepest_term ) {
                            $current_term_id = (int) $deepest_term->term_id;
                        } else {
                            $current_term_id = (int) $product_terms_for_active[0]->term_id;
                        }
                    } elseif ( is_tax( 'product_category' ) ) {
                        $current_term_id = (int) get_queried_object_id();
                    }
                    $current_term = $current_term_id ? get_term( $current_term_id ) : null;
                    $active_parent_id = 0;
                    if ( $current_term && ! is_wp_error( $current_term ) ) {
                        // 如果是子分类，找到其顶级父分类
                        $term_obj = $current_term;
                        while ( $term_obj->parent != 0 ) {
                            $term_obj = get_term( $term_obj->parent );
                            if ( is_wp_error( $term_obj ) ) break;
                        }
                        $active_parent_id = $term_obj->term_id;
                    }

                    // 递归显示分类及其子分类
                    function display_category_tree( $parent_id = 0, $level = 0, $current_term_id = 0, $active_parent_id = 0 ) {
                        $terms = get_terms( [
                            'taxonomy'   => 'product_category',
                            'hide_empty' => false,
                            'parent'     => $parent_id,
                        ] );

                        if ( is_wp_error( $terms ) || empty( $terms ) ) {
                            return;
                        }

                        foreach ( $terms as $term ) {
                            $is_active = $current_term_id === (int) $term->term_id;
                            $is_active_parent = $active_parent_id === (int) $term->term_id;
                            $term_link = get_term_link( $term );
                            
                            $class = '';
                            if ( $level == 0 ) {
                                $class = 'category-level-1';
                                if ( $is_active || $is_active_parent ) {
                                    $class .= ' active';
                                }
                            } else {
                                $class = 'category-level-2';
                                if ( $is_active ) {
                                    $class .= ' active';
                                }
                            }
                            
                            // 检查是否有子分类
                            $has_children = get_terms( [
                                'taxonomy'   => 'product_category',
                                'hide_empty' => false,
                                'parent'     => $term->term_id,
                                'number'     => 1,
                            ] );
                            $has_children_bool = ! is_wp_error( $has_children ) && ! empty( $has_children );
                            
                            // 判断是否应该默认展开（当前激活的分类或其父分类）
                            $should_expand = ( $is_active || $is_active_parent ) && $has_children_bool;
                            
                            echo '<li class="' . esc_attr( $class ) . ( $has_children_bool ? ' has-children' : '' ) . '">';
                            echo '<div class="category-item-wrapper">';
                            echo '<a href="' . esc_url( $term_link ) . '" class="category-link">';
                            echo esc_html( $term->name );
                            echo '</a>';
                            if ( $has_children_bool ) {
                                echo '<button type="button" class="category-toggle" aria-label="Toggle subcategories" data-expanded="' . ( $should_expand ? 'true' : 'false' ) . '">';
                                if ( $should_expand ) {
                                    echo '<i class="fa fa-caret-down" aria-hidden="true"></i>';
                                } else {
                                    echo '<i class="fa fa-caret-right" aria-hidden="true"></i>';
                                }
                                echo '</button>';
                            }
                            echo '</div>';
                            
                            if ( $has_children_bool ) {
                                $sub_class = ( $is_active || $is_active_parent ) ? 'sub-categories active-parent' : 'sub-categories';
                                $sub_class .= $should_expand ? ' expanded' : ' collapsed';
                                echo '<ul class="' . esc_attr( $sub_class ) . '">';
                                display_category_tree( $term->term_id, $level + 1, $current_term_id, $active_parent_id );
                                echo '</ul>';
                            }
                            echo '</li>';
                        }
                    }

                    if ( ! is_wp_error( $terms ) ) {
                        display_category_tree( 0, 0, $current_term_id, $active_parent_id );
                    }
                    ?>
                </ul>
            </aside>

            <!-- Right: Product Detail -->
            <section>
                <article class="single-product-detail">
                    <div class="product-detail-grid">
                        <?php
                        // 准备图片：特色图 + 画廊
                        $images = [];
                        if ( has_post_thumbnail() ) {
                            $images[] = get_post_thumbnail_id();
                        }
                        $gallery_ids = get_post_meta( get_the_ID(), '_product_gallery_images', true );
                        if ( ! empty( $gallery_ids ) ) {
                            $ids = array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) );
                            foreach ( $ids as $img_id ) {
                                if ( ! in_array( $img_id, $images, true ) ) { $images[] = $img_id; }
                            }
                        }
                        ?>

                        <!-- 左侧：图廊 -->
                        <div class="product-gallery">
                            <div class="product-gallery-thumbs">
                                <button type="button" class="thumb-nav up" aria-label="up" id="thumb-nav-up">
                                    <i class="fa fa-chevron-up desktop-icon" aria-hidden="true"></i>
                                    <i class="fa fa-chevron-left mobile-icon" aria-hidden="true"></i>
                                </button>
                                <div class="thumb-scroll-container">
                                    <ul class="thumb-list">
                                        <?php foreach ( $images as $idx => $img_id ) :
                                            $thumb = wp_get_attachment_image_src( $img_id, 'thumbnail' );
                                            $full  = wp_get_attachment_image_src( $img_id, 'large' );
                                        ?>
                                            <li>
                                                <a href="#" class="product-thumb-item<?php echo $idx === 0 ? ' active' : ''; ?>" data-full="<?php echo esc_url( $full[0] ); ?>" data-index="<?php echo $idx; ?>">
                                                    <img src="<?php echo esc_url( $thumb[0] ); ?>" alt="thumb">
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <button type="button" class="thumb-nav down" aria-label="down" id="thumb-nav-down">
                                    <i class="fa fa-chevron-down desktop-icon" aria-hidden="true"></i>
                                    <i class="fa fa-chevron-right mobile-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="product-gallery-main">
                                <?php if ( ! empty( $images ) ) :
                                    $first = wp_get_attachment_image_src( $images[0], 'full' );
                                ?>
                                    <img id="product-main-image" src="<?php echo esc_url( $first[0] ); ?>" alt="product" />
                                <?php else : ?>
                                    <div class="product-main-placeholder"></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 右侧：标题区（分类、标题、短描述、PDF按钮） -->
                        <div class="single-product-header">
                            <?php
                            $term_names = [];
                            if ( $product_terms_for_active && ! is_wp_error( $product_terms_for_active ) ) {
                                foreach ( $product_terms_for_active as $t ) { $term_names[] = $t->name; }
                            }
                            if ( ! empty( $term_names ) ) {
                                echo '<div class="single-product-category">' . esc_html( implode( ', ', $term_names ) ) . '</div>';
                            }
                            ?>
                            <h1 class="single-product-title"><?php the_title(); ?></h1>
                            <?php
                            $short_desc = get_post_meta( get_the_ID(), '_product_short_description', true );
                            if ( ! empty( $short_desc ) ) {
                                echo '<div class="single-product-shortdesc">' . wp_kses_post( nl2br( $short_desc ) ) . '</div>';
                            }
                            $pdf_id = get_post_meta( get_the_ID(), '_product_pdf_file', true );
                            if ( $pdf_id ) {
                                $pdf_url = wp_get_attachment_url( $pdf_id );
                                // 获取PDF文件名
                                $pdf_filename = get_the_title( $pdf_id );
                                if ( empty( $pdf_filename ) ) {
                                    $pdf_attachment = get_post( $pdf_id );
                                    if ( $pdf_attachment && $pdf_attachment->post_title ) {
                                        $pdf_filename = $pdf_attachment->post_title;
                                    } else {
                                        $pdf_filename = basename( $pdf_url );
                                    }
                                }
                                // 移除文件扩展名（如果存在）
                                $pdf_name = preg_replace( '/\.pdf$/i', '', $pdf_filename );
                                echo '<p class="product-pdf"><a class="product-pdf-button" href="' . esc_url( $pdf_url ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $download_icon_url ) . '" alt="Download" style="width: 20px; height: 20px; margin-right: 5px;">' . esc_html( $pdf_name ) . ' PDF</a></p>';
                            }
                            ?>
                        </div>
                    </div>
                </article>

                <div class="single-product-body">
                    <div class="single-product-content-title-wrapper">
                        <span class="single-product-content-title">Introduction</span>
                    </div>
                  <div class="single-product-content">
                    
                      <?php the_content(); ?>
                  </div>
              </div>
              <div class="single-product-inquery-form">
                <div class="single-product-content-title-wrapper">
                    <span class="single-product-content-title">Send Message</span>
                </div>
                <div class="single-product-inquery-form-content">
                    <div class="single-product-inquery-form-content-title-wrapper">
                        <span class="single-product-inquery-form-content-title">Inquire: <?php the_title(); ?></span>
                    </div>
                  <?php echo do_shortcode('[wpforms id="893"]'); ?>
                </div>
              </div>
              <div class="single-product-related-products">
                <div class="single-product-content-title-wrapper">
                    <span class="single-product-content-title">Related Products</span>
                </div>
                <div class="single-product-related-products-list">
                  <?php echo do_shortcode('[related_products limit="3"]'); ?>
                </div>
              </div>
            </section>

        </div>
    </div>
</div>

<?php get_footer(); ?>

<script>
// 产品画廊切换和滚动
(function(){
  var main = document.getElementById('product-main-image');
  var thumbList = document.querySelector('.thumb-list');
  var thumbContainer = document.querySelector('.thumb-scroll-container');
  var navUp = document.getElementById('thumb-nav-up');
  var navDown = document.getElementById('thumb-nav-down');
  
  if(!main || !thumbList || !thumbContainer) return;
  
  var links = document.querySelectorAll('.product-thumb-item');
  var currentIndex = 0;
  var isMobile = window.innerWidth <= 768;
  var maxScroll = 0;
  
  // 检测是否为移动端
  function checkMobile() {
    isMobile = window.innerWidth <= 768;
    updateScrollLimits();
  }
  
  // 计算最大滚动距离
  function updateScrollLimits() {
    if (!thumbList || !thumbContainer) return;
    
    if (isMobile) {
      // 移动端：横向滚动
      var containerWidth = thumbContainer.clientWidth;
      var listWidth = thumbList.scrollWidth;
      maxScroll = Math.max(0, listWidth - containerWidth);
    } else {
      // 桌面端：纵向滚动
      maxScroll = Math.max(0, thumbList.scrollHeight - thumbContainer.clientHeight);
    }
    updateNavButtons();
  }
  
  // 更新导航按钮状态
  function updateNavButtons() {
    if (!navUp || !navDown) return;
    navUp.style.opacity = currentIndex > 0 ? '1' : '0.3';
    navDown.style.opacity = currentIndex < links.length - 1 ? '1' : '0.3';
  }
  
  // 切换到指定索引的图片
  function switchToImage(index) {
    if (index < 0 || index >= links.length) return;
    
    currentIndex = index;
    var activeLink = links[index];
    var url = activeLink.getAttribute('data-full');
    
    if (url && main) {
      main.src = url;
    }
    
    // 更新激活状态
    links.forEach(function(x){ x.classList.remove('active'); });
    activeLink.classList.add('active');
    
    // 滚动到当前选中的缩略图
    if (isMobile) {
      // 移动端：横向滚动，确保当前缩略图可见（显示3个缩略图）
      var thumbItem = activeLink.closest('li');
      if (thumbItem && thumbContainer) {
        var itemLeft = thumbItem.offsetLeft;
        var itemWidth = thumbItem.offsetWidth; // 80px
        var containerWidth = thumbContainer.clientWidth;
        
        // 计算需要滚动的位置，确保当前项在可见区域内
        // 如果容器显示3个缩略图，当前项应该在中间位置
        var targetScroll = itemLeft - (containerWidth - itemWidth) / 2;
        thumbContainer.scrollLeft = Math.max(0, Math.min(targetScroll, maxScroll));
      }
    } else {
      // 桌面端：纵向滚动
      var itemHeight = 88; // 80px + 8px gap
      var scrollTo = index * itemHeight;
      thumbContainer.scrollTop = scrollTo;
    }
    updateNavButtons();
  }
  
  // 点击缩略图切换大图
  links.forEach(function(a, index){
    a.addEventListener('click', function(e){
      e.preventDefault();
      switchToImage(index);
    });
  });
  
  // 导航按钮点击事件
  if (navUp) {
    navUp.addEventListener('click', function(e){
      e.preventDefault();
      if (currentIndex > 0) {
        switchToImage(currentIndex - 1);
      }
    });
  }
  
  if (navDown) {
    navDown.addEventListener('click', function(e){
      e.preventDefault();
      if (currentIndex < links.length - 1) {
        switchToImage(currentIndex + 1);
      }
    });
  }
  
  // 初始化
  updateScrollLimits();
  updateNavButtons();
  
  // 监听窗口大小变化
  window.addEventListener('resize', function() {
    checkMobile();
  });
})();

// 产品分类折叠功能
(function() {
  var toggles = document.querySelectorAll('.category-toggle');
  
  toggles.forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      var isExpanded = toggle.getAttribute('data-expanded') === 'true';
      var subCategories = toggle.closest('.category-level-1').querySelector('.sub-categories');
      
      if (subCategories) {
        var toggleIcon = toggle.querySelector('i');
        if (isExpanded) {
          // 折叠
          subCategories.classList.remove('expanded');
          subCategories.classList.add('collapsed');
          toggle.setAttribute('data-expanded', 'false');
          if (toggleIcon) {
            toggleIcon.className = 'fa fa-caret-right';
          }
        } else {
          // 展开
          subCategories.classList.remove('collapsed');
          subCategories.classList.add('expanded');
          toggle.setAttribute('data-expanded', 'true');
          if (toggleIcon) {
            toggleIcon.className = 'fa fa-caret-down';
          }
        }
      }
    });
  });
})();
</script>


