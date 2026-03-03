import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
    const { talkId } = attributes;
    const blockProps = useBlockProps( {
        className: 'speekr-single-talk-editor',
    } );

    const [ talkSearch, setTalkSearch ] = useState( '' );
    const [ talkResults, setTalkResults ] = useState( [] );
    const talkDebounceRef = useRef( null );
    const [ selectedTalkTitle, setSelectedTalkTitle ] = useState( '' );

    // Fetch title of currently selected talk
    useEffect( () => {
        if ( ! talkId ) { setSelectedTalkTitle( '' ); return; }
        apiFetch( { path: `/wp/v2/talks/${ talkId }?_fields=id,title` } )
            .then( ( post ) => setSelectedTalkTitle( decodeEntities( post.title.rendered ) ) )
            .catch( () => {} );
    }, [ talkId ] );

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

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Talk', 'speekr' ) } initialOpen={ true }>
                    { talkId > 0 && (
                        <p>
                            <strong>{ selectedTalkTitle || `#${ talkId }` }</strong>{ ' ' }
                            <Button
                                variant="link"
                                isDestructive
                                onClick={ () => {
                                    setAttributes( { talkId: 0 } );
                                    setSelectedTalkTitle( '' );
                                } }
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
                                setAttributes( { talkId: post.id } );
                                setTalkSearch( '' );
                                setTalkResults( [] );
                            } }
                        >
                            { decodeEntities( post.title.rendered ) }
                        </Button>
                    ) ) }
                    { talkId === 0 && (
                        <p style={ { fontSize: '12px', color: '#757575', marginTop: '8px' } }>
                            { __( 'Leave blank to auto-use the current talk page context.', 'speekr' ) }
                        </p>
                    ) }
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <p style={ { color: '#757575', fontStyle: 'italic', padding: '16px' } }>
                    { talkId > 0
                        ? `${ __( 'Single Talk', 'speekr' ) }: ${ selectedTalkTitle || `#${ talkId }` }`
                        : __( 'Single Talk — rendered on the frontend using the current talk post.', 'speekr' )
                    }
                </p>
            </div>
        </>
    );
};

export default Edit;
