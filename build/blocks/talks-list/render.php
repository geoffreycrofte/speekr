<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Cheatin\' uh?' ); }

$layout       = isset( $attributes['layout'] ) ? $attributes['layout'] : 'grid';
$layout_class = ( 'list' === $layout ) ? 'speekr-talks-list--list' : 'speekr-talks-list--grid';

// Query all published talks ordered by date descending.
$talks_query = new WP_Query( array(
    'post_type'      => speekr_get_cpt_slug(), // 'talks'
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
) );

// Empty state
if ( ! $talks_query->have_posts() ) {
    echo '<div class="wp-block-speekr-talks-list speekr-talks-list--empty">';
    if ( current_user_can( 'edit_posts' ) ) {
        $new_talk_url = admin_url( 'post-new.php?post_type=' . speekr_get_cpt_slug() );
        echo '<p>' . esc_html__( 'No talks yet.', 'speekr' ) . ' <a href="' . esc_url( $new_talk_url ) . '">' . esc_html__( 'Create your first talk', 'speekr' ) . '</a>.</p>';
    } else {
        echo '<p>' . esc_html__( 'No talks to display yet — check back soon.', 'speekr' ) . '</p>';
    }
    echo '</div>';
    return;
}

// Collect all unique topics from query results for filter tabs.
$all_topics     = array();
$talk_topic_map = array(); // post_id => array of slugs

while ( $talks_query->have_posts() ) {
    $talks_query->the_post();
    $pid   = get_the_ID();
    $terms = wp_get_post_terms( $pid, 'speekr_topic', array( 'fields' => 'all' ) );
    $slugs = array();
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $slugs[] = $term->slug;
            if ( ! isset( $all_topics[ $term->slug ] ) ) {
                $all_topics[ $term->slug ] = esc_html( $term->name );
            }
        }
    }
    $talk_topic_map[ $pid ] = $slugs;
}
wp_reset_postdata();

/**
 * Resolve the best media visual for a talk card.
 * Priority: YouTube thumb > Vimeo thumb (via oEmbed, cached) > slide player embed >
 *           post featured image > generic placeholder.
 *
 * Stored as a static closure (not a named function) so re-rendering the block in the
 * same request (editor preview, etc.) does not trigger a "Cannot redeclare" fatal.
 *
 * @param int $post_id Talk post ID.
 * @return array { type: string, url: string }
 */
$resolve_card_media = static function( $post_id ) {
    // 1. Post featured image (cover) — highest priority.
    if ( has_post_thumbnail( $post_id ) ) {
        return array( 'type' => 'featured_image', 'url' => get_the_post_thumbnail_url( $post_id, 'medium' ) );
    }
    // 2. YouTube thumbnail.
    $yt = get_post_meta( $post_id, '_speekr_media_youtube', true );
    if ( $yt ) {
        $vid_id = speekr_get_youtube_id( $yt );
        if ( $vid_id ) {
            return array( 'type' => 'thumbnail', 'url' => 'https://img.youtube.com/vi/' . $vid_id . '/hqdefault.jpg' );
        }
    }
    // 3. Vimeo thumbnail (via oEmbed, WordPress caches result as transient).
    $vim = get_post_meta( $post_id, '_speekr_media_vimeo', true );
    if ( $vim ) {
        $oembed_url = 'https://vimeo.com/api/oembed.json?url=' . urlencode( $vim );
        $response   = wp_remote_get( $oembed_url, array( 'timeout' => 5 ) );
        if ( ! is_wp_error( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data['thumbnail_url'] ) ) {
                return array( 'type' => 'thumbnail', 'url' => $data['thumbnail_url'] );
            }
        }
    }
    // 4. SpeakerDeck — embed via WordPress oEmbed (result cached as transient).
    $spd = get_post_meta( $post_id, '_speekr_media_speakerdeck', true );
    if ( $spd ) {
        return array( 'type' => 'speakerdeck_embed', 'url' => $spd );
    }
    // 5. Generic placeholder.
    return array( 'type' => 'placeholder', 'url' => SPEEKR_PLUGIN_URL . 'assets/img/placeholder-talk.svg' );
};

