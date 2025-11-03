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
    <div class="product-hero" style="background-image: url('<?php echo esc_url( $product_banner_url ); ?>'); background-size: cover; background-position: center center; background-repeat: no-repeat; height: 160px; display: block; margin: 0; padding: 0;"></div>
    <div class="product-hero-title" style="position: relative;">
        <!-- reserved for potential overlay text -->
    </div>
</div>

<div class="container">
    <div class="products-archive-content">
        <?php
        // Breadcrumb - 支持多种breadcrumb插件
        // 自定义简单的breadcrumb
        echo '<div class="breadcrumb-wrapper" style="padding-bottom: 20px;">';
        echo '<a href="' . esc_url(home_url('/')) . '">Home</a> > ';
        echo '<a href="' . esc_url(get_post_type_archive_link('products')) . '">Products</a> ';
        // echo '<span>' . esc_html(get_the_title()) . '</span>';
        echo '</div>';
        ?>
        <div class="products-archive" style="display: grid; grid-template-columns: 260px 1fr; gap: 30px; align-items: start;">

            <!-- Left: Product Categories -->
            <aside class="product-categories" style="background: #fff; border: 1px solid #eee; padding: 16px;">
                <h2 style="margin: 0 0 12px; font-size: 16px; font-weight: 600;">Categories</h2>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php
                    // 获取所有分类（包含层级关系）
                    $terms = get_terms( [
                        'taxonomy'   => 'product_category',
                        'hide_empty' => false,
                        'parent'     => 0, // 只获取顶级分类
                    ] );

                    // 递归显示分类及其子分类
                    function display_category_tree( $parent_id = 0, $level = 0, $current_term_id = 0 ) {
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
                            $term_link = get_term_link( $term );
                            $padding_left = $level * 20; // 每级缩进20px
                            
                            echo '<li style="margin: 6px 0;">';
                            echo '<a href="' . esc_url( $term_link ) . '"';
                            echo ' style="display:block; padding:8px 10px; padding-left:' . ( 10 + $padding_left ) . 'px; border:1px solid #eee; border-radius:4px; text-decoration:none; color:#222;';
                            if ( $is_active ) {
                                echo ' background:#f2f7ff; border-color:#cfe3ff;';
                            }
                            echo '">' . esc_html( $term->name ) . '</a>';
                            echo '</li>';
                            
                            // 递归显示子分类
                            display_category_tree( $term->term_id, $level + 1, $current_term_id );
                        }
                    }

                    if ( ! is_wp_error( $terms ) ) {
                        $current_term_id = is_tax( 'product_category' ) ? get_queried_object_id() : 0;
                        display_category_tree( 0, 0, $current_term_id );
                    }
                    ?>
                </ul>
            </aside>

            <!-- Right: Product List -->
            <section>
                <?php if ( have_posts() ) : ?>
                    <div class="product-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px;">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('product-card'); ?> style="background:#fff; border:1px solid #eee; border-radius:4px; overflow:hidden;">
                                <a href="<?php the_permalink(); ?>" style="display:block; text-decoration:none; color:inherit;">
                                    <div class="product-thumb" style="aspect-ratio: 4/3; width:100%; background:#fafafa; display:flex; align-items:center; justify-content:center;">
                                        <?php if ( has_post_thumbnail() ) {
                                            the_post_thumbnail( 'medium', [ 'style' => 'width:100%; height:auto; object-fit:cover;' ] );
                                        } else {
                                            echo '<div style="height:100%; width:100%; background:#f5f5f5;"></div>';
                                        } ?>
                                    </div>
                                    <h3 class="product-title" style="margin:12px; font-family: Montserrat; font-weight: 400; font-size: 16px; color: #000000; line-height: 24px;">
                                        <?php the_title(); ?>
                                    </h3>
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


