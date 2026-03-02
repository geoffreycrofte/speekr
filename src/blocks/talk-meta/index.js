import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import TalkMetaPanels from './edit';

const TalkMetaPlugin = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	if ( postType !== 'talks' ) {
		return null;
	}

	return <TalkMetaPanels />;
};

registerPlugin( 'speekr-talk-meta', {
	render: TalkMetaPlugin,
} );
