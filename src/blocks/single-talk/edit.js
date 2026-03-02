import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = () => {
    const blockProps = useBlockProps( {
        className: 'speekr-single-talk-editor',
    } );
    return (
        <div { ...blockProps }>
            <p style={ { color: '#757575', fontStyle: 'italic', padding: '16px' } }>
                { __( 'Single Talk — rendered on the frontend using the current talk post.', 'speekr' ) }
            </p>
        </div>
    );
};

export default Edit;
