import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import ConferenceMetaPanels from './edit';

const ConferenceMetaPlugin = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	if ( postType !== 'speekr_conference' ) {
		return null;
	}

	return <ConferenceMetaPanels />;
};

registerPlugin( 'speekr-conference-meta', {
	render: ConferenceMetaPlugin,
} );
