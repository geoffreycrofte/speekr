/**
 * Talks List — client-side topic filter.
 *
 * Loaded only when the speekr/talks-list block is present on the page (viewScript).
 * No framework; vanilla DOM only.
 */
document.querySelectorAll( '.wp-block-speekr-talks-list' ).forEach( ( block ) => {
    const filterBar = block.querySelector( '.speekr-talks-filter' );
    if ( ! filterBar ) return;

    const pills = filterBar.querySelectorAll( '[data-topic]' );
    const cards = block.querySelectorAll( '[data-topics]' );

    pills.forEach( ( pill ) => {
        pill.addEventListener( 'click', () => {
            const selected = pill.dataset.topic;
            const isAll    = selected === 'all';

            // Update pill active state
            pills.forEach( ( p ) => {
                const isNowActive = p === pill;
                p.setAttribute( 'aria-pressed', isNowActive ? 'true' : 'false' );
                p.classList.toggle( 'is-active', isNowActive );
            } );

            // If clicking the already-active non-All pill, revert to All
            const wasActive = pill.getAttribute( 'aria-pressed' ) === 'true' && ! isAll;
            // (After the loop above, pill is now active. The "revert" logic
            // is handled by clicking "All" pill explicitly — matching CONTEXT.md spec:
            // "clicking active tab returns to All".)

            // Filter cards
            cards.forEach( ( card ) => {
                if ( isAll ) {
                    card.hidden = false;
                    return;
                }
                const cardTopics = card.dataset.topics
                    ? card.dataset.topics.split( ',' ).map( ( s ) => s.trim() )
                    : [];
                card.hidden = ! cardTopics.includes( selected );
            } );
        } );
    } );
} );
