import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
    const { layout, allowDownload } = attributes;
    const blockProps = useBlockProps( {
        className: `speekr-speaker-profile layout-${ layout }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout', 'speekr' ) } initialOpen={ true }>
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
                    { __( 'Speaker Profile — rendered on the frontend from post meta.', 'speekr' ) }
                </p>
            </div>
        </>
    );
};

export default Edit;
