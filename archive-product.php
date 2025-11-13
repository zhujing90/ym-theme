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
?>

<div class="product-hero-wrapper">
    <div class="product-hero" style="background-image: url('<?php echo esc_url( $product_banner_url ); ?>'); background-size: cover; background-position: center center; background-repeat: no-repeat; height: 250px; display: block; margin: 0; padding: 0;"></div>
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

                    // 检查当前激活的分类是否是一级分类或其子分类
                    $current_term_id = is_tax( 'product_category' ) ? get_queried_object_id() : 0;
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
                            
                            echo '<li class="' . esc_attr( $class ) . '">';
                            echo '<a href="' . esc_url( $term_link ) . '" class="category-link">';
                            echo esc_html( $term->name );
                            echo '</a>';
                            echo '</li>';
                            
                            // 如果有子分类，需要包裹一个容器来设置背景色
                            $has_children = get_terms( [
                                'taxonomy'   => 'product_category',
                                'hide_empty' => false,
                                'parent'     => $term->term_id,
                                'number'     => 1,
                            ] );
                            
                            if ( ! is_wp_error( $has_children ) && ! empty( $has_children ) ) {
                                $sub_class = ( $is_active || $is_active_parent ) ? 'sub-categories active-parent' : 'sub-categories';
                                echo '<ul class="' . esc_attr( $sub_class ) . '">';
                                display_category_tree( $term->term_id, $level + 1, $current_term_id, $active_parent_id );
                                echo '</ul>';
                            }
                        }
                    }

                    if ( ! is_wp_error( $terms ) ) {
                        display_category_tree( 0, 0, $current_term_id, $active_parent_id );
                    }
                    ?>
                </ul>
            </aside>

            <!-- Right: Product List -->
            <section>
                <?php if ( have_posts() ) : ?>
                    <div class="product-grid">
                        <?php while ( have_posts() ) : the_post(); 
                            // 获取产品分类
                            $terms = get_the_terms( get_the_ID(), 'product_category' );
                            $category_name = '';
                            if ( $terms && ! is_wp_error( $terms ) ) {
                                $category_name = $terms[0]->name;
                            }
                        ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('product-card'); ?>>
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

                    <?php
                    // Pagination (match .page-numbers inside .wpsp-load-more wrapper)
                    $pagination = paginate_links( [
                        'type'      => 'array',
                        'prev_text' => __('« Prev'),
                        'next_text' => __('Next »'),
                    ] );
                    if ( ! empty( $pagination ) ) {
                        echo '<div class="wpsp-load-more">';
                        foreach ( $pagination as $link ) {
                            // paginate_links already outputs .page-numbers classes
                            echo $link;
                        }
                        echo '</div>';
                    }
                    ?>

                <?php else : ?>
                    <p><?php esc_html_e( 'No products found.', 'ym-scientific' ); ?></p>
                <?php endif; ?>
            </section>

        </div>
    </div>
</div>

<?php get_footer(); ?>


