/**
 * Internal dependencies
 */
import { SkeletonRegion, SkeletonRows, SkeletonSection } from './skeleton';

/**
 * Placeholder for a settings tab: two cards of rows.
 */
export default function FormSkeleton() {
	return (
		<SkeletonRegion className="lw-skel-tab">
			<SkeletonSection>
				<SkeletonRows count={ 3 } />
			</SkeletonSection>
			<SkeletonSection>
				<SkeletonRows count={ 4 } />
			</SkeletonSection>
		</SkeletonRegion>
	);
}
