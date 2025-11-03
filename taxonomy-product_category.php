<?php
/**
 * Template for displaying Product Category archive pages
 * Uses the same layout as archive-product.php
 */

get_header();

// 直接加载 archive-product.php 的内容
include( locate_template( 'archive-product.php' ) );

get_footer();
