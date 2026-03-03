<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Cheatin\' uh?' ); }

$layout         = isset( $attributes['layout'] ) ? $attributes['layout'] : 'side-by-side';
$allow_download = ! empty( $attributes['allowDownload'] );
$layout_class   = ( 'stacked' === $layout ) ? 'speekr-speaker-profile--stacked' : 'speekr-speaker-profile--side-by-side';

// Smart speaker resolution (priority order):
// 1. Explicit speakerId attribute set in editor
// 2. On a talks CPT singular with _speekr_talk_speaker meta set
// 3. Only one speekr_speaker post exists → auto-select
// 4. Nothing found → return empty
$speaker_id = isset( $attributes['speakerId'] ) ? (int) $attributes['speakerId'] : 0;

if ( ! $speaker_id ) {
    $current_id = get_the_ID();
    if ( $current_id && 'talks' === get_post_type( $current_id ) ) {
        $talk_speaker = (int) get_post_meta( $current_id, '_speekr_talk_speaker', true );
        if ( $talk_speaker ) {
            $speaker_id = $talk_speaker;
        }
    }
}

if ( ! $speaker_id ) {
    $all_speakers = get_posts( array(
        'post_type'      => 'speekr_speaker',
        'posts_per_page' => 2,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    if ( 1 === count( $all_speakers ) ) {
        $speaker_id = $all_speakers[0];
    }
}

if ( ! $speaker_id ) {
    if ( current_user_can( 'edit_posts' ) ) {
        echo '<p class="speekr-speaker-profile--no-context" style="color:#757575;font-style:italic;padding:1em;border:1px dashed #ccc;">'
            . esc_html__( 'Speaker Profile: select a speaker in the block settings sidebar, or link a speaker to this talk.', 'speekr' )
            . '</p>';
    }
    return;
}

$post_id      = $speaker_id;
$headshots    = get_post_meta( $post_id, '_speekr_headshots', true ) ?: array();
$bio_short    = get_post_meta( $post_id, '_speekr_bio_short', true ) ?: '';
$social_links = get_post_meta( $post_id, '_speekr_social_links', true ) ?: array();
$rider        = get_post_meta( $post_id, '_speekr_rider', true ) ?: array();
$post_content = get_post_field( 'post_content', $post_id );
$speaker_name = get_the_title( $post_id );

// Human-readable platform names for screen-reader-text (mirrors PLATFORMS in edit.js).
$platform_names = array(
    'linkedin'  => 'LinkedIn',
    'facebook'  => 'Facebook',
    'instagram' => 'Instagram',
    'bluesky'   => 'Bluesky',
    'mastodon'  => 'Mastodon',
    'x'         => 'X',
    'twitter'   => 'Twitter',
    'github'    => 'GitHub',
    'youtube'   => 'YouTube',
    'personal'  => __( 'Personal website', 'speekr' ),
    'website'   => __( 'Website', 'speekr' ),
);

// Inline SVG icon map keyed by platform slug (lowercase).
$platform_icons = array(
    'linkedin'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 0H5C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5zM8 19H5V8h3v11zM6.5 6.7A1.8 1.8 0 1 1 6.5 3.1a1.8 1.8 0 0 1 0 3.6zM20 19h-3v-5.6c0-1.34-.03-3.07-1.87-3.07-1.87 0-2.16 1.46-2.16 2.97V19h-3V8h2.88v1.5h.04c.4-.76 1.38-1.56 2.83-1.56 3.02 0 3.58 1.99 3.58 4.57V19z"/></svg>',
    'twitter'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.95 4.57a10 10 0 0 1-2.82.77 4.96 4.96 0 0 0 2.16-2.72c-.95.55-2 .95-3.12 1.19a4.92 4.92 0 0 0-8.38 4.49A13.96 13.96 0 0 1 1.64 3.16a4.92 4.92 0 0 0 1.52 6.57 4.9 4.9 0 0 1-2.23-.61v.06a4.92 4.92 0 0 0 3.95 4.83 4.94 4.94 0 0 1-2.22.08 4.93 4.93 0 0 0 4.6 3.42A9.87 9.87 0 0 1 0 19.54a13.94 13.94 0 0 0 7.55 2.21c9.06 0 14.01-7.5 14.01-14.01 0-.21 0-.42-.01-.63A9.94 9.94 0 0 0 24 4.59l-.05-.02z"/></svg>',
    'x'           => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    'github'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.757-1.333-1.757-1.09-.745.083-.729.083-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
    'instagram'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
    'youtube'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>',
    'mastodon'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z"/></svg>',
    'bluesky'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.204-.659-.3-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z"/></svg>',
    'website'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
);
$generic_link_icon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';

?>
<div class="wp-block-speekr-speaker-profile <?php echo esc_attr( $layout_class ); ?>">

    <?php if ( ! empty( $headshots ) ) : ?>
    <div class="speekr-profile__headshots">
        <?php foreach ( $headshots as $hs ) :
            $img_id    = isset( $hs['id'] ) ? (int) $hs['id'] : 0;
            $img_label = isset( $hs['label'] ) ? esc_attr( $hs['label'] ) : esc_attr( $speaker_name );
            if ( ! $img_id ) continue;
            echo wp_get_attachment_image( $img_id, 'medium', false, array(
                'class' => 'speekr-headshot',
                'alt'   => $img_label,
            ) );
        endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="speekr-profile__content">
        <h2 class="speekr-profile__name"><?php echo esc_html( $speaker_name ); ?></h2>

        <?php if ( $bio_short ) : ?>
        <p class="speekr-profile__bio-short"><?php echo esc_html( $bio_short ); ?></p>
        <?php endif; ?>

        <?php if ( $post_content ) : ?>
        <div class="speekr-profile__bio-full">
            <?php echo apply_filters( 'the_content', $post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $social_links ) ) : ?>
        <ul class="speekr-profile__social-links" aria-label="<?php esc_attr_e( 'Social links', 'speekr' ); ?>">
            <?php foreach ( $social_links as $link ) :
                $platform = isset( $link['platform'] ) ? strtolower( sanitize_key( $link['platform'] ) ) : '';
                $url      = isset( $link['url'] ) ? esc_url( $link['url'] ) : '';
                $label    = ! empty( $link['label'] )
                    ? esc_html( $link['label'] )
                    : esc_html( $platform_names[ $platform ] ?? ucfirst( $platform ) );
                if ( ! $url ) continue;
                $icon = isset( $platform_icons[ $platform ] ) ? $platform_icons[ $platform ] : $generic_link_icon;
            ?>
            <li class="speekr-social-link">
                <a href="<?php echo $url; ?>" rel="noopener noreferrer" target="_blank" class="speekr-social-link__anchor">
                    <?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG map ?>
                    <span class="screen-reader-text"><?php echo $label; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php
        $rider_fields = array(
            'av'            => __( 'A/V Requirements', 'speekr' ),
            'travel'        => __( 'Travel', 'speekr' ),
            'dietary'       => __( 'Dietary', 'speekr' ),
            'accessibility' => __( 'Accessibility', 'speekr' ),
        );
        $has_rider = false;
        foreach ( $rider_fields as $key => $label ) {
            if ( ! empty( $rider[ $key ] ) ) { $has_rider = true; break; }
        }
        if ( $has_rider ) : ?>
        <div class="speekr-profile__rider">
            <h3><?php esc_html_e( 'Speaker Preferences', 'speekr' ); ?></h3>
            <?php foreach ( $rider_fields as $key => $label ) :
                if ( empty( $rider[ $key ] ) ) continue; ?>
            <div class="speekr-rider-section">
                <h4><?php echo esc_html( $label ); ?></h4>
                <p><?php echo wp_kses_post( nl2br( $rider[ $key ] ) ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $allow_download ) : ?>
        <div class="speekr-profile__press-kit">
            <a href="<?php
                $token = hash_hmac( 'sha256', 'speekr-kit|' . $post_id, AUTH_KEY );
                echo esc_url( add_query_arg( 'token', $token, rest_url( 'speekr/v1/press-kit/' . $post_id ) ) );
            ?>"
               class="speekr-press-kit-download wp-block-button__link">
                <?php esc_html_e( 'Download press kit', 'speekr' ); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>