do_action( 'speekr_before_talks_list', 0, $attributes );
?>
<div class="wp-block-speekr-talks-list <?php echo esc_attr( $layout_class ); ?>">

    <?php if ( ! empty( $all_topics ) ) : ?>
    <div class="speekr-talks-filter" role="tablist" aria-label="<?php esc_attr_e( 'Filter by topic', 'speekr' ); ?>">
        <button class="speekr-topic-pill is-active"
                data-topic="all"
                aria-pressed="true"
                role="tab">
            <?php esc_html_e( 'All', 'speekr' ); ?>
        </button>
        <?php foreach ( $all_topics as $slug => $name ) : ?>
        <button class="speekr-topic-pill"
                data-topic="<?php echo esc_attr( $slug ); ?>"
                aria-pressed="false"
                role="tab">
            <?php echo esc_html( $name ); ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="speekr-talks-cards">

    <?php
    $talks_query->rewind_posts();
    while ( $talks_query->have_posts() ) :
        $talks_query->the_post();
        $pid        = get_the_ID();
        $title      = get_the_title();
        $summary    = get_post_meta( $pid, 'speekr-summary', true ) ?: '';
        $as_article = get_post_meta( $pid, 'speekr-as-article', true );
        $is_blog    = ( 'on' !== $as_article );
        $permalink  = get_permalink();

        // Date + location: prefer linked Conference CPT, fall back to speekr-conf object.
        $conf_talk_ref  = (int) get_post_meta( $pid, '_speekr_conf_talk_ref', true );
        $conf_date      = '';
        $conf_location  = '';
        if ( $conf_talk_ref ) {
            // _speekr_conf_talk_ref on the Talk points to a Conference post.
            // The Conference CPT stores meta on the conference, not the talk.
            // Find the conference that references this talk via _speekr_conf_talk_ref.
            // (The talk stores the link ID, conference also stores _speekr_conf_talk_ref = talk ID.)
            $conf_post = get_post( $conf_talk_ref );
            if ( $conf_post && 'speekr_conference' === get_post_type( $conf_post ) ) {
                $conf_date     = get_post_meta( $conf_post->ID, '_speekr_conf_date', true );
                $conf_city     = get_post_meta( $conf_post->ID, '_speekr_conf_city', true );
                $conf_country  = get_post_meta( $conf_post->ID, '_speekr_conf_country', true );
                $conf_location = trim( $conf_city . ( $conf_city && $conf_country ? ', ' : '' ) . $conf_country );
            }
        }
        if ( ! $conf_date ) {
            // Legacy fallback: speekr-conf object on the Talk.
            $speekr_conf = get_post_meta( $pid, 'speekr-conf', true );
            if ( is_array( $speekr_conf ) && ! empty( $speekr_conf['name'] ) ) {
                $conf_location = $speekr_conf['name'];
            }
        }

        $media       = $resolve_card_media( $pid );
        $topic_slugs = $talk_topic_map[ $pid ] ?? array();
        $topics_attr = esc_attr( implode( ',', $topic_slugs ) );
    ob_start();
    ?>
        <article class="speekr-talk-card"
                 data-topics="<?php echo $topics_attr; ?>">

            <div class="speekr-talk-card__media">
                <?php if ( 'thumbnail' === $media['type'] || 'featured_image' === $media['type'] ) : ?>
                    <a href="<?php echo esc_url( $permalink ); ?>">
                        <img src="<?php echo esc_url( $media['url'] ); ?>"
                             alt="<?php echo esc_attr( $title ); ?>"
                             loading="lazy" />
                    </a>
                <?php elseif ( 'speakerdeck_embed' === $media['type'] ) : ?>
                    <?php $embed_html = wp_oembed_get( $media['url'], array( 'maxwidth' => 400 ) ); ?>
                    <?php if ( $embed_html ) : ?>
                        <div class="speekr-slides-embed"><?php echo $embed_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $permalink ); ?>">
                            <img src="<?php echo esc_url( SPEEKR_PLUGIN_URL . 'assets/img/placeholder-talk.svg' ); ?>"
                                 alt="" aria-hidden="true" loading="lazy" />
                        </a>
                    <?php endif; ?>
                <?php else : // placeholder ?>
                    <a href="<?php echo esc_url( $permalink ); ?>">
                        <img src="<?php echo esc_url( $media['url'] ); ?>"
                             alt="" aria-hidden="true" loading="lazy" />
                    </a>
                <?php endif; ?>
            </div>

            <div class="speekr-talk-card__body">
                <h3 class="speekr-talk-card__title">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                </h3>

                <?php if ( $summary ) : ?>
                <p class="speekr-talk-card__summary"><?php echo esc_html( $summary ); ?></p>
                <?php endif; ?>

                <?php if ( $conf_date || $conf_location ) : ?>
                <p class="speekr-talk-card__meta">
                    <?php if ( $conf_date ) : ?>
                        <time datetime="<?php echo esc_attr( $conf_date ); ?>">
                            <?php echo esc_html( $conf_date ); ?>
                        </time>
                    <?php endif; ?>
                    <?php if ( $conf_date && $conf_location ) : ?>&mdash;<?php endif; ?>
                    <?php if ( $conf_location ) : ?>
                        <span class="speekr-talk-card__location"><?php echo esc_html( $conf_location ); ?></span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if ( $is_blog ) : ?>
                <a href="<?php echo esc_url( $permalink ); ?>"
                   class="speekr-talk-card__readmore wp-block-button__link">
                    <?php esc_html_e( 'Read more', 'speekr' ); ?>
                </a>
                <?php endif; ?>
            </div>

        </article>
    <?php
    $talk_html = ob_get_clean();
    $talk_post = get_post( $pid );
    echo apply_filters( 'speekr_talk_output', $talk_html, $talk_post, $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput
    endwhile;
    wp_reset_postdata();
    ?>

    </div><!-- .speekr-talks-cards -->
</div><!-- .wp-block-speekr-talks-list -->
<?php
do_action( 'speekr_after_talks_list', 0, $attributes );
