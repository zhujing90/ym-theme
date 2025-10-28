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
    <div class="news-single-hero" style="background-image: url('<?php echo esc_url($banner_url); ?>'); background-size: cover; background-position: center center; background-repeat: no-repeat; height: 160px; display: block; margin: 0; padding: 0;">
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 30px 20px;">
    <div class="news-single-content">
        <?php
        // Breadcrumb - 支持多种breadcrumb插件
        if (function_exists('yoast_breadcrumb')) {
            echo '<div class="breadcrumb-wrapper" style="padding: 20px 0;">';
            yoast_breadcrumb();
            echo '</div>';
        } elseif (function_exists('bcn_display')) {
            echo '<div class="breadcrumb-wrapper" style="padding: 20px 0;">';
            bcn_display();
            echo '</div>';
        } else {
            // 自定义简单的breadcrumb
            echo '<div class="breadcrumb-wrapper" style="padding: 20px 0;">';
            echo '<a href="' . esc_url(home_url('/')) . '">Home</a> / ';
            echo '<a href="' . esc_url(get_post_type_archive_link('news')) . '">News</a> / ';
            echo '<span>' . esc_html(get_the_title()) . '</span>';
            echo '</div>';
        }
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
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'ym-scientific'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            </article>
            
        <?php
        endwhile;
        ?>
    </div>
</div>

<?php get_footer(); ?>
