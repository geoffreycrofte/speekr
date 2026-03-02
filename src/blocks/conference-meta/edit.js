import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { TextControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

const ConferenceMetaPanels = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Talk Reference state
	const [ talkSearch, setTalkSearch ] = useState( '' );
	const [ talkResults, setTalkResults ] = useState( [] );
	const talkDebounceRef = useRef( null );
	const selectedTalkId = meta ? ( meta._speekr_conf_talk_ref ?? 0 ) : 0;
	const [ selectedTalkTitle, setSelectedTalkTitle ] = useState( '' );

	// Speaker state
	const [ speakerSearch, setSpeakerSearch ] = useState( '' );
	const [ speakerResults, setSpeakerResults ] = useState( [] );
	const [ speakerNames, setSpeakerNames ] = useState( {} );
	const speakerDebounceRef = useRef( null );
	const selectedSpeakers = meta ? ( meta._speekr_conf_speakers ?? [] ) : [];

	// Fetch title of currently selected talk on mount / when selectedTalkId changes
	useEffect( () => {
		if ( ! selectedTalkId ) return;
		apiFetch( { path: `/wp/v2/talks/${ selectedTalkId }?_fields=id,title` } )
			.then( ( post ) => setSelectedTalkTitle( post.title.rendered ) )
			.catch( () => {} );
	}, [ selectedTalkId ] );

	// Debounced talk search
	useEffect( () => {
		if ( talkSearch.length < 2 ) {
			setTalkResults( [] );
			return;
		}
		clearTimeout( talkDebounceRef.current );
		talkDebounceRef.current = setTimeout( () => {
			apiFetch( {
				path: addQueryArgs( '/wp/v2/talks', {
					search: talkSearch,
					per_page: 10,
					status: 'publish',
					_fields: 'id,title',
				} ),
			} )
				.then( setTalkResults )
				.catch( () => {} );
		}, 300 );
	}, [ talkSearch ] );

	// Load names for currently selected speakers
	useEffect( () => {
		if ( ! selectedSpeakers.length ) return;
		const uncached = selectedSpeakers.filter( ( id ) => ! speakerNames[ id ] );
		if ( ! uncached.length ) return;
		apiFetch( {
			path: addQueryArgs( '/wp/v2/speekr_speaker', {
				include: uncached.join( ',' ),
				_fields: 'id,title',
			} ),
		} )
			.then( ( posts ) => {
				const newNames = {};
				posts.forEach( ( p ) => {
					newNames[ p.id ] = p.title.rendered;
				} );
				setSpeakerNames( ( prev ) => ( { ...prev, ...newNames } ) );
			} )
			.catch( () => {} );
	}, [ selectedSpeakers ] );

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

	const addSpeaker = ( id ) => {
		if ( selectedSpeakers.includes( id ) ) return;
		setMeta( { ...meta, _speekr_conf_speakers: [ ...selectedSpeakers, id ] } );
		setSpeakerSearch( '' );
		setSpeakerResults( [] );
	};

	const removeSpeaker = ( id ) => {
		setMeta( {
			...meta,
			_speekr_conf_speakers: selectedSpeakers.filter( ( s ) => s !== id ),
		} );
	};

	if ( ! meta ) return null;

	return (
		<>
			{ /* Panel 1: Conference Details */ }
			<PluginDocumentSettingPanel
				name="speekr-conf-details"
				title={ __( 'Conference Details', 'speekr' ) }
			>
				<TextControl
					label={ __( 'Event Date', 'speekr' ) }
					value={ meta._speekr_conf_date ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_conf_date: value } )
					}
				/>
				<TextControl
					label={ __( 'City', 'speekr' ) }
					value={ meta._speekr_conf_city ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_conf_city: value } )
					}
				/>
				<TextControl
					label={ __( 'Country', 'speekr' ) }
					value={ meta._speekr_conf_country ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_conf_country: value } )
					}
				/>
				<TextControl
					label={ __( 'Event URL', 'speekr' ) }
					type="url"
					value={ meta._speekr_conf_url ?? '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, _speekr_conf_url: value } )
					}
				/>
			</PluginDocumentSettingPanel>

			{ /* Panel 2: Talk Reference */ }
			<PluginDocumentSettingPanel
				name="speekr-conf-talk"
				title={ __( 'Talk Reference', 'speekr' ) }
			>
				{ selectedTalkId > 0 && (
					<p>
						<strong>{ selectedTalkTitle || `#${ selectedTalkId }` }</strong>{ ' ' }
						<Button
							variant="link"
							isDestructive
							onClick={ () =>
								setMeta( { ...meta, _speekr_conf_talk_ref: 0 } )
							}
						>
							{ __( 'Clear', 'speekr' ) }
						</Button>
					</p>
				) }
				<TextControl
					label={ __( 'Search Talks', 'speekr' ) }
					value={ talkSearch }
					onChange={ setTalkSearch }
				/>
				{ talkResults.map( ( post ) => (
					<Button
						key={ post.id }
						variant="tertiary"
						onClick={ () => {
							setMeta( { ...meta, _speekr_conf_talk_ref: post.id } );
							setTalkSearch( '' );
							setTalkResults( [] );
						} }
					>
						{ post.title.rendered }
					</Button>
				) ) }
			</PluginDocumentSettingPanel>

			{ /* Panel 3: Speakers */ }
			<PluginDocumentSettingPanel
				name="speekr-conf-speakers"
				title={ __( 'Speakers', 'speekr' ) }
			>
				{ selectedSpeakers.length > 0 && (
					<ul style={ { margin: '0 0 8px', padding: 0, listStyle: 'none' } }>
						{ selectedSpeakers.map( ( id ) => (
							<li key={ id } style={ { display: 'flex', alignItems: 'center', gap: '4px', marginBottom: '4px' } }>
								<span>{ speakerNames[ id ] || `#${ id }` }</span>
								<Button
									variant="link"
									isDestructive
									onClick={ () => removeSpeaker( id ) }
								>
									{ __( '\u00d7', 'speekr' ) }
								</Button>
							</li>
						) ) }
					</ul>
				) }
				<TextControl
					label={ __( 'Add Speaker', 'speekr' ) }
					value={ speakerSearch }
					onChange={ setSpeakerSearch }
				/>
				{ speakerResults.map( ( post ) => (
					<Button
						key={ post.id }
						variant="tertiary"
						onClick={ () => addSpeaker( post.id ) }
					>
						{ post.title.rendered }
					</Button>
				) ) }
			</PluginDocumentSettingPanel>
		</>
	);
};

export default ConferenceMetaPanels;
