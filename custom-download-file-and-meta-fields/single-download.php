<?php
/**
 * The template for displaying single Download posts
 *
 * @since Iceberg 1.0
 */

get_header(); ?>

    <div id="primary" class="content-area">

        <main id="main" class="site-main">

        <?php while ( have_posts() ) : the_post();
            $files = get_post_meta( get_the_ID(), '_iceberg_download_files', true );
        ?>
        
        <?php iceberg_render_ad( 'above_single_content' ); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <?php iceberg_post_thumbnail(); ?>

            <div class="inner-box">
                <div class="content-container">

                    <header class="entry-header">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                        <?php iceberg_the_post_meta( '<div class="post-meta">', '</div>', get_theme_mod( 'display_date', 1 ), get_theme_mod( 'display_author', 1 ) ); ?>
                    </header><!-- .entry-header -->

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div><!-- .entry-content -->

                    <?php if ( is_array( $files ) && ! empty( $files ) ) : ?>
                    <div class="download-files-section">

                        <h3 class="download-files-heading">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <?php esc_html_e( 'Available Downloads', 'iceberg' ); ?>
                        </h3>

                        <div class="download-files-list">
                            <?php foreach ( $files as $index => $file ) :
                                if ( empty( $file['url'] ) ) continue;
                                $title     = ! empty( $file['title'] ) ? $file['title'] : __( 'Download', 'iceberg' );
                                $format    = ! empty( $file['format'] ) ? strtoupper( $file['format'] ) : '';
                                $file_size = ! empty( $file['file_size'] ) ? $file['file_size'] : '';
                                $is_online = ( isset( $file['type'] ) && $file['type'] === 'online' );
                                $url       = $file['url'];
                            ?>
                            <div class="download-file-item">
                                <div class="download-file-icon">
                                    <?php if ( $is_online ) : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    <?php else : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                    <?php endif; ?>
                                </div>

                                <div class="download-file-info">
                                    <span class="download-file-title"><?php echo esc_html( $title ); ?></span>
                                    <span class="download-file-meta">
                                        <?php if ( $format ) : ?>
                                            <span class="file-format-badge"><?php echo esc_html( $format ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( $file_size ) : ?>
                                            <span class="file-size"><?php echo esc_html( $file_size ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( $is_online ) : ?>
                                            <span class="file-source"><?php esc_html_e( 'External Link', 'iceberg' ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <a href="<?php echo esc_url( $url ); ?>"
                                   class="download-file-btn"
                                   <?php echo $is_online ? 'target="_blank" rel="noopener noreferrer"' : 'download'; ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <?php echo $is_online ? esc_html__( 'Open', 'iceberg' ) : esc_html__( 'Download', 'iceberg' ); ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    </div><!-- .download-files-section -->
                    <?php endif; ?>

                    <?php
                        wp_link_pages( array(
                            'before' => '<div class="page-links"><span class="page-links-title">' . esc_html__( 'Pages:', 'iceberg' ) . '</span>',
                            'after'  => '</div>',
                        ) );
                    ?>

                    <?php iceberg_the_post_footer( get_theme_mod( 'display_tags_list', 1 ), get_theme_mod( 'display_share_buttons', 1 ), '<footer class="entry-footer">', '</footer>' ); ?>

                </div><!-- .content-container -->
            </div><!-- .inner-box -->

        </article><!-- #post-## -->

        <?php iceberg_render_ad( 'below_single_content' ); ?>

        <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

            // Previous/next post navigation.
            get_template_part( 'template-parts/navigation' );

        endwhile; ?>

        </main><!-- .site-main -->

    </div><!-- .content-area -->

<?php get_footer(); ?>
