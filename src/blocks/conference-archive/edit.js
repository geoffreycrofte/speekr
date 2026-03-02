import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = () => {
    const blockProps = useBlockProps( {
        className: 'speekr-conference-archive-editor',
    } );
    return (
        <div { ...blockProps }>
            <p style={ { color: '#757575', fontStyle: 'italic', padding: '16px' } }>
                { __( 'Conference Archive — rendered on the frontend from conference posts.', 'speekr' ) }
            </p>
        </div>
    );
};

export default Edit;
