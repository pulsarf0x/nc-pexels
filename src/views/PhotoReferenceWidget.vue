<template>
	<div class="pexels-photo-reference">
		<div class="photo-wrapper">
			<strong>
				{{ richObject.alt }}
			</strong>
			<span>
				{{ richObject.photographer }}
			</span>
			<div v-if="!isLoaded" class="loading-icon">
				<NcLoadingIcon :size="44" :title="t('pexels', 'Loading Pexels stock photo')" />
			</div>
			<img v-show="isLoaded"
				 class="image"
				 :src="richObject.proxied_url"
				 @load="isLoaded = true">
			<a v-show="isLoaded"
			   class="attribution"
			   target="_blank"
			   :title="poweredByTitle"
			   href="https://pexels.com">
				<div class="content" />
			</a>
		</div>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { imagePath } from '@nextcloud/router'

export default {
	name: 'PhotoReferenceWidget',

	components: {
		NcLoadingIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			isLoaded: false,
			poweredByImgSrc: imagePath('pexels', 'pexels.logo.png'),
			poweredByTitle: t('pexels', 'Powered by Pexels'),
		}
	},

	computed: {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.pexels-photo-reference {
	width: 100%;
	padding: 12px;
	white-space: normal;

	.photo-wrapper {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		position: relative;

		.image {
			max-height: 300px;
			max-width: 100%;
			border-radius: var(--border-radius);
			margin-top: 8px;
		}

		.attribution {
			position: absolute;
			left: 0;
			bottom: 0;
			height: 33px;
			width: 80px;
			padding: 0;
			border-radius: var(--border-radius);
			background-color: var(--color-main-background);
			.content {
				height: 33px;
				width: 80px;
				background-image: url('../../img/pexels.logo.png');
				background-size: 80px 33px;
				filter: var(--background-invert-if-dark);
			}
		}
	}
}
</style>
