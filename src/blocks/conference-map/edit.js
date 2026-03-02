import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
    const { height } = attributes;
    const blockProps = useBlockProps( {
        className: 'speekr-conference-map-editor',
        style: { height: `${ height }px`, background: '#e8e8e8', display: 'flex', alignItems: 'center', justifyContent: 'center' },
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Map Height', 'speekr' ) } initialOpen={ true }>
                    <RangeControl
                        label={ __( 'Map height (px)', 'speekr' ) }
                        value={ height }
                        onChange={ ( value ) => setAttributes( { height: value } ) }
                        min={ 200 }
                        max={ 900 }
                        step={ 50 }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <p style={ { color: '#757575', fontStyle: 'italic' } }>
                    { __( 'Conference Map — Leaflet renders on the frontend only.', 'speekr' ) }
                </p>
            </div>
        </>
    );
};

export default Edit;
