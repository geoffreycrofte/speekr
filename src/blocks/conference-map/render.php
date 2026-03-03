<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Cheatin\' uh?' ); }

$height = isset( $attributes['height'] ) ? (int) $attributes['height'] : 450;

$conferences = get_posts( array(
    'post_type'      => 'speekr_conference',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
    'meta_query'     => array(
        array(
            'key'     => '_speekr_conf_lat',
            'compare' => 'EXISTS',
        ),
    ),
) );

$map_data = array();
foreach ( $conferences as $conf ) {
    $conf_id  = $conf->ID;
    $lat      = get_post_meta( $conf_id, '_speekr_conf_lat', true );
    $lng      = get_post_meta( $conf_id, '_speekr_conf_lng', true );

    if ( ! $lat || ! $lng ) continue;

    $talk_ref_id  = (int) get_post_meta( $conf_id, '_speekr_conf_talk_ref', true );
    $talk_title   = '';
    $talk_url     = '';
    if ( $talk_ref_id ) {
        $talk_title = get_the_title( $talk_ref_id );
        $talk_as_article = get_post_meta( $talk_ref_id, 'speekr-as-article', true );
        if ( 'on' === $talk_as_article ) {
            $talk_url = get_permalink( $talk_ref_id );
        }
    }

    $map_data[] = array(
        'lat'       => (float) $lat,
        'lng'       => (float) $lng,
        'name'      => get_the_title( $conf_id ),
        'date'      => get_post_meta( $conf_id, '_speekr_conf_date', true ),
        'city'      => get_post_meta( $conf_id, '_speekr_conf_city', true ),
        'eventUrl'  => get_post_meta( $conf_id, '_speekr_conf_url', true ),
        'talkTitle' => $talk_title,
        'talkUrl'   => $talk_url,
    );
}

$json_data = wp_json_encode( $map_data );
do_action( 'speekr_before_map', 0, $attributes );
?>
<div class="wp-block-speekr-conference-map"
     data-speekr-map="<?php echo esc_attr( $json_data ); ?>"
     style="height: <?php echo esc_attr( $height ); ?>px;">
    <?php if ( empty( $map_data ) ) : ?>
        <p class="speekr-conference-map__empty">
            <?php esc_html_e( 'No conference locations to display yet.', 'speekr' ); ?>
        </p>
    <?php endif; ?>
</div>
<?php
do_action( 'speekr_after_map', 0, $attributes );
