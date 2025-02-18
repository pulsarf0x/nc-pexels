import { registerWidget } from '@nextcloud/vue-richtext'
import PhotoReferenceWidget from './views/PhotoReferenceWidget.vue'
import Vue from 'vue'
Vue.mixin({ methods: { t, n } })

registerWidget('pexels_photo', (el, { richObjectType, richObject, accessible }) => {
	const Widget = Vue.extend(PhotoReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})
