import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
    const { layout } = attributes;
    const blockProps = useBlockProps( {
        className: `speekr-talks-list-editor layout-${ layout }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout', 'speekr' ) } initialOpen={ true }>
                    <SelectControl
                        label={ __( 'Talk display layout', 'speekr' ) }
                        value={ layout }
                        options={ [
                            { label: __( 'Grid (2–3 columns)', 'speekr' ), value: 'grid' },
                            { label: __( 'List (single column)', 'speekr' ), value: 'list' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <p style={ { color: '#757575', fontStyle: 'italic', padding: '16px' } }>
                    { __( 'Talks List — rendered on the frontend with topic filters.', 'speekr' ) }
                </p>
            </div>
        </>
    );
};

export default Edit;
