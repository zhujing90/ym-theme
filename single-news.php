<?php
/**
 * The template for displaying single news items
 *
 * @package YM Scientific
 * @since 1.0.0
 */

get_header(); ?>

<?php
// 获取 banner 图片URL
$banner_url = get_site_url() . '/wp-content/uploads/2025/10/bg-news-single-scaled.png';
?>
<div class="news-single-hero-wrapper">
    <div class="news-single-hero" style="background-image: url('<?php echo esc_url($banner_url); ?>'); background-size: cover; background-position: center center; background-repeat: no-repeat; height: 250px; display: block; margin: 0; padding: 0;">
    </div>
</div>

<div class="container">
    <div class="news-single-content">
        <?php
        // Breadcrumb - 支持多种breadcrumb插件
        // 自定义简单的breadcrumb
        echo '<div class="breadcrumb-wrapper">';
        echo '<a href="' . esc_url(home_url('/')) . '">Home</a> > ';
        echo '<a href="' . esc_url(get_post_type_archive_link('news')) . '">News</a> ';
        // echo '<span>' . esc_html(get_the_title()) . '</span>';
        echo '</div>';
        ?>
        
        <?php
        while (have_posts()) :
            the_post();
            ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('news-single-article'); ?>>
                <header class="entry-header">
                    <h1 class="entry-title" style="font-family: Montserrat; font-weight: 400; font-size: 28px; color: #000000; line-height: 25px; margin-bottom: 20px;">
                        <?php the_title(); ?>
                    </h1>
                    
                    <div class="entry-meta" style="font-family: Montserrat; font-weight: 400; font-size: 14px; color: #666666; line-height: 25px; margin-bottom: 20px;">
                        <span class="posted-on">
                            <time class="entry-date published" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                <?php echo esc_html(get_the_date()); ?>
                            </time>
                        </span>
                    </div>
                </header>

                <!-- 分割线 -->
                <div class="news-divider" style="height: 1px; background: #EEEEEE; margin-bottom: 30px;"></div>

                <div class="entry-content" style="font-family: Montserrat; font-weight: 400; font-size: 16px; color: #333333; line-height: 28px;">
                    <?php
                    the_post_thumbnail('large', ['class' => 'news-center-image']); 

                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ym-scientific'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            </article>
            <div class="news-navigation">
                <div class="news-prev">
                    <?php
                    $prev_post = get_previous_post();
                    if ($prev_post) {
                        echo '<a href="' . get_permalink($prev_post->ID) . '" class="news-nav-link prev">&laquo; Prev: ' . esc_html($prev_post->post_title) . '</a>';
                    }
                    ?>
                </div>

                <div class="news-next">
                    <?php
                    $next_post = get_next_post();
                    if ($next_post) {
                        echo '<a href="' . get_permalink($next_post->ID) . '" class="news-nav-link next">Next: ' . esc_html($next_post->post_title) . ' &raquo;</a>';
                    }
                    ?>
                </div>
            </div>

        <?php
        endwhile;
        ?>
    </div>
</div>

<?php get_footer(); ?>
