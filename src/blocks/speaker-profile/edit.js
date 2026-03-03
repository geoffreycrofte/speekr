import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, TextControl, Button } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
    const { layout, allowDownload, speakerId } = attributes;
    const blockProps = useBlockProps( {
        className: `speekr-speaker-profile layout-${ layout }`,
    } );

    const [ speakerSearch, setSpeakerSearch ] = useState( '' );
    const [ speakerResults, setSpeakerResults ] = useState( [] );
    const speakerDebounceRef = useRef( null );
    const [ selectedSpeakerTitle, setSelectedSpeakerTitle ] = useState( '' );

    // Fetch title of currently selected speaker
    useEffect( () => {
        if ( ! speakerId ) { setSelectedSpeakerTitle( '' ); return; }
        apiFetch( { path: `/wp/v2/speekr_speaker/${ speakerId }?_fields=id,title` } )
            .then( ( post ) => setSelectedSpeakerTitle( decodeEntities( post.title.rendered ) ) )
            .catch( () => {} );
    }, [ speakerId ] );

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

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Speaker', 'speekr' ) } initialOpen={ true }>
                    { speakerId > 0 && (
                        <p>
                            <strong>{ selectedSpeakerTitle || `#${ speakerId }` }</strong>{ ' ' }
                            <Button
                                variant="link"
                                isDestructive
                                onClick={ () => {
                                    setAttributes( { speakerId: 0 } );
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
                                setAttributes( { speakerId: post.id } );
                                setSpeakerSearch( '' );
                                setSpeakerResults( [] );
                            } }
                        >
                            { decodeEntities( post.title.rendered ) }
                        </Button>
                    ) ) }
                    { speakerId === 0 && (
                        <p style={ { fontSize: '12px', color: '#757575', marginTop: '8px' } }>
                            { __( 'Leave blank to auto-select (only one speaker, or current talk\'s speaker).', 'speekr' ) }
                        </p>
                    ) }
                </PanelBody>
                <PanelBody title={ __( 'Layout', 'speekr' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Profile layout', 'speekr' ) }
                        value={ layout }
                        options={ [
                            { label: __( 'Headshot left (side-by-side)', 'speekr' ), value: 'side-by-side' },
                            { label: __( 'Headshot above (stacked)', 'speekr' ), value: 'stacked' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                </PanelBody>
                <PanelBody title={ __( 'Press Kit', 'speekr' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Allow press kit download', 'speekr' ) }
                        help={ __( 'Shows a "Download press kit" button that streams a ZIP of headshots and speaker info.', 'speekr' ) }
                        checked={ allowDownload }
                        onChange={ ( value ) => setAttributes( { allowDownload: value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <p style={ { color: '#757575', fontStyle: 'italic', padding: '16px' } }>
                    { speakerId > 0
                        ? `${ __( 'Speaker Profile', 'speekr' ) }: ${ selectedSpeakerTitle || `#${ speakerId }` }`
                        : __( 'Speaker Profile — rendered on the frontend from post meta.', 'speekr' )
                    }
                </p>
            </div>
        </>
    );
};

export default Edit;
