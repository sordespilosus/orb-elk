<template>
	<ul class="base-list">
		<li
			class="base-list__item"
			v-for="(item, key) in itemsData"
			:key="key"
			v-if="(value && item[value] !== null) || showNullable">
			<slot name="key" :item="item">
				<div class="base-list__key">{{ label ? item[label] : key }}</div>
			</slot>

			<slot name="value" :item="item">
				<div class="base-list__value">{{ value ? item[value] : item }}</div>
			</slot>
		</li>
	</ul>
</template>
<script>
import {forEach} from "lodash";

export default {
	name: "BaseList",

	data: () => ({
		itemsData: {},
	}),

	props: {
		items: { type: [Array, Object], required: true },
		label: { type: String },
		value: { type: String },
		showNullable: { type: Boolean, default: true },
	},

	created() {
        for (const item in this.items) {
            this.itemsData = {
                ...this.itemsData,
                [item]: this.items[item]
            }
        }
	},
};
</script>

<style lang="scss">
@import "../../sass/variables";

.base-list {
	&__item {
		display: flex;
		align-items: center;
		padding: 10px;

		& + & {
			border-top: 1px solid #d3d8d8;
		}

		&:hover {
			background-color: #ededed;
		}

		.app_adaptive & {
			@media screen and (max-width: $break_sm) {
				display: block;
			}
		}
	}

	&__key {
		flex: 0 0 50%;
		max-width: 250px;
		color: #7b898d;

		overflow-wrap: break-word;

		.app_adaptive & {
			@media screen and (max-width: $break_sm) {
				max-width: none;
				margin-bottom: 10px; }
		}
	}

	&__value {
		flex: 1 1 auto;
		min-width: 0;
		margin-left: 20px;

		overflow-wrap: break-word;

		.app_adaptive & {
			@media screen and (max-width: $break_sm) {
				margin-left: 0;
			}
		}
	}
}
</style>
