import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import SpeakerProfileMetaPanels from './edit';

const SpeakerProfileMetaPlugin = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	if ( postType !== 'speekr_speaker' ) {
		return null;
	}

	return <SpeakerProfileMetaPanels />;
};

registerPlugin( 'speekr-speaker-profile-meta', {
	render: SpeakerProfileMetaPlugin,
} );
