import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { TextControl, TextareaControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

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

	if ( ! meta ) return null;

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
			</PluginDocumentSettingPanel>

			{ /* Panel 3 — Conference */ }
			<PluginDocumentSettingPanel
				name="speekr-talk-conf"
				title={ __( 'Conference', 'speekr' ) }
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
		</>
	);
};

export default TalkMetaPanels;
