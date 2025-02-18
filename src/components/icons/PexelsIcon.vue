<template>
	<span :aria-hidden="!title"
	:aria-label="title"
	class="material-design-icon pexels-icon"
	role="img"
	v-bind="$attrs"
	@click="$emit('click', $event)">
	<svg :fill="fillColor"
	:width="size"
	:height="size"
	enable-background="new 0 0 32 32"
	version="1.1"
	viewBox="0 0 32 32"
	xml:space="preserve"
	xmlns="http://www.w3.org/2000/svg">
	<path d="M 2 0 A 2 2 0 0 0 0 2 L 0 30 A 2 2 0 0 0 2 32 L 30 32 A 2 2 0 0 0 32 30 L 32 2 A 2 2 0 0 0 30 0 L 2 0 z M 11 9 L 18.029297 9 A 5.124 5.124 0 0 1 18.863281 19.179688 L 18.863281 23 L 11 23 L 11 9 z M 13 11 L 13 21 L 16.863281 21 L 16.863281 17.248047 L 18.029297 17.248047 A 3.1240234 3.1240234 0 1 0 18.029297 11 L 13 11 z" />
</svg>
</span>
</template>

<script>
	export default {
	name: 'PexelsIcon',
	props: {
	title: {
	type: String,
	default: '',
},
	fillColor: {
	type: String,
	default: 'currentColor',
},
	size: {
	type: Number,
	default: 24,
},
},
}
</script>
