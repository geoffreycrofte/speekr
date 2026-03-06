<?php
/**
 * Single Conference Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_singular( 'speekr_conference' )
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/single-conference.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();

$conf_id      = get_the_ID();
$conf_name    = get_the_title( $conf_id );
$conf_url     = get_post_meta( $conf_id, '_speekr_conf_url', true );
$conf_date    = get_post_meta( $conf_id, '_speekr_conf_date', true );
$conf_city    = get_post_meta( $conf_id, '_speekr_conf_city', true );
$conf_country = get_post_meta( $conf_id, '_speekr_conf_country', true );
$conf_location = trim( $conf_city . ( $conf_city && $conf_country ? ', ' : '' ) . $conf_country );
$conf_content = get_post_field( 'post_content', $conf_id );
$conf_thumb   = get_the_post_thumbnail( $conf_id, 'large', array( 'class' => 'speekr-conf-single__banner-img' ) );

// Collect talk IDs linked to this conference:
// 1. The conference's own _speekr_conf_talk_ref (Talk post ID stored on Conference).
$talk_ids = array();
$linked_talk_id = (int) get_post_meta( $conf_id, '_speekr_conf_talk_ref', true );
if ( $linked_talk_id && 'talks' === get_post_type( $linked_talk_id ) ) {
	$talk_ids[] = $linked_talk_id;
}
// 2. Talks that store this conference's ID in their own _speekr_conf_talk_ref meta.
$reverse_talks = get_posts( array(
	'post_type'      => 'talks',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'fields'         => 'ids',
	'no_found_rows'  => true,
	'meta_query'     => array(
		array(
			'key'     => '_speekr_conf_talk_ref',
			'value'   => $conf_id,
			'compare' => '=',
			'type'    => 'NUMERIC',
		),
	),
) );
foreach ( $reverse_talks as $rt_id ) {
	if ( ! in_array( (int) $rt_id, $talk_ids, true ) ) {
		$talk_ids[] = (int) $rt_id;
	}
}

// Speaker IDs — from conference _speekr_conf_speakers, plus talk speakers.
$speaker_ids = get_post_meta( $conf_id, '_speekr_conf_speakers', true );
if ( ! is_array( $speaker_ids ) ) {
	$speaker_ids = array();
}
foreach ( $talk_ids as $t_id ) {
	$spk = (int) get_post_meta( $t_id, '_speekr_talk_speaker', true );
	if ( $spk && ! in_array( $spk, $speaker_ids, true ) ) {
		$speaker_ids[] = $spk;
	}
}
$speaker_ids = array_filter( array_map( 'intval', $speaker_ids ) );
?>
<div class="speekr-classic-template speekr-conf-single">
	<div class="container wide-max-width entry-content">
		<?php if ( $conf_thumb ) : ?>
		<div class="speekr-conf-single__banner">
			<?php echo $conf_thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php endif; ?>

		<div class="speekr-conf-single__header">
			<div class="container wide-max-width">
				<h1 class="speekr-conf-single__title">
					<?php if ( $conf_url ) : ?>
					<a href="<?php echo esc_url( $conf_url ); ?>" rel="noopener noreferrer" target="_blank">
						<?php echo esc_html( $conf_name ); ?>
						<span class="speekr-conf-single__ext-icon" aria-hidden="true">↗</span>
					</a>
					<?php else : ?>
					<?php echo esc_html( $conf_name ); ?>
					<?php endif; ?>
				</h1>

				<dl class="speekr-conf-single__meta">
					<?php if ( $conf_date ) : ?>
					<div class="speekr-conf-single__meta-item">
						<dt><?php esc_html_e( 'Date', 'speekr' ); ?></dt>
						<dd><time datetime="<?php echo esc_attr( $conf_date ); ?>"><?php echo esc_html( $conf_date ); ?></time></dd>
					</div>
					<?php endif; ?>

					<?php if ( $conf_city ) : ?>
					<div class="speekr-conf-single__meta-item">
						<dt><?php esc_html_e( 'City', 'speekr' ); ?></dt>
						<dd><?php echo esc_html( $conf_city ); ?></dd>
					</div>
					<?php endif; ?>

					<?php if ( $conf_country ) : ?>
					<div class="speekr-conf-single__meta-item">
						<dt><?php esc_html_e( 'Country', 'speekr' ); ?></dt>
						<dd><?php echo esc_html( $conf_country ); ?></dd>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $talk_ids ) ) : ?>
					<div class="speekr-conf-single__meta-item">
						<dt><?php esc_html_e( 'Talks', 'speekr' ); ?></dt>
						<dd><?php echo (int) count( $talk_ids ); ?></dd>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $speaker_ids ) ) : ?>
					<div class="speekr-conf-single__meta-item">
						<dt><?php echo 1 === count( $speaker_ids ) ? esc_html__( 'Speaker', 'speekr' ) : esc_html__( 'Speakers', 'speekr' ); ?></dt>
						<dd><?php echo (int) count( $speaker_ids ); ?></dd>
					</div>
					<?php endif; ?>
				</dl>
			</div>
		</div>

		<div class="speekr-conf-single__body container wide-max-width">

			<?php if ( $conf_content ) : ?>
			<div class="speekr-conf-single__description">
				<?php echo apply_filters( 'the_content', $conf_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $talk_ids ) ) : ?>
			<section class="speekr-conf-single__talks">
				<h2 class="speekr-conf-single__section-title"><?php esc_html_e( 'Talks', 'speekr' ); ?></h2>
				<ul class="speekr-conf-talk-list">
				<?php foreach ( $talk_ids as $t_id ) :
					$t_id        = (int) $t_id;
					$t_title     = get_the_title( $t_id );
					$t_summary   = get_post_meta( $t_id, 'speekr-summary', true );
					$t_as_article = get_post_meta( $t_id, 'speekr-as-article', true );
					$t_url       = ( 'on' !== $t_as_article ) ? get_permalink( $t_id ) : '';
					$t_thumb     = get_the_post_thumbnail( $t_id, 'medium', array( 'class' => 'speekr-talk-card__img' ) );

					// Speaker for this talk.
					$t_spk_id    = (int) get_post_meta( $t_id, '_speekr_talk_speaker', true );
					$t_spk_name  = '';
					$t_spk_url   = '';
					$t_spk_img   = '';
					if ( $t_spk_id && 'speekr_speaker' === get_post_type( $t_spk_id ) ) {
						$t_spk_name = get_the_title( $t_spk_id );
						$t_spk_url  = get_permalink( $t_spk_id );
						$t_spk_hss  = get_post_meta( $t_spk_id, '_speekr_headshots', true ) ?: array();
						if ( ! empty( $t_spk_hss ) ) {
							$first      = reset( $t_spk_hss );
							$hs_id      = isset( $first['id'] ) ? (int) $first['id'] : 0;
							$hs_lbl     = isset( $first['label'] ) ? $first['label'] : $t_spk_name;
							$t_spk_img  = $hs_id ? wp_get_attachment_image( $hs_id, 'thumbnail', false, array(
								'class' => 'speekr-talk-card__speaker-img',
								'alt'   => esc_attr( $hs_lbl ),
							) ) : '';
						}
					}
				?>
					<li class="speekr-talk-card">
						<?php if ( $t_thumb ) : ?>
						<div class="speekr-talk-card__media">
							<?php if ( $t_url ) : ?>
							<a href="<?php echo esc_url( $t_url ); ?>" tabindex="-1" aria-hidden="true"><?php echo $t_thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
							<?php else : ?>
							<?php echo $t_thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>

						<div class="speekr-talk-card__content">
							<h3 class="speekr-talk-card__title">
								<?php if ( $t_url ) : ?>
								<a href="<?php echo esc_url( $t_url ); ?>"><?php echo esc_html( $t_title ); ?></a>
								<?php else : ?>
								<?php echo esc_html( $t_title ); ?>
								<?php endif; ?>
							</h3>

							<?php if ( $t_summary ) : ?>
							<p class="speekr-talk-card__summary"><?php echo esc_html( $t_summary ); ?></p>
							<?php endif; ?>

							<?php if ( $t_spk_name ) : ?>
							<div class="speekr-talk-card__speaker">
								<?php if ( $t_spk_img ) : ?>
								<span class="speekr-talk-card__speaker-avatar">
									<?php if ( $t_spk_url ) : ?><a href="<?php echo esc_url( $t_spk_url ); ?>"><?php endif; ?>
									<?php echo $t_spk_img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php if ( $t_spk_url ) : ?></a><?php endif; ?>
								</span>
								<?php endif; ?>
								<?php if ( $t_spk_url ) : ?>
								<a href="<?php echo esc_url( $t_spk_url ); ?>" class="speekr-talk-card__speaker-name"><?php echo esc_html( $t_spk_name ); ?></a>
								<?php else : ?>
								<span class="speekr-talk-card__speaker-name"><?php echo esc_html( $t_spk_name ); ?></span>
								<?php endif; ?>
							</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $speaker_ids ) ) : ?>
			<section class="speekr-conf-single__speakers">
				<h2 class="speekr-conf-single__section-title">
					<?php echo 1 === count( $speaker_ids ) ? esc_html__( 'Speaker', 'speekr' ) : esc_html__( 'Speakers', 'speekr' ); ?>
				</h2>
				<ul class="speekr-speaker-wall">
				<?php foreach ( $speaker_ids as $spk_id ) :
					$spk_id   = (int) $spk_id;
					if ( ! $spk_id || 'speekr_speaker' !== get_post_type( $spk_id ) ) continue;
					$spk_name = get_the_title( $spk_id );
					$spk_bio  = get_post_meta( $spk_id, '_speekr_bio_short', true );
					$spk_url  = get_permalink( $spk_id );
					$spk_img  = '';
					$spk_hss  = get_post_meta( $spk_id, '_speekr_headshots', true ) ?: array();
					if ( ! empty( $spk_hss ) ) {
						$first  = reset( $spk_hss );
						$hs_id  = isset( $first['id'] ) ? (int) $first['id'] : 0;
						$hs_lbl = isset( $first['label'] ) ? $first['label'] : $spk_name;
						$spk_img = $hs_id ? wp_get_attachment_image( $hs_id, 'medium', false, array(
							'class' => 'speekr-speaker-wall__img',
							'alt'   => esc_attr( $hs_lbl ),
						) ) : '';
					}
				?>
					<li class="speekr-speaker-wall__item">
						<?php if ( $spk_img ) : ?>
						<a href="<?php echo esc_url( $spk_url ); ?>" class="speekr-speaker-wall__photo">
							<?php echo $spk_img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
						<?php endif; ?>
						<a href="<?php echo esc_url( $spk_url ); ?>" class="speekr-speaker-wall__name">
							<?php echo esc_html( $spk_name ); ?>
						</a>
						<?php if ( $spk_bio ) : ?>
						<p class="speekr-speaker-wall__bio"><?php echo esc_html( $spk_bio ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

		</div><!-- .speekr-conf-single__body -->
	</div>
</div><!-- .speekr-conf-single -->
<?php
get_footer();
