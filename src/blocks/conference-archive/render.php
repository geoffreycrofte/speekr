<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Cheatin\' uh?' ); }

$conferences = get_posts( array(
    'post_type'      => 'speekr_conference',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'meta_value',
    'meta_key'       => '_speekr_conf_date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
) );

if ( empty( $conferences ) ) {
    echo '<p class="speekr-conference-archive--empty">'
        . esc_html__( 'No conference appearances yet.', 'speekr' )
        . '</p>';
    return;
}
do_action( 'speekr_before_conference_archive', 0, $attributes );
?>
<div class="wp-block-speekr-conference-archive">
    <table class="speekr-conference-table">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e( 'Conference', 'speekr' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Date', 'speekr' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Location', 'speekr' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Talk', 'speekr' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $conferences as $conf ) :
            $conf_id      = $conf->ID;
            $conf_name    = get_the_title( $conf_id );
            $conf_url     = get_post_meta( $conf_id, '_speekr_conf_url', true );
            $conf_date    = get_post_meta( $conf_id, '_speekr_conf_date', true );
            $conf_city    = get_post_meta( $conf_id, '_speekr_conf_city', true );
            $conf_country = get_post_meta( $conf_id, '_speekr_conf_country', true );
            $conf_location = trim( $conf_city . ( $conf_city && $conf_country ? ', ' : '' ) . $conf_country );
            $talk_ref_id  = (int) get_post_meta( $conf_id, '_speekr_conf_talk_ref', true );
            $talk_title   = '';
            $talk_url     = '';
            if ( $talk_ref_id ) {
                $talk_title = get_the_title( $talk_ref_id );
                $talk_as_article = get_post_meta( $talk_ref_id, 'speekr-as-article', true );
                if ( 'on' !== $talk_as_article ) {
                    $talk_url = get_permalink( $talk_ref_id );
                }
            }
        ?>
            <tr>
                <td>
                    <?php if ( $conf_url ) : ?>
                        <a href="<?php echo esc_url( $conf_url ); ?>"
                           rel="noopener noreferrer" target="_blank">
                            <?php echo esc_html( $conf_name ); ?>
                        </a>
                    <?php else : ?>
                        <?php echo esc_html( $conf_name ); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ( $conf_date ) : ?>
                        <time datetime="<?php echo esc_attr( $conf_date ); ?>">
                            <?php echo esc_html( $conf_date ); ?>
                        </time>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html( $conf_location ); ?></td>
                <td>
                    <?php if ( $talk_title ) : ?>
                        <?php if ( $talk_url ) : ?>
                            <a href="<?php echo esc_url( $talk_url ); ?>"><?php echo esc_html( $talk_title ); ?></a>
                        <?php else : ?>
                            <?php echo esc_html( $talk_title ); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
do_action( 'speekr_after_conference_archive', 0, $attributes );
