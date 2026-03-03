import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { TextControl, TextareaControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
// Panel icons use dashicon strings (native WP, consistent with admin UI)

const TalkMetaPanels = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Panel 5 — Speaker picker state
	const [ speakerSearch, setSpeakerSearch ] = useState( '' );
	const [ speakerResults, setSpeakerResults ] = useState( [] );
	const speakerDebounceRef = useRef( null );
	const selectedSpeakerId = meta ? ( meta._speekr_talk_speaker ?? 0 ) : 0;
	const [ selectedSpeakerTitle, setSelectedSpeakerTitle ] = useState( '' );

	// Panel 2 — Other links local add-form state.
	const [ otherLabel, setOtherLabel ] = useState( '' );
	const [ otherUrl, setOtherUrl ]     = useState( '' );

	// Panel 4 — Appears In: reverse lookup state
	const [ conferences, setConferences ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	// Fetch conferences that reference this Talk on mount
	// Searches up to 100 conferences — acceptable limit for this plugin's use case.
	useEffect( () => {
		if ( ! postId ) return;
		apiFetch( {
			path: addQueryArgs( '/wp/v2/speekr_conference', {
				per_page: 100,
				_fields: 'id,title,meta',
				status: 'any',
			} ),
		} )
			.then( ( posts ) => {
				const matches = posts.filter(
					( p ) => p.meta && p.meta._speekr_conf_talk_ref === postId
				);
				setConferences( matches );
				setLoading( false );
			} )
			.catch( () => setLoading( false ) );
	}, [ postId ] );

	// Fetch title of currently selected speaker on mount / when id changes
	useEffect( () => {
		if ( ! selectedSpeakerId ) { setSelectedSpeakerTitle( '' ); return; }
		apiFetch( { path: `/wp/v2/speekr_speaker/${ selectedSpeakerId }?_fields=id,title` } )
			.then( ( post ) => setSelectedSpeakerTitle( decodeEntities( post.title.rendered ) ) )
			.catch( () => {} );
	}, [ selectedSpeakerId ] );

	// Debounced speaker search
	useEffect( () => {
		if ( speakerSearch.length < 2 ) {
			setSpeakerResults( [] );
			return;
		}
		clearTimeout( speakerDebounceRef.current );
		speakerDebounceRef.current = setTimeout( () => {
			apiFetch( {
				path: addQueryArgs( '/wp/v2/speekr_speaker', {
					search: speakerSearch,
					per_page: 10,
					status: 'publish',
					_fields: 'id,title',
				} ),
			} )
				.then( setSpeakerResults )
				.catch( () => {} );
		}, 300 );
	}, [ speakerSearch ] );

	if ( ! meta ) return null;

	// Other links helpers
	const otherLinks = meta._speekr_media_other ?? [];

	const addOtherLink = () => {
		if ( ! otherLabel || ! otherUrl ) return;
		setMeta( { ...meta, _speekr_media_other: [ ...otherLinks, { label: otherLabel, url: otherUrl } ] } );
		setOtherLabel( '' );
		setOtherUrl( '' );
	};
	const removeOtherLink = ( i ) => {
		setMeta( { ...meta, _speekr_media_other: otherLinks.filter( ( _, idx ) => idx !== i ) } );
	};

	// Helper for Conference object meta
	const conf = meta[ 'speekr-conf' ] ?? { name: '', url: '' };
	const updateConf = ( key, value ) =>
		setMeta( { ...meta, 'speekr-conf': { ...conf, [ key ]: value } } );

	return (
		<>
			{ /* Panel 1 — Talk Summary */ }
			{ /* Note: RichText not used — broken in PluginDocumentSettingPanel since WP 6.5 (Gutenberg issue #60524) */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-summary"
				title={ __( 'Talk Summary', 'speekr' ) }
				icon="format-quote"
				className={ `speekr-panel-talk-summary${ meta[ 'speekr-summary' ] ? ' is-filled' : '' }` }
			>
				<TextareaControl
					label={ __( 'Summary', 'speekr' ) }
					value={ meta[ 'speekr-summary' ] ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, 'speekr-summary': value } )
					}
					rows={ 5 }
				/>
			</PluginDocumentSettingPanel>

			{ /* Panel 2 — Media Links */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-media"
				title={ __( 'Media Links', 'speekr' ) }
				icon="video-alt2"
				className={ `speekr-panel-talk-media${
					( meta._speekr_media_youtube || meta._speekr_media_vimeo || meta._speekr_media_dailymotion ||
					  meta._speekr_media_slides || meta._speekr_media_speakerdeck || meta._speekr_media_slideshare ||
					  ( meta._speekr_media_other && meta._speekr_media_other.length > 0 ) )
					? ' is-filled' : '' }` }
			>
				<TextControl
					label={ __( 'YouTube URL', 'speekr' ) }
					type="url"
					value={ meta[ '_speekr_media_youtube' ] ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_media_youtube: value } )
					}
				/>
				<TextControl
					label={ __( 'Vimeo URL', 'speekr' ) }
					type="url"
					value={ meta[ '_speekr_media_vimeo' ] ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_media_vimeo: value } )
					}
				/>
				<TextControl
					label={ __( 'Slides URL', 'speekr' ) }
					type="url"
					value={ meta[ '_speekr_media_slides' ] ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_media_slides: value } )
					}
				/>
				<TextControl
					label={ __( 'Dailymotion URL', 'speekr' ) }
					type="url"
					value={ meta._speekr_media_dailymotion ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_media_dailymotion: v } ) }
				/>
				<TextControl
					label={ __( 'SpeakerDeck URL', 'speekr' ) }
					type="url"
					value={ meta._speekr_media_speakerdeck ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_media_speakerdeck: v } ) }
				/>
				<TextControl
					label={ __( 'Slideshare URL', 'speekr' ) }
					type="url"
					value={ meta._speekr_media_slideshare ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_media_slideshare: v } ) }
				/>
				{ otherLinks.length > 0 && (
					<ul className="speekr-other-links-list">
						{ otherLinks.map( ( link, i ) => (
							<li key={ i }>
								<span><strong>{ link.label }</strong>{ ' \u2014 ' }{ link.url }</span>
								<Button isDestructive variant="link" onClick={ () => removeOtherLink( i ) }>
									{ __( 'Remove', 'speekr' ) }
								</Button>
							</li>
						) ) }
					</ul>
				) }
				<TextControl
					label={ __( 'Other link label', 'speekr' ) }
					value={ otherLabel }
					onChange={ setOtherLabel }
				/>
				{ otherLabel && (
					<TextControl
						label={ __( 'Other link URL', 'speekr' ) }
						type="url"
						value={ otherUrl }
						onChange={ setOtherUrl }
					/>
				) }
				{ otherLabel && otherUrl && (
					<Button variant="secondary" onClick={ addOtherLink }>
						{ __( 'Add link', 'speekr' ) }
					</Button>
				) }
			</PluginDocumentSettingPanel>

			{ /* Panel 3 — Conference */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-conf"
				title={ __( 'Conference', 'speekr' ) }
				icon="location"
				className={ `speekr-panel-talk-conference${ meta[ 'speekr-conf' ]?.name ? ' is-filled' : '' }` }
			>
				<TextControl
					label={ __( 'Conference Name', 'speekr' ) }
					value={ conf.name ?? '' }
					onChange={ ( v ) => updateConf( 'name', v ) }
				/>
				<TextControl
					label={ __( 'Conference URL', 'speekr' ) }
					type="url"
					value={ conf.url ?? '' }
					onChange={ ( v ) => updateConf( 'url', v ) }
				/>
			</PluginDocumentSettingPanel>

			{ /* Panel 4 — Appears In (read-only reverse lookup) */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-appears-in"
				title={ __( 'Appears In', 'speekr' ) }
				icon="visibility"
				className="speekr-panel-talk-appears-in"
			>
				{ loading && <p>{ __( 'Loading\u2026', 'speekr' ) }</p> }
				{ ! loading && conferences.length === 0 && (
					<p>{ __( 'Not referenced by any conference.', 'speekr' ) }</p>
				) }
				{ ! loading && conferences.length > 0 && (
					<ul>
						{ conferences.map( ( conference ) => (
							<li key={ conference.id }>
								{ conference.title.rendered }
							</li>
						) ) }
					</ul>
				) }
			</PluginDocumentSettingPanel>

			{ /* Panel 5 — Speaker */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-speaker"
				title={ __( 'Speaker', 'speekr' ) }
				icon="admin-users"
				className={ `speekr-panel-talk-speaker${ selectedSpeakerId > 0 ? ' is-filled' : '' }` }
			>
				{ selectedSpeakerId > 0 && (
					<p>
						<strong>{ selectedSpeakerTitle || `#${ selectedSpeakerId }` }</strong>{ ' ' }
						<Button
							variant="link"
							isDestructive
							onClick={ () => {
								setMeta( { ...meta, _speekr_talk_speaker: 0 } );
								setSelectedSpeakerTitle( '' );
							} }
						>
							{ __( 'Clear', 'speekr' ) }
						</Button>
					</p>
				) }
				<TextControl
					label={ __( 'Search Speakers', 'speekr' ) }
					value={ speakerSearch }
					onChange={ setSpeakerSearch }
				/>
				{ speakerResults.map( ( post ) => (
					<Button
						key={ post.id }
						variant="tertiary"
						onClick={ () => {
							setMeta( { ...meta, _speekr_talk_speaker: post.id } );
							setSpeakerSearch( '' );
							setSpeakerResults( [] );
						} }
					>
						{ decodeEntities( post.title.rendered ) }
					</Button>
				) ) }
			</PluginDocumentSettingPanel>
		</>
	);
};

export default TalkMetaPanels;
