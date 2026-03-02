import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

// Fix Leaflet default marker icon paths broken by webpack bundling (well-known issue).
// Without this fix, pins render as broken image icons.
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions( {
    iconUrl:       markerIconUrl,
    iconRetinaUrl: markerIcon2xUrl,
    shadowUrl:     markerShadowUrl,
} );

/**
 * Build the popup HTML for a conference pin.
 *
 * @param {Object} pin Conference geodata object from data-speekr-map JSON.
 * @returns {string} HTML string for Leaflet popup.
 */
function buildPopup( pin ) {
    let html = `<strong>${ escHtml( pin.name ) }</strong>`;
    if ( pin.date ) html += `<br><time>${ escHtml( pin.date ) }</time>`;
    if ( pin.city ) html += ` &mdash; ${ escHtml( pin.city ) }`;
    if ( pin.talkTitle ) html += `<br>${ escHtml( pin.talkTitle ) }`;
    if ( pin.eventUrl ) {
        html += `<br><a href="${ escAttr( pin.eventUrl ) }" rel="noopener noreferrer" target="_blank">Event site</a>`;
    }
    if ( pin.talkUrl ) {
        html += ` | <a href="${ escAttr( pin.talkUrl ) }" rel="noopener noreferrer" target="_blank">Talk page</a>`;
    }
    return html;
}

function escHtml( str ) {
    if ( ! str ) return '';
    return String( str )
        .replace( /&/g, '&amp;' )
        .replace( /</g, '&lt;' )
        .replace( />/g, '&gt;' )
        .replace( /"/g, '&quot;' );
}

function escAttr( str ) {
    return escHtml( str );
}

document.querySelectorAll( '.wp-block-speekr-conference-map' ).forEach( ( el ) => {
    const rawData = el.dataset.speekrMap;
    if ( ! rawData ) return;

    let data;
    try {
        data = JSON.parse( rawData );
    } catch ( e ) {
        return;
    }
    if ( ! data.length ) return;

    const map = L.map( el ).setView( [ 20, 0 ], 2 );

    L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    } ).addTo( map );

    const cluster = L.markerClusterGroup();

    data.forEach( ( pin ) => {
        if ( typeof pin.lat !== 'number' || typeof pin.lng !== 'number' ) return;
        const marker = L.marker( [ pin.lat, pin.lng ] );
        marker.bindPopup( buildPopup( pin ) );
        cluster.addLayer( marker );
    } );

    map.addLayer( cluster );
} );
