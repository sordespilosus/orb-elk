<template>
	<div class="base-tabs">
		<div class="base-tabs__inner"
		     :style="!modify ? `border-bottom: 2px solid #9ca8af` : ''">
			<template v-for="(tab, index) in data">
				<slot :data="{tabsInterface, tab}">
					<div
						@click="select(tab)"
						:key="index"
						class="base-tabs__item"
						:class="{
							'base-tabs__item--active': value === getValue(tab) && !modify,
							'base-tabs__item--disabled': isDisabled(tab),
							'base-tabs__item--modify': value === getValue(tab) && modify
						}">
						<slot name="label" :data="{tabsInterface, tab}">
							<span
								class="base-tabs__item_name"
								v-text="getLabel(tab)"/>
						</slot>
						<slot
							name="count"
							:data="{tabsInterface, tab}">
							<span
								class="base-tabs__item_count"
								v-text="getCount(tab)"
								v-if="countName"/>
						</slot>
					</div>
				</slot>
			</template>
		</div>
	</div>
</template>

<script>
import {get, pick} from "lodash";

export default {
	name: "BaseTabs",

	props: {
		data: { type: Array, default: () => [] },
		value: { type: null },
		modify: { type: Boolean, default: false },

		valueName: { type: String },
		labelName: { type: String },
		countName: { type: String },
		disabledName: { type: String },
	},

	computed: {
		tabsInterface() {
			return pick(this, ["getValue", "getLabel", "isActive", "select", "getCount", "isDisabled"]);
		},
	},

	methods: {
		getValue(item) {
			const { valueName } = this;
			return valueName ? get(item, valueName) : item;
		},

		getLabel(item) {
			const { labelName } = this;
			return labelName ? get(item, labelName) : item;
		},

		isDisabled(item) {
			const { disabledName } = this;
			return disabledName ? get(item, disabledName) : false;
		},

		getCount(item) {
			const { countName } = this;
			return countName ? get(item, countName) : 0;
		},

		isActive(item) {
			return this.getValue(item) === this.value;
		},

		select(item) {
			this.$emit("input", this.getValue(item));
		},
	},
};
</script>
<style lang="scss">
@import "~@/sass/variables";

.base-tabs {
	&__inner {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		margin-top: 30px;
	}

	&__item {
		flex: 0 0 auto;

		position: relative;

		font-size: 13px;
		font-weight: 500;
		color: #a2a6b0;

		cursor: pointer;

		&:hover {
			color: #0c2248;
		}

		&--modify {
			text-transform: uppercase;
			background: white;
			position: relative;
			border-radius: 10px;
			box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2);
			padding: 2px 0;
			box-sizing: border-box;
			color: #0c2248;

			&::after {
				content: "";
				position: absolute;
				left: 0;
				right: 0;
				bottom: -2px;
				height: 2px;
			}
		}

		&--active {
			position: relative;
			color: #2a3e4c;

			&::after {
				content: "";
				position: absolute;
				left: 0;
				right: 0;
				bottom: -2px;
				height: 2px;
				background-color: currentColor;
			}

			&:hover {
				color: #2a3e4c;
			}
		}

		&--disabled {
			pointer-events: none;
			opacity: .6;
		}
	}
}
</style>
