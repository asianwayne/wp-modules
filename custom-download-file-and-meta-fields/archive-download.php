<?php
/**
 * The template for displaying the Downloads archive
 *
 * @since Iceberg 1.0
 */

get_header(); ?>

<div id="primary" class="content-area">

    <main id="main" class="site-main">

        <?php iceberg_render_ad('above_posts_loop'); ?>

        <?php if (have_posts()): ?>

            <header class="page-header inner-box-small">
                <div class="content-container">
                    <h1 class="page-title">
                        <?php esc_html_e('Downloads', 'iceberg'); ?>
                    </h1>
                    <?php the_archive_description('<div class="taxonomy-description">', '</div>'); ?>
                </div>
            </header><!-- .page-header -->

            <div class="download-archive-list inner-box">
                <div class="content-container">

                    <?php while (have_posts()):
                        the_post();
                        $files = get_post_meta(get_the_ID(), '_iceberg_download_files', true);
                        $file_count = is_array($files) ? count($files) : 0;
                        ?>

                        <article id="post-<?php the_ID(); ?>" <?php post_class('download-card'); ?>>

                            <?php if (has_post_thumbnail()): ?>
                                <div class="download-card-thumb">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('iceberg-medium-square-thumbnail'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="download-card-body">
                                <header class="download-card-header">
                                    <?php the_title(sprintf('<h2 class="download-card-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?>
                                </header>

                                <?php if (has_excerpt()): ?>
                                    <div class="download-card-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>

                                <footer class="download-card-footer">
                                    <?php if ($file_count > 0): ?>
                                        <span class="download-file-count">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                            <?php printf(
                                                _n('%d file', '%d files', $file_count, 'iceberg'),
                                                $file_count
                                            ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($file_count > 0):
                                        // Collect formats
                                        $formats = array();
                                        foreach ($files as $f) {
                                            if (!empty($f['format'])) {
                                                $formats[] = strtoupper($f['format']);
                                            }
                                        }
                                        $formats = array_unique($formats);
                                        if (!empty($formats)): ?>
                                            <span class="download-formats">
                                                <?php foreach ($formats as $fmt): ?>
                                                    <span class="format-badge">
                                                        <?php echo esc_html($fmt); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <span class="download-date">
                                        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                            <?php echo esc_html(get_the_date()); ?>
                                        </time>
                                    </span>
                                </footer>
                            </div>

                        </article><!-- .download-card -->

                    <?php endwhile; ?>

                </div><!-- .content-container -->
            </div><!-- .download-archive-list -->

            <?php get_template_part('template-parts/pagination'); ?>

        <?php else:
            get_template_part('template-parts/content', 'none');
        endif; ?>

    </main><!-- .site-main -->

</div><!-- .content-area -->

<?php get_footer(); ?>