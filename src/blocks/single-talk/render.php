<?php
if ( ! defined( 'ABSPATH' ) ) { die( 'Cheatin\' uh?' ); }

// Smart talk resolution (priority order):
// 1. Explicit talkId attribute set in editor
// 2. Current post is a talks CPT → use get_the_ID()
// 3. Neither → prompt to select in sidebar
$post_id = isset( $attributes['talkId'] ) ? (int) $attributes['talkId'] : 0;

if ( ! $post_id ) {
	$current_id = get_the_ID();
	if ( $current_id && speekr_get_cpt_slug() === get_post_type( $current_id ) ) {
		$post_id = $current_id;
	}
}

if ( ! $post_id ) {
	echo '<p class="speekr-single-talk--no-context">'
		. esc_html__( 'Select a talk in the block settings sidebar.', 'speekr' )
		. '</p>';
	return;
}

// Meta reads — new per-key fields (Phase 3 refactor)
$youtube     = get_post_meta( $post_id, '_speekr_media_youtube', true );
$vimeo       = get_post_meta( $post_id, '_speekr_media_vimeo', true );
$dailymotion = get_post_meta( $post_id, '_speekr_media_dailymotion', true );
$speakerdeck = get_post_meta( $post_id, '_speekr_media_speakerdeck', true );
$slides      = get_post_meta( $post_id, '_speekr_media_slides', true );
$slideshare  = get_post_meta( $post_id, '_speekr_media_slideshare', true );
$other_links = get_post_meta( $post_id, '_speekr_media_other', true ) ?: array();
$summary     = get_post_meta( $post_id, 'speekr-summary', true ) ?: '';
$as_article  = get_post_meta( $post_id, 'speekr-as-article', true );
$is_blog     = ( 'on' === $as_article );
$post_content = get_post_field( 'post_content', $post_id );
$title       = get_the_title( $post_id );

// Conference reference — the talk stores _speekr_conf_talk_ref as a Conference post ID.
$conf_ref_id  = (int) get_post_meta( $post_id, '_speekr_conf_talk_ref', true );
$conf_name    = '';
$conf_url     = '';
$conf_date    = '';
$conf_city    = '';
$conf_country = '';
if ( $conf_ref_id && 'speekr_conference' === get_post_type( $conf_ref_id ) ) {
	$conf_name    = get_the_title( $conf_ref_id );
	$conf_url     = get_post_meta( $conf_ref_id, '_speekr_conf_url', true );
	$conf_date    = get_post_meta( $conf_ref_id, '_speekr_conf_date', true );
	$conf_city    = get_post_meta( $conf_ref_id, '_speekr_conf_city', true );
	$conf_country = get_post_meta( $conf_ref_id, '_speekr_conf_country', true );
}

// Primary video embed — try each in priority order.
$primary_embed = false;
$video_sources = array_filter( array( $youtube, $vimeo, $dailymotion ) );
foreach ( $video_sources as $video_url ) {
	$embed = wp_oembed_get( $video_url, array( 'width' => 800 ) );
	if ( $embed ) {
		$primary_embed = $embed;
		break;
	}
}

// Fallback media for the primary area.
$fallback_img = '';
if ( ! $primary_embed ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$fallback_img = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'speekr-talk-media__img' ) );
	} else {
		$fallback_img = '<img src="' . esc_url( SPEEKR_PLUGIN_URL . 'assets/img/placeholder-talk.svg' )
			. '" alt="" aria-hidden="true" class="speekr-talk-media__img" />';
	}
}

// Resources section items
$resources = array();
if ( $speakerdeck ) {
	$resources[] = array( 'label' => __( 'View on SpeakerDeck', 'speekr' ), 'url' => $speakerdeck );
}
if ( $slides ) {
	$resources[] = array( 'label' => __( 'View Slides', 'speekr' ), 'url' => $slides );
}
if ( $slideshare ) {
	$resources[] = array( 'label' => __( 'View on Slideshare', 'speekr' ), 'url' => $slideshare );
}
foreach ( $other_links as $link ) {
	$l_label = isset( $link['label'] ) ? $link['label'] : __( 'Link', 'speekr' );
	$l_url   = isset( $link['url'] ) ? $link['url'] : '';
	if ( $l_url ) {
		$resources[] = array( 'label' => $l_label, 'url' => $l_url );
	}
}

do_action( 'speekr_before_single_talk', $post_id, $attributes );
?>
<div class="wp-block-speekr-single-talk">

	<div class="speekr-talk__media">
		<?php if ( $primary_embed ) : ?>
		<div class="speekr-talk__video-wrapper">
			<?php echo $primary_embed; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_oembed_get output ?>
		</div>
		<?php else : ?>
		<div class="speekr-talk__image-wrapper">
			<?php echo $fallback_img; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image output ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="speekr-talk__body">

		<?php if ( $summary ) : ?>
		<p class="speekr-talk__summary"><?php echo esc_html( $summary ); ?></p>
		<?php endif; ?>

		<?php if ( $post_content ) : ?>
		<div class="speekr-talk__content">
			<?php echo apply_filters( 'the_content', $post_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php endif; ?>

		<?php if ( $conf_name || $conf_date || $conf_city ) : ?>
		<div class="speekr-talk__conference">
			<h3 class="speekr-talk__conference-heading"><?php esc_html_e( 'Conference', 'speekr' ); ?></h3>
			<?php if ( $conf_url ) : ?>
			<a href="<?php echo esc_url( $conf_url ); ?>"
			   rel="noopener noreferrer" target="_blank"
			   class="speekr-talk__conf-link">
				<?php echo esc_html( $conf_name ); ?>
			</a>
			<?php elseif ( $conf_name ) : ?>
			<span class="speekr-talk__conf-name"><?php echo esc_html( $conf_name ); ?></span>
			<?php endif; ?>

			<?php if ( $conf_date || $conf_city ) : ?>
			<p class="speekr-talk__conf-meta">
				<?php if ( $conf_date ) : ?>
					<time datetime="<?php echo esc_attr( $conf_date ); ?>"><?php echo esc_html( $conf_date ); ?></time>
				<?php endif; ?>
				<?php if ( $conf_date && ( $conf_city || $conf_country ) ) : ?>&mdash;<?php endif; ?>
				<?php if ( $conf_city || $conf_country ) : ?>
					<?php echo esc_html( trim( $conf_city . ( $conf_city && $conf_country ? ', ' : '' ) . $conf_country ) ); ?>
				<?php endif; ?>
			</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $resources ) ) : ?>
		<div class="speekr-talk__resources">
			<h3 class="speekr-talk__resources-heading"><?php esc_html_e( 'Resources', 'speekr' ); ?></h3>
			<ul class="speekr-talk__resources-list">
				<?php foreach ( $resources as $resource ) : ?>
				<li>
					<a href="<?php echo esc_url( $resource['url'] ); ?>"
					   rel="noopener noreferrer" target="_blank">
						<?php echo esc_html( $resource['label'] ); ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<?php if ( $is_blog ) : ?>
		<p class="speekr-talk__permalink">
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
			   class="speekr-talk__blog-link wp-block-button__link">
				<?php esc_html_e( 'Read full article', 'speekr' ); ?>
			</a>
		</p>
		<?php endif; ?>

	</div><!-- .speekr-talk__body -->
</div><!-- .wp-block-speekr-single-talk -->
<?php
do_action( 'speekr_after_single_talk', $post_id, $attributes );
